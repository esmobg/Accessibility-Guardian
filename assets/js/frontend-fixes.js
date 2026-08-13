/**
 * Accessibility Guardian - front-end automatic fixes.
 *
 * Applies opt-in DOM remediations on page load based on flags supplied by the
 * server. CSS-only fixes are handled via body classes, not here.
 */
( function () {
	'use strict';

	var config = window.accgFixes || {};
	var flags = config.flags || {};
	var i18n = config.i18n || {};

	function ensureHtmlLang() {
		var html = document.documentElement;
		if ( ! html.getAttribute( 'lang' ) && config.lang ) {
			html.setAttribute( 'lang', config.lang );
		}
	}

	function fixViewport() {
		var meta = document.querySelector( 'meta[name="viewport"]' );
		if ( ! meta ) {
			return;
		}
		var content = meta.getAttribute( 'content' ) || '';
		content = content
			.replace( /,?\s*user-scalable\s*=\s*(no|0)/gi, '' )
			.replace( /,?\s*maximum-scale\s*=\s*[0-9.]+/gi, '' );
		if ( ! /maximum-scale/i.test( content ) ) {
			content += ', maximum-scale=5.0';
		}
		meta.setAttribute( 'content', content.replace( /^[,\s]+/, '' ) );
	}

	function removePositiveTabindex() {
		var nodes = document.querySelectorAll( '[tabindex]' );
		Array.prototype.forEach.call( nodes, function ( el ) {
			var value = parseInt( el.getAttribute( 'tabindex' ), 10 );
			if ( ! isNaN( value ) && value > 0 ) {
				el.setAttribute( 'tabindex', '0' );
			}
		} );
	}

	function removeTitleAttr() {
		var nodes = document.querySelectorAll( 'a[title], input[title], button[title]' );
		Array.prototype.forEach.call( nodes, function ( el ) {
			var text = ( el.textContent || el.value || '' ).trim();
			if ( text ) {
				el.removeAttribute( 'title' );
			}
		} );
	}

	function newWindowWarning() {
		var notice = i18n.newWindow || '(opens in a new window)';
		var links = document.querySelectorAll( 'a[target="_blank"]' );
		Array.prototype.forEach.call( links, function ( link ) {
			if ( link.getAttribute( 'data-ag-new-window' ) ) {
				return;
			}
			var label = ( link.getAttribute( 'aria-label' ) || link.textContent || '' ).toLowerCase();
			if ( /new window|new tab|opens in/.test( label ) ) {
				return;
			}
			var span = document.createElement( 'span' );
			span.className = 'screen-reader-text ag-new-window-note';
			span.textContent = ' ' + notice;
			link.appendChild( span );
			if ( ! link.getAttribute( 'rel' ) || ! /noopener/.test( link.getAttribute( 'rel' ) ) ) {
				link.setAttribute( 'rel', ( ( link.getAttribute( 'rel' ) || '' ) + ' noopener' ).trim() );
			}
			link.setAttribute( 'data-ag-new-window', '1' );
		} );
	}

	function init() {
		if ( flags.ensureHtmlLang ) {
			ensureHtmlLang();
		}
		if ( flags.fixViewport ) {
			fixViewport();
		}
		if ( flags.removePositiveTabindex ) {
			removePositiveTabindex();
		}
		if ( flags.removeTitleAttr ) {
			removeTitleAttr();
		}
		if ( flags.newWindowWarning ) {
			newWindowWarning();
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
