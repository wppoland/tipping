/**
 * Tipping — admin settings progressive enhancement.
 *
 * Provides a fallback for the native Popover API so the inline-help tooltips work
 * in browsers that do not support `popover`. Where Popover is supported, the
 * markup works on its own and this script does nothing.
 */
( function () {
	'use strict';

	var supportsPopover =
		typeof HTMLElement !== 'undefined' &&
		HTMLElement.prototype &&
		Object.prototype.hasOwnProperty.call( HTMLElement.prototype, 'popover' );

	if ( supportsPopover ) {
		return;
	}

	function closeAll() {
		document.querySelectorAll( '.tipping-tip--fallback' ).forEach( function ( tip ) {
			tip.hidden = true;
			tip.classList.remove( 'tipping-tip--fallback' );
		} );
		document.querySelectorAll( '.tipping-help[aria-expanded="true"]' ).forEach( function ( btn ) {
			btn.setAttribute( 'aria-expanded', 'false' );
		} );
	}

	function init() {
		document.querySelectorAll( '.tipping-help' ).forEach( function ( btn ) {
			var id = btn.getAttribute( 'popovertarget' );
			var tip = id ? document.getElementById( id ) : null;
			if ( ! tip ) {
				return;
			}

			btn.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				var open = btn.getAttribute( 'aria-expanded' ) === 'true';
				closeAll();
				if ( ! open ) {
					tip.hidden = false;
					tip.classList.add( 'tipping-tip--fallback' );
					var rect = btn.getBoundingClientRect();
					tip.style.left = ( rect.left + window.scrollX ) + 'px';
					tip.style.top = ( rect.bottom + window.scrollY + 6 ) + 'px';
					btn.setAttribute( 'aria-expanded', 'true' );
				}
			} );
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' ) {
				closeAll();
			}
		} );

		document.addEventListener( 'click', function ( event ) {
			if ( ! event.target.closest( '.tipping-help' ) && ! event.target.closest( '.tipping-tip' ) ) {
				closeAll();
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
