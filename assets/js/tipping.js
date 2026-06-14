/**
 * Tipping — front-end behaviour.
 *
 * Sends the customer's tip choice to the server, then asks WooCommerce to
 * recalculate totals so the fee appears live. Progressive enhancement: if this
 * script does not run, the presets still post on the next cart/checkout refresh
 * via the server-side default and selection persistence.
 */
( function () {
	'use strict';

	if ( typeof window.TippingData === 'undefined' ) {
		return;
	}

	var data = window.TippingData;

	function post( params ) {
		var body = new URLSearchParams();
		body.append( 'action', 'tipping_set' );
		body.append( 'nonce', data.nonce );
		Object.keys( params ).forEach( function ( key ) {
			body.append( key, params[ key ] );
		} );

		return window.fetch( data.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
		} );
	}

	function refreshTotals() {
		var $ = window.jQuery;
		if ( ! $ ) {
			return;
		}
		// Checkout: triggers the standard update_checkout AJAX.
		$( document.body ).trigger( 'update_checkout' );
		// Cart page: triggers the cart totals refresh.
		$( document.body ).trigger( 'wc_update_cart' );
	}

	function setActive( root, target ) {
		root.querySelectorAll( '.tipping__option' ).forEach( function ( btn ) {
			var active = btn === target;
			btn.classList.toggle( 'is-active', active );
			btn.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
		} );
	}

	function status( root, message ) {
		var el = root.querySelector( '.tipping__status' );
		if ( el ) {
			el.textContent = message || '';
		}
	}

	function send( root, params ) {
		root.classList.add( 'is-busy' );
		post( params )
			.then( function () {
				refreshTotals();
			} )
			.catch( function () {
				status( root, '' );
			} )
			.finally( function () {
				root.classList.remove( 'is-busy' );
			} );
	}

	function init( root ) {
		var customField = root.querySelector( '.tipping__custom' );
		var customInput = root.querySelector( '.tipping__custom-input' );

		root.querySelectorAll( '.tipping__option' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var mode = btn.getAttribute( 'data-tipping-mode' );

				if ( mode === 'custom-toggle' ) {
					setActive( root, btn );
					btn.setAttribute( 'aria-expanded', 'true' );
					if ( customField ) {
						customField.hidden = false;
					}
					if ( customInput ) {
						customInput.focus();
					}
					if ( customInput && customInput.value ) {
						send( root, { mode: 'custom', amount: customInput.value } );
					}
					return;
				}

				if ( customField ) {
					customField.hidden = true;
				}
				var toggle = root.querySelector( '.tipping__option--custom' );
				if ( toggle ) {
					toggle.setAttribute( 'aria-expanded', 'false' );
				}

				setActive( root, btn );

				if ( mode === 'preset' ) {
					send( root, { mode: 'preset', preset: btn.getAttribute( 'data-tipping-preset' ) } );
				} else {
					send( root, { mode: 'none' } );
				}
			} );
		} );

		if ( customInput ) {
			var debounce;
			customInput.addEventListener( 'input', function () {
				window.clearTimeout( debounce );
				debounce = window.setTimeout( function () {
					send( root, { mode: 'custom', amount: customInput.value } );
				}, 500 );
			} );
		}
	}

	function boot() {
		document.querySelectorAll( '.tipping' ).forEach( init );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	// Re-bind after WooCommerce re-renders the checkout review block.
	if ( window.jQuery ) {
		window.jQuery( document.body ).on( 'updated_checkout updated_cart_totals', boot );
	}
}() );
