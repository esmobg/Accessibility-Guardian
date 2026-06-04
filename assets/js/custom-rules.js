/**
 * Accessibility Guardian - supplemental checks not covered by axe-core.
 *
 * Each rule returns the same shape axe-core uses for violations so the
 * server-side normalizer can treat them uniformly.
 */
( function () {
	'use strict';

	var GENERIC_LINK_TEXT = [
		'click here',
		'click',
		'here',
		'read more',
		'more',
		'learn more',
		'details',
		'this link',
		'continue'
	];

	function textOf( el ) {
		return ( el.textContent || '' ).replace( /\s+/g, ' ' ).trim().toLowerCase();
	}

	function selectorFor( el ) {
		if ( el.id ) {
			return '#' + el.id;
		}
		var name = el.nodeName.toLowerCase();
		if ( el.className && typeof el.className === 'string' ) {
			name += '.' + el.className.trim().split( /\s+/ ).slice( 0, 2 ).join( '.' );
		}
		return name;
	}

	function nodeFor( el, summary ) {
		return {
			html: el.outerHTML ? el.outerHTML.slice( 0, 400 ) : '',
			target: [ selectorFor( el ) ],
			failureSummary: summary || ''
		};
	}

	function ruleResult( id, impact, help, nodes ) {
		return { id: id, impact: impact, help: help, nodes: nodes };
	}

	function checkGenericLinks( doc ) {
		var nodes = [];
		var links = doc.querySelectorAll( 'a[href]' );
		Array.prototype.forEach.call( links, function ( link ) {
			var label = link.getAttribute( 'aria-label' );
			var text = label ? label.trim().toLowerCase() : textOf( link );
			if ( text && GENERIC_LINK_TEXT.indexOf( text ) !== -1 ) {
				nodes.push( nodeFor( link, 'Link text "' + text + '" does not describe its destination.' ) );
			}
		} );
		return nodes.length
			? ruleResult( 'ag-generic-link-text', 'moderate', 'Links must use descriptive text', nodes )
			: null;
	}

	function checkPlaceholderLabels( doc ) {
		var nodes = [];
		var fields = doc.querySelectorAll( 'input[placeholder], textarea[placeholder]' );
		Array.prototype.forEach.call( fields, function ( field ) {
			var id = field.getAttribute( 'id' );
			var hasLabel = ( id && doc.querySelector( 'label[for="' + cssEscape( id ) + '"]' ) ) ||
				field.closest( 'label' ) ||
				field.getAttribute( 'aria-label' ) ||
				field.getAttribute( 'aria-labelledby' );
			if ( ! hasLabel ) {
				nodes.push( nodeFor( field, 'Field relies on placeholder text instead of a persistent label.' ) );
			}
		} );
		return nodes.length
			? ruleResult( 'ag-placeholder-as-label', 'moderate', 'Placeholder is not a substitute for a label', nodes )
			: null;
	}

	function checkNewWindow( doc ) {
		var nodes = [];
		var links = doc.querySelectorAll( 'a[target="_blank"]' );
		Array.prototype.forEach.call( links, function ( link ) {
			var label = ( link.getAttribute( 'aria-label' ) || link.textContent || '' ).toLowerCase();
			var warns = /new window|new tab|opens in/.test( label );
			if ( ! warns ) {
				nodes.push( nodeFor( link, 'Link opens in a new window without warning the user.' ) );
			}
		} );
		return nodes.length
			? ruleResult( 'ag-new-window-warning', 'minor', 'Warn users when links open new windows', nodes )
			: null;
	}

	function checkPdfLinks( doc ) {
		var nodes = [];
		var links = doc.querySelectorAll( 'a[href]' );
		Array.prototype.forEach.call( links, function ( link ) {
			var href = ( link.getAttribute( 'href' ) || '' ).toLowerCase();
			if ( /\.pdf($|\?|#)/.test( href ) ) {
				nodes.push( nodeFor( link, 'Linked PDF detected; verify it is tagged and accessible.' ) );
			}
		} );
		return nodes.length
			? ruleResult( 'ag-pdf-link', 'minor', 'Verify accessibility of linked PDF documents', nodes )
			: null;
	}

	function cssEscape( value ) {
		if ( window.CSS && window.CSS.escape ) {
			return window.CSS.escape( value );
		}
		return String( value ).replace( /([^a-zA-Z0-9_-])/g, '\\$1' );
	}

	var SEVERITY_BY_RULE = {
		'ag-generic-link-text': 'minor',
		'ag-placeholder-as-label': 'minor',
		'ag-new-window-warning': 'warning',
		'ag-pdf-link': 'warning'
	};

	window.agCustomRules = {
		run: function ( doc ) {
			var checks = [ checkGenericLinks, checkPlaceholderLabels, checkNewWindow, checkPdfLinks ];
			var violations = [];
			checks.forEach( function ( check ) {
				var result = check( doc );
				if ( result ) {
					violations.push( result );
				}
			} );
			return { violations: violations };
		},
		severityFor: function ( rule ) {
			return SEVERITY_BY_RULE[ rule.id ] || null;
		}
	};
} )();
