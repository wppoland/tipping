<?php
/**
 * Default settings, merged under the option key `tipping_settings`.
 *
 * The plugin ships enabled with three percentage presets and a custom-amount
 * field, shown on both cart and checkout. Merchants tune the label, the preset
 * type (fixed amounts or a percentage of the cart), the preset values, the
 * default selection and where the control appears from the Tipping admin screen.
 *
 * @package Tipping
 *
 * @return array<string, mixed>
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return [
    // Master switch.
    'enabled' => true,

    // Customer-facing copy.
    'label'       => 'Add a tip',
    'description' => 'Support our team — every tip is appreciated. Choose an amount or skip.',

    // Preset type: 'percent' (of the cart subtotal) or 'fixed' (currency amounts).
    'type' => 'percent',

    // Preset values. For 'percent' these are whole percentages (e.g. 5 = 5%);
    // for 'fixed' they are amounts in the store currency.
    'presets' => [5, 10, 15],

    // Default selected preset (the array index, 0-based) or 'none' for none.
    'default_selection' => 'none',

    // Allow a free-form custom amount alongside the presets.
    'allow_custom' => true,

    // Where the control renders.
    'show_on_cart'     => true,
    'show_on_checkout' => true,

    // Whether the fee is taxable (most tips/donations are not).
    'taxable' => false,
];
