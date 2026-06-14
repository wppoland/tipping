# Tipping - Checkout Tips and Donations for WooCommerce

Let customers add an optional tip or donation at the WooCommerce cart and
checkout: preset amounts (fixed or a percentage of the cart) plus an optional
custom amount, applied as a native cart fee and shown in the totals and on the
order. Updates live on selection via WooCommerce checkout AJAX.

Self-contained — no external runtime dependencies.

## Configuration

Everything lives under **WooCommerce → Tipping**:

- Master on/off toggle.
- Customer-facing label and description.
- Preset type: percentage of the cart, or fixed amounts.
- Preset values, default selection and an optional custom-amount field.
- Placement: cart page and/or checkout page.
- Taxable toggle (off by default).

## Development

```bash
composer install
composer cs        # PHP_CodeSniffer (WPCS security ruleset)
composer analyse   # PHPStan level 6
```

The wp.org build excludes dev tooling (see `.distignore`); the bundled fallback
PSR-4 autoloader (`autoload.php`) means the plugin runs without `vendor/`.

## Pro

A premium add-on (`tipping-pro`) extends this plugin via the `tipping/booted`
action and the `tipping/fee_amount` filter — for example, round-up tipping.

## License

GPL-2.0-or-later.
