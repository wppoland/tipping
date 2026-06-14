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

Tipping adds a friendly, optional tip or donation control to your WooCommerce
checkout. Customers pick a preset amount — a flat figure or a percentage of
their order — and the tip is added to the order totals as a fee and recorded on
the order.

The control updates live: choosing an amount recalculates the totals through
WooCommerce's own checkout AJAX, so customers always see the up-to-date total
before they pay. Percentage tips track the live subtotal automatically.

Everything is configured from a single screen under **WooCommerce → Tipping**:
the label and description shoppers see, whether presets are fixed amounts or
percentages, and the preset values.

= Features =

* Preset tip amounts — fixed currency values or a percentage of the cart.
* Applied as a native WooCommerce cart fee, shown in totals and on the order.
* Live updates on selection via WooCommerce checkout AJAX.
* Configurable label and description.
* Fully opt-in: the default selection is "No tip", and tips are never taxed.
* Graceful: renders nothing when disabled or misconfigured.
* Accessible: keyboard friendly, focus-visible, ARIA live status, reduced-motion aware.
* Translation ready (POT included) and clean uninstall.
* HPOS and cart/checkout blocks compatible.

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

== Changelog ==

= 0.1.0 =
* Initial release: preset tips/donations on the checkout, applied as a WooCommerce cart fee with live totals, plus a WooCommerce settings screen.
