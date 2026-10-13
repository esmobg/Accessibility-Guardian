/**
 * Accessibility Guardian website: progressive enhancements.
 * The page is fully usable without this script.
 */
( function () {
	'use strict';

	var root = document.documentElement;

	/* Theme toggle --------------------------------------------------------- */

	function systemPrefersDark() {
		return window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches;
	}

	function isDark() {
		var theme = root.getAttribute( 'data-theme' );
		return theme ? theme === 'dark' : systemPrefersDark();
	}

	function initThemeToggle() {
		var button = document.querySelector( '.theme-toggle' );
		if ( ! button ) {
			return;
		}

		function sync() {
			button.setAttribute( 'aria-pressed', isDark() ? 'true' : 'false' );
		}

		button.hidden = false;
		sync();

		button.addEventListener( 'click', function () {
			var next = isDark() ? 'light' : 'dark';
			root.setAttribute( 'data-theme', next );
			try {
				localStorage.setItem( 'accg-theme', next );
			} catch ( e ) {}
			sync();
		} );

		if ( window.matchMedia ) {
			var query = window.matchMedia( '(prefers-color-scheme: dark)' );
			if ( query.addEventListener ) {
				query.addEventListener( 'change', sync );
			}
		}
	}

	/* Screenshot lightbox --------------------------------------------------- */

	function initLightbox() {
		var dialog = document.getElementById( 'lightbox' );
		var links = Array.prototype.slice.call( document.querySelectorAll( '.gallery__link' ) );
		if ( ! dialog || ! links.length || typeof dialog.showModal !== 'function' ) {
			return; // Without <dialog> support the links simply open the full image.
		}

		var image = dialog.querySelector( '.lightbox__image' );
		var caption = dialog.querySelector( '.lightbox__caption' );
		var counter = dialog.querySelector( '.lightbox__counter' );
		var closeButton = dialog.querySelector( '[data-action="close"]' );
		var current = 0;
		var opener = null;

		function show( index ) {
			current = ( index + links.length ) % links.length;
			var link = links[ current ];
			var thumb = link.querySelector( 'img' );
			var figcaption = link.closest( 'figure' ).querySelector( 'figcaption' );
			image.src = link.getAttribute( 'href' );
			image.alt = thumb ? thumb.alt : '';
			caption.textContent = figcaption ? figcaption.textContent : '';
			counter.textContent = 'Screenshot ' + ( current + 1 ) + ' of ' + links.length;
		}

		links.forEach( function ( link, index ) {
			link.addEventListener( 'click', function ( event ) {
				if ( event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0 ) {
					return; // Let the browser open the image in a new tab or window.
				}
				event.preventDefault();
				opener = link;
				show( index );
				dialog.showModal();
				closeButton.focus();
			} );
		} );

		dialog.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( 'button[data-action]' );
			if ( button ) {
				var action = button.getAttribute( 'data-action' );
				if ( action === 'prev' ) {
					show( current - 1 );
				} else if ( action === 'next' ) {
					show( current + 1 );
				} else {
					dialog.close();
				}
				return;
			}
			if ( event.target === dialog ) {
				dialog.close(); // Click on the backdrop.
			}
		} );

		dialog.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'ArrowLeft' ) {
				event.preventDefault();
				show( current - 1 );
			} else if ( event.key === 'ArrowRight' ) {
				event.preventDefault();
				show( current + 1 );
			}
		} );

		dialog.addEventListener( 'close', function () {
			if ( opener ) {
				opener.focus();
			}
		} );
	}

	/* Copy code button ------------------------------------------------------ */

	function initCopyButtons() {
		var status = document.getElementById( 'copy-status' );
		var buttons = document.querySelectorAll( '.copy-button' );

		Array.prototype.forEach.call( buttons, function ( button ) {
			var target = document.getElementById( button.getAttribute( 'data-copy-target' ) );
			if ( ! target || ! navigator.clipboard ) {
				return;
			}

			button.hidden = false;
			button.addEventListener( 'click', function () {
				navigator.clipboard.writeText( target.textContent ).then(
					function () {
						button.textContent = 'Copied';
						if ( status ) {
							status.textContent = 'Code copied to the clipboard.';
						}
						window.setTimeout( function () {
							button.textContent = 'Copy code';
							if ( status ) {
								status.textContent = '';
							}
						}, 2500 );
					},
					function () {
						if ( status ) {
							status.textContent = 'Copying failed. Select the code and copy it manually.';
						}
					}
				);
			} );
		} );
	}

	function init() {
		initThemeToggle();
		initLightbox();
		initCopyButtons();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
