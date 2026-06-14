<?php
/**
 * Default settings, merged under the option key `tipping_settings`.
 *
 * The plugin ships enabled with three percentage presets on the checkout.
 * Merchants tune the label, the preset type (fixed amounts or a percentage of
 * the cart) and the preset values from the Tipping admin screen.
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
];
