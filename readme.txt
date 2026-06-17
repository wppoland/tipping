=== Tipping - Checkout Tips and Donations for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, tips, donations, checkout, gratuity
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Requires Plugins: woocommerce
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let customers add an optional tip or donation at checkout — preset amounts, applied to the order total and updated live.

== Description ==

Tipping adds an optional tip or donation control to the WooCommerce checkout.
Customers pick a preset amount, either a flat figure or a percentage of their
order, and the tip is added to the order totals as a fee and saved on the order.

Choosing an amount recalculates the totals through WooCommerce's own checkout
AJAX, so the figure customers see before they pay always includes the tip.
Percentage presets are worked out from the current subtotal, so they stay
correct if the cart changes.

Everything lives on one screen under **WooCommerce → Tipping**: the label and
description shoppers see, whether presets are fixed amounts or percentages, and
the preset values themselves.

The code is on GitHub at https://github.com/wppoland/tipping if you want to read
it, report a bug or suggest a preset workflow we have missed.

= Features =

* Preset tip amounts: fixed currency values or a percentage of the cart.
* Applied as a native WooCommerce cart fee, so it shows in totals and on the order.
* Recalculates as soon as the customer picks an amount, via WooCommerce checkout AJAX.
* Editable label and description.
* Opt-in by default: "No tip" is preselected, and tips are added as a non-taxable fee.
* Renders nothing when tipping is disabled or no presets are set, so the checkout is never cluttered with an empty control.
* Buttons are keyboard operable, with a visible focus ring, an ARIA live status line and reduced-motion handling.
* Ships with a POT file for translation and removes its option on uninstall.
* Declares HPOS compatibility. The control renders on the classic (shortcode) checkout.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/tipping`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be installed and active.
3. Go to **WooCommerce → Tipping**, enable tipping and set your presets.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. WooCommerce must be installed and active.

= Are tips taxed? =

No. Tips and donations are added as a non-taxable fee.

= How does a percentage tip work? =

A percentage preset is calculated from the cart subtotal at the moment of
selection and recalculated whenever the cart changes, so it always reflects the
current order.

= Where is the tip stored? =

The tip is added to the order as a standard WooCommerce fee, so it appears in the
order totals, emails and reports. The amount is also saved as order meta for
auditing.

= Is tipping optional for customers? =

Yes. The default selection is "No tip", keeping it fully opt-in.

== Screenshots ==

1. The tip control on the checkout page.
2. The Tipping settings screen under WooCommerce.

== External Services ==

Tipping does not connect to, send data to or load resources from any external service. It runs entirely on your own site.

The customer's tip choice is posted to WordPress's own `admin-ajax.php` on the same origin, then WooCommerce recalculates the checkout totals; no third party is involved. Settings are kept in the `tipping_settings` option (with `tipping_db_version` tracking the schema), and each tip is recorded both as a native WooCommerce cart fee and as the `_tipping_amount` order meta. The plugin does not send any email of its own.

== Changelog ==

= 0.1.0 =
* Initial release: preset tips/donations on the checkout, applied as a WooCommerce cart fee with live totals, plus a WooCommerce settings screen.
