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
		counts: { critical: 0, major: 0, minor: 0, warning: 0 },
		running: false,
		cancelled: false
	};

	var els = {};

	function t( key, fallback ) {
		return i18n[ key ] || fallback;
	}

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
			return res.json();
		} );
	}

	function setProgress( done, total, message ) {
		if ( els.bar ) {
			var percent = total > 0 ? Math.round( ( done / total ) * 100 ) : 0;
			els.bar.style.width = percent + '%';
			els.bar.setAttribute( 'aria-valuenow', String( percent ) );
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

	function accumulate( violations ) {
		violations.forEach( function ( rule ) {
			var severity = window.agCustomRules && window.agCustomRules.severityFor
				? window.agCustomRules.severityFor( rule )
				: null;
			var count = ( rule.nodes || [] ).length || 1;
			var bucket = severity || mapImpact( rule.impact );
			state.counts[ bucket ] += count;
		} );
	}

	function mapImpact( impact ) {
		switch ( impact ) {
			case 'critical':
				return 'critical';
			case 'serious':
				return 'major';
			case 'moderate':
				return 'minor';
			default:
				return 'warning';
		}
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
				accumulate( results.violations );

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
					var found = res && res.success ? res.data.inserted : 0;
					logLine( entry.label + ' — ' + found + ' ' + t( 'issues', 'issues' ), found > 0 ? 'warn' : 'ok' );
				} );
			} )
			.catch( function () {
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
			state.running = false;
			toggleControls( false );

			if ( res && res.success ) {
				setProgress( state.queue.length, state.queue.length, t( 'complete', 'Scan complete' ) );
				logLine(
					t( 'score', 'Score' ) + ': ' + res.data.score + '% (' + res.data.band.label + ')',
					'ok'
				);
				if ( els.viewReport && res.data ) {
					els.viewReport.hidden = false;
				}
				window.setTimeout( function () {
					window.location.reload();
				}, 1500 );
			} else {
				logLine( t( 'finishError', 'Scan could not be finalized.' ), 'error' );
			}
		} );
	}

	function toggleControls( running ) {
		if ( els.start ) {
			els.start.disabled = running;
		}
		if ( els.cancel ) {
			els.cancel.hidden = ! running;
		}
		if ( els.progressWrap ) {
			els.progressWrap.hidden = ! running;
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
		state.counts = { critical: 0, major: 0, minor: 0, warning: 0 };

		if ( els.log ) {
			els.log.innerHTML = '';
		}
		toggleControls( true );
		setProgress( 0, 1, t( 'preparing', 'Preparing scan…' ) );

		post( 'ag_start_scan', { scan_type: scanType, post_id: postId || 0 } )
			.then( function ( res ) {
				if ( ! res || ! res.success ) {
					state.running = false;
					toggleControls( false );
					logLine( ( res && res.data && res.data.message ) || t( 'startError', 'Could not start scan.' ), 'error' );
					return;
				}

				state.scanId = res.data.scan_id;
				state.queue = res.data.queue;
				setProgress( 0, state.queue.length );
				logLine( t( 'queued', 'Queued URLs' ) + ': ' + state.queue.length, 'ok' );
				processNext();
			} );
	}

	function init() {
		els.frame = document.getElementById( 'ag-scan-frame' );
		els.start = document.getElementById( 'ag-start-scan' );
		els.cancel = document.getElementById( 'ag-cancel-scan' );
		els.bar = document.getElementById( 'ag-progress-bar' );
		els.count = document.getElementById( 'ag-progress-count' );
		els.message = document.getElementById( 'ag-progress-message' );
		els.progressWrap = document.getElementById( 'ag-progress' );
		els.log = document.getElementById( 'ag-scan-log' );
		els.viewReport = document.getElementById( 'ag-view-report' );

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
				logLine( t( 'cancelling', 'Cancelling after current page…' ), 'warn' );
			} );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
