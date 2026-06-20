/**
 * Tipping — front-end behaviour.
 *
 * Sends the customer's tip choice to the server, then asks WooCommerce to
 * recalculate totals so the fee appears live. Progressive enhancement: if this
 * script does not run, the presets still post on the next checkout refresh via
 * the server-side selection persistence.
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

	function currentRecipient( root ) {
		var select = root.querySelector( '[data-tipping-recipient]' );
		if ( ! select ) {
			return '';
		}
		return select.value || '';
	}

	function withRecipient( root, params ) {
		var recipient = currentRecipient( root );
		if ( recipient ) {
			params.recipient = recipient;
		}
		return params;
	}

	function init( root ) {
		var recipientSelect = root.querySelector( '[data-tipping-recipient]' );
		if ( recipientSelect ) {
			recipientSelect.addEventListener( 'change', function () {
				var active = root.querySelector( '.tipping__option.is-active' );
				var mode = active ? active.getAttribute( 'data-tipping-mode' ) : 'none';
				if ( mode === 'preset' ) {
					send(
						root,
						withRecipient( root, {
							mode: 'preset',
							preset: active.getAttribute( 'data-tipping-preset' ),
						} )
					);
				} else {
					send( root, withRecipient( root, { mode: 'none' } ) );
				}
			} );
		}

		root.querySelectorAll( '.tipping__option' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var mode = btn.getAttribute( 'data-tipping-mode' );

				setActive( root, btn );

				if ( mode === 'preset' ) {
					send(
						root,
						withRecipient( root, {
							mode: 'preset',
							preset: btn.getAttribute( 'data-tipping-preset' ),
						} )
					);
				} else {
					send( root, withRecipient( root, { mode: 'none' } ) );
				}
			} );
		} );
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
		window.jQuery( document.body ).on( 'updated_checkout', boot );
	}
}() );
