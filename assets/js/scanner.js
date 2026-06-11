/**
 * Accessibility Guardian - browser scan orchestrator.
 *
 * Loads each queued URL into a hidden same-origin iframe, runs axe-core
 * (plus supplemental custom rules) against the rendered DOM, and streams
 * normalized results back to the server in batches.
 */
( function () {
	'use strict';

	var settings = window.agScanner || {};
	var ajaxUrl = settings.ajaxUrl;
	var nonce = settings.nonce;
	var i18n = settings.i18n || {};

	var state = {
		scanId: 0,
		queue: [],
		index: 0,
		passes: 0,
		failures: 0,
		issuesFound: 0,
		running: false,
		cancelled: false
	};

	var els = {};

	function t( key, fallback ) {
		return i18n[ key ] || fallback;
	}

	/**
	 * POST to admin-ajax and resolve with parsed JSON. Rejects on network
	 * failures, HTTP errors and non-JSON responses (e.g. PHP fatals) so the
	 * UI can always recover instead of hanging.
	 */
	function post( action, data ) {
		var body = new FormData();
		body.append( 'action', action );
		body.append( 'nonce', nonce );
		Object.keys( data ).forEach( function ( key ) {
			body.append( key, data[ key ] );
		} );

		return fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} ).then( function ( res ) {
			return res.json().catch( function () {
				throw new Error( 'invalid-json' );
			} );
		} );
	}

	function setProgress( done, total, message ) {
		var percent = total > 0 ? Math.round( ( done / total ) * 100 ) : 0;
		if ( els.bar ) {
			els.bar.style.width = percent + '%';
		}
		if ( els.track ) {
			els.track.setAttribute( 'aria-valuenow', String( percent ) );
		}
		if ( els.count ) {
			els.count.textContent = done + ' / ' + total;
		}
		if ( els.message && message ) {
			els.message.textContent = message;
		}
	}

	function logLine( text, type ) {
		if ( ! els.log ) {
			return;
		}
		var li = document.createElement( 'li' );
		li.className = 'ag-log__item' + ( type ? ' ag-log__item--' + type : '' );
		li.textContent = text;
		els.log.appendChild( li );
		els.log.scrollTop = els.log.scrollHeight;
	}

	/**
	 * Run axe-core inside the iframe document, then merge custom rule results.
	 */
	function analyze( frameWindow, frameDocument ) {
		var axe = frameWindow.axe;
		if ( ! axe ) {
			return Promise.reject( new Error( 'axe-core unavailable in frame' ) );
		}

		var options = {
			resultTypes: [ 'violations', 'incomplete', 'passes' ],
			runOnly: { type: 'tag', values: [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice' ] }
		};

		return axe.run( frameDocument, options ).then( function ( results ) {
			var custom = window.agCustomRules ? window.agCustomRules.run( frameDocument ) : { violations: [] };

			return {
				violations: results.violations.concat( custom.violations || [] ),
				incomplete: results.incomplete || [],
				passes: ( results.passes || [] ).length
			};
		} );
	}

	function compactResult( result ) {
		return ( result || [] ).map( function ( rule ) {
			return {
				id: rule.id,
				impact: rule.impact || '',
				help: rule.help || '',
				nodes: ( rule.nodes || [] ).slice( 0, 25 ).map( function ( node ) {
					return {
						html: node.html || '',
						target: node.target || [],
						failureSummary: node.failureSummary || ''
					};
				} )
			};
		} );
	}

	/**
	 * Inject axe-core into the frame document (by URL, same-origin) and wait
	 * until the axe global is available inside the frame.
	 */
	function injectAxe( ctx ) {
		return new Promise( function ( resolve, reject ) {
			if ( ctx.win.axe ) {
				resolve( ctx );
				return;
			}

			var doc = ctx.doc;
			var script = doc.createElement( 'script' );
			script.src = settings.axeUrl;
			script.onload = function () {
				resolve( ctx );
			};
			script.onerror = function () {
				reject( new Error( 'axe load failed' ) );
			};
			( doc.head || doc.documentElement ).appendChild( script );
		} );
	}

	/**
	 * Load a URL into the hidden iframe and resolve with its document once ready.
	 */
	function loadFrame( url ) {
		return new Promise( function ( resolve, reject ) {
			var frame = els.frame;
			var settled = false;

			var timer = window.setTimeout( function () {
				if ( ! settled ) {
					settled = true;
					reject( new Error( 'timeout' ) );
				}
			}, 30000 );

			frame.onload = function () {
				if ( settled ) {
					return;
				}
				settled = true;
				window.clearTimeout( timer );

				try {
					var doc = frame.contentDocument || frame.contentWindow.document;
					resolve( { win: frame.contentWindow, doc: doc } );
				} catch ( err ) {
					reject( err );
				}
			};

			frame.src = url;
		} );
	}

	function processNext() {
		if ( state.cancelled || state.index >= state.queue.length ) {
			return finish();
		}

		var entry = state.queue[ state.index ];
		setProgress( state.index, state.queue.length, t( 'scanning', 'Scanning' ) + ': ' + entry.label );

		loadFrame( entry.url )
			.then( injectAxe )
			.then( function ( ctx ) {
				return analyze( ctx.win, ctx.doc );
			} )
			.then( function ( results ) {
				state.passes += results.passes;

				var payload = {
					url: entry.url,
					post_id: entry.post_id,
					violations: compactResult( results.violations ),
					incomplete: compactResult( results.incomplete )
				};

				return post( 'ag_save_results', {
					scan_id: state.scanId,
					payload: JSON.stringify( payload )
				} ).then( function ( res ) {
					if ( ! res || ! res.success ) {
						state.failures += 1;
						logLine( entry.label + ' — ' + t( 'saveFailed', 'results could not be saved' ), 'error' );
						return;
					}
					var found = res.data.inserted || 0;
					state.issuesFound += found;
					logLine( entry.label + ' — ' + found + ' ' + t( 'issues', 'issues' ), found > 0 ? 'warn' : 'ok' );
				} );
			} )
			.catch( function () {
				state.failures += 1;
				logLine( entry.label + ' — ' + t( 'failed', 'could not be scanned' ), 'error' );
			} )
			.then( function () {
				state.index += 1;
				setProgress( state.index, state.queue.length );
				processNext();
			} );
	}

	function finish() {
		post( 'ag_finish_scan', {
			scan_id: state.scanId,
			passes: state.passes
		} ).then( function ( res ) {
			if ( res && res.success ) {
				var doneLabel = state.cancelled ? t( 'cancelled', 'Scan cancelled' ) : t( 'complete', 'Scan complete' );
				setProgress( state.index, state.queue.length, doneLabel );
				logLine( t( 'score', 'Score' ) + ': ' + res.data.score + '% (' + res.data.band.label + ')', 'ok' );

				if ( state.failures > 0 ) {
					logLine( state.failures + ' ' + t( 'pagesFailed', 'pages could not be scanned' ), 'warn' );
				}

				showResult( res.data );
			} else {
				logLine( t( 'finishError', 'Scan could not be finalized.' ), 'error' );
			}
		} ).catch( function () {
			logLine( t( 'networkError', 'Connection problem while talking to the server.' ), 'error' );
		} ).then( function () {
			state.running = false;
			toggleControls( false );
		} );
	}

	/**
	 * Render the completion summary panel with the score and severity counts.
	 */
	function showResult( data ) {
		if ( ! els.result ) {
			return;
		}

		var counts = data.counts || {};
		var fill = function ( id, value ) {
			var node = document.getElementById( id );
			if ( node ) {
				node.textContent = String( value );
			}
		};

		fill( 'ag-result-score', data.score + '%' );
		fill( 'ag-result-band', data.band && data.band.label ? data.band.label : '' );
		fill( 'ag-result-critical', counts.critical || 0 );
		fill( 'ag-result-major', counts.major || 0 );
		fill( 'ag-result-minor', counts.minor || 0 );
		fill( 'ag-result-warning', counts.warning || 0 );

		var badge = document.getElementById( 'ag-result-badge' );
		if ( badge && data.band && data.band.key ) {
			badge.className = 'ag-result__badge ag-result__badge--' + data.band.key;
		}

		els.result.hidden = false;
		els.result.focus();
	}

	function toggleControls( running ) {
		if ( els.start ) {
			els.start.disabled = running;
		}
		if ( els.cancel ) {
			els.cancel.hidden = ! running;
			els.cancel.disabled = false;
		}
		// The progress block stays visible after a run so the final state remains readable.
		if ( els.progressWrap && running ) {
			els.progressWrap.hidden = false;
		}
	}

	function start( scanType, postId ) {
		if ( state.running ) {
			return;
		}

		state.running = true;
		state.cancelled = false;
		state.index = 0;
		state.passes = 0;
		state.failures = 0;
		state.issuesFound = 0;

		if ( els.log ) {
			els.log.innerHTML = '';
		}
		if ( els.result ) {
			els.result.hidden = true;
		}
		toggleControls( true );
		setProgress( 0, 1, t( 'preparing', 'Preparing scan…' ) );

		post( 'ag_start_scan', { scan_type: scanType, post_id: postId || 0 } )
			.then( function ( res ) {
				if ( ! res || ! res.success || ! res.data || ! res.data.queue ) {
					throw new Error( ( res && res.data && res.data.message ) || '' );
				}

				state.scanId = res.data.scan_id;
				state.queue = res.data.queue;
				setProgress( 0, state.queue.length );
				logLine( t( 'queued', 'Queued URLs' ) + ': ' + state.queue.length, 'ok' );
				processNext();
			} )
			.catch( function ( err ) {
				state.running = false;
				toggleControls( false );
				logLine( ( err && err.message ) || t( 'startError', 'Could not start scan.' ), 'error' );
			} );
	}

	function init() {
		els.frame = document.getElementById( 'ag-scan-frame' );
		els.start = document.getElementById( 'ag-start-scan' );
		els.cancel = document.getElementById( 'ag-cancel-scan' );
		els.bar = document.getElementById( 'ag-progress-bar' );
		els.track = document.getElementById( 'ag-progress-track' );
		els.count = document.getElementById( 'ag-progress-count' );
		els.message = document.getElementById( 'ag-progress-message' );
		els.progressWrap = document.getElementById( 'ag-progress' );
		els.log = document.getElementById( 'ag-scan-log' );
		els.result = document.getElementById( 'ag-scan-result' );

		if ( ! els.frame || ! els.start ) {
			return;
		}

		els.start.addEventListener( 'click', function () {
			var scanType = els.start.getAttribute( 'data-scan-type' ) || 'full';
			var postId = parseInt( els.start.getAttribute( 'data-post-id' ) || '0', 10 );
			start( scanType, postId );
		} );

		if ( els.cancel ) {
			els.cancel.addEventListener( 'click', function () {
				state.cancelled = true;
				els.cancel.disabled = true;
				logLine( t( 'cancelling', 'Cancelling after current page…' ), 'warn' );
			} );
		}

		// Warn before leaving mid-scan so a closed tab does not strand the scan.
		window.addEventListener( 'beforeunload', function ( event ) {
			if ( state.running ) {
				event.preventDefault();
				event.returnValue = '';
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
