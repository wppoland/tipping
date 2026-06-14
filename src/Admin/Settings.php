<?php

declare(strict_types=1);

namespace Tipping\Admin;

defined('ABSPATH') || exit;

use Tipping\Contract\HasHooks;
use Tipping\Frontend\TipControl;
use Tipping\Settings\Options;

/**
 * Admin settings page registered under the WooCommerce submenu.
 *
 * Stores everything in the `tipping_settings` option (array): the master toggle,
 * customer-facing label/description, preset type (percentage vs fixed), preset
 * values, default selection, custom-amount toggle, placement (cart/checkout) and
 * taxability. All output is escaped; all input is sanitised and clamped on save.
 */
final class Settings implements HasHooks
{
    private const PAGE = 'tipping-settings';

    /** Incremented to give each inline-help popover a unique id. */
    private int $helpSeq = 0;

    public function __construct(private readonly Options $options)
    {
    }

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'woocommerce_page_' . self::PAGE) {
            return;
        }

        wp_enqueue_style(
            'tipping-admin',
            \TIPPING_URL . 'assets/css/admin.css',
            [],
            \Tipping\VERSION,
        );

        wp_enqueue_script(
            'tipping-admin',
            \TIPPING_URL . 'assets/js/admin.js',
            [],
            \Tipping\VERSION,
            ['in_footer' => true, 'strategy' => 'defer'],
        );
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Tipping', 'tipping'),
            __('Tipping', 'tipping'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::PAGE,
            Options::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
            ],
        );

        // The menu uses manage_woocommerce; align the options.php save capability.
        add_filter(
            'option_page_capability_' . self::PAGE,
            static fn (): string => 'manage_woocommerce',
        );
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $settings = $this->options->all();
        $type     = $this->options->type();
        $presets  = $this->options->presets();
        ?>
        <div class="wrap tipping-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="tipping-admin__intro">
                <h2><?php esc_html_e('Let customers add a tip or donation at checkout', 'tipping'); ?></h2>
                <p>
                    <?php esc_html_e('A friendly, optional tip control on the cart and checkout. Pick preset amounts (fixed or a percentage of the order), optionally allow a custom amount, and the tip is added to the order totals as a fee. Updates live as the customer chooses.', 'tipping'); ?>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <div class="tipping-admin__card">
                    <h2><?php esc_html_e('General', 'tipping'); ?></h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Enable tipping', 'tipping'); ?>
                                    <?php $this->help(__('The master switch. When off, the control never renders and no assets load on the storefront.', 'tipping')); ?>
                                </th>
                                <td>
                                    <label for="tipping_enabled">
                                        <input type="checkbox" id="tipping_enabled" name="<?php echo esc_attr(Options::OPTION); ?>[enabled]" value="1" <?php checked($this->options->isEnabled(), true); ?> />
                                        <?php esc_html_e('Show the tip control on the storefront.', 'tipping'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="tipping_label"><?php esc_html_e('Label', 'tipping'); ?></label>
                                    <?php $this->help(__('The heading shown above the tip buttons, e.g. “Add a tip” or “Support our shelter”.', 'tipping')); ?>
                                </th>
                                <td>
                                    <input type="text" id="tipping_label" name="<?php echo esc_attr(Options::OPTION); ?>[label]" value="<?php echo esc_attr((string) ($settings['label'] ?? '')); ?>" class="regular-text" placeholder="<?php esc_attr_e('Add a tip', 'tipping'); ?>" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="tipping_description"><?php esc_html_e('Description', 'tipping'); ?></label>
                                    <?php $this->help(__('Optional supporting text shown under the label. Keep it short and reassuring — tipping is always optional.', 'tipping')); ?>
                                </th>
                                <td>
                                    <textarea id="tipping_description" name="<?php echo esc_attr(Options::OPTION); ?>[description]" rows="2" class="large-text"><?php echo esc_textarea((string) ($settings['description'] ?? '')); ?></textarea>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="tipping-admin__card">
                    <h2><?php esc_html_e('Presets', 'tipping'); ?></h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="tipping_type"><?php esc_html_e('Preset type', 'tipping'); ?></label>
                                    <?php $this->help(__('Percentage presets scale with the order total (5% of the subtotal). Fixed presets are flat amounts in your store currency.', 'tipping')); ?>
                                </th>
                                <td>
                                    <select id="tipping_type" name="<?php echo esc_attr(Options::OPTION); ?>[type]">
                                        <option value="percent" <?php selected($type, 'percent'); ?>><?php esc_html_e('Percentage of cart', 'tipping'); ?></option>
                                        <option value="fixed" <?php selected($type, 'fixed'); ?>><?php esc_html_e('Fixed amount', 'tipping'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="tipping_presets"><?php esc_html_e('Preset values', 'tipping'); ?></label>
                                    <?php $this->help(__('Comma-separated values. For percentages use whole numbers (5, 10, 15). For fixed amounts use currency values (2, 5, 10). Blank or non-positive values are dropped.', 'tipping')); ?>
                                </th>
                                <td>
                                    <input type="text" id="tipping_presets" name="<?php echo esc_attr(Options::OPTION); ?>[presets]" value="<?php echo esc_attr(implode(', ', array_map([$this, 'formatNumber'], $presets))); ?>" class="regular-text" placeholder="5, 10, 15" />
                                    <p class="description"><?php esc_html_e('Up to a handful of values keeps the control tidy.', 'tipping'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="tipping_default_selection"><?php esc_html_e('Default selection', 'tipping'); ?></label>
                                    <?php $this->help(__('Which option is pre-selected before the customer chooses. “No tip” keeps tipping fully opt-in (recommended).', 'tipping')); ?>
                                </th>
                                <td>
                                    <select id="tipping_default_selection" name="<?php echo esc_attr(Options::OPTION); ?>[default_selection]">
                                        <option value="none" <?php selected($this->options->defaultSelectionIndex(), null); ?>><?php esc_html_e('No tip', 'tipping'); ?></option>
                                        <?php foreach ($presets as $tipping_i => $tipping_v) : ?>
                                            <option value="<?php echo esc_attr((string) (int) $tipping_i); ?>" <?php selected($this->options->defaultSelectionIndex(), (int) $tipping_i); ?>>
                                                <?php echo esc_html(TipControl::formatPreset((float) $tipping_v, $this->options->isPercent())); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Custom amount', 'tipping'); ?>
                                    <?php $this->help(__('Adds a “Custom” button that reveals a free-form amount field, so generous customers can give more than the presets.', 'tipping')); ?>
                                </th>
                                <td>
                                    <label for="tipping_allow_custom">
                                        <input type="checkbox" id="tipping_allow_custom" name="<?php echo esc_attr(Options::OPTION); ?>[allow_custom]" value="1" <?php checked($this->options->allowCustom(), true); ?> />
                                        <?php esc_html_e('Allow customers to enter a custom tip amount.', 'tipping'); ?>
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="tipping-admin__card">
                    <h2><?php esc_html_e('Placement & tax', 'tipping'); ?></h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Show on', 'tipping'); ?>
                                    <?php $this->help(__('Where the control appears. The checkout placement updates live via WooCommerce’s checkout AJAX as the customer chooses.', 'tipping')); ?>
                                </th>
                                <td>
                                    <label for="tipping_show_on_cart">
                                        <input type="checkbox" id="tipping_show_on_cart" name="<?php echo esc_attr(Options::OPTION); ?>[show_on_cart]" value="1" <?php checked($this->options->showOnCart(), true); ?> />
                                        <?php esc_html_e('Cart page', 'tipping'); ?>
                                    </label>
                                    <br />
                                    <label for="tipping_show_on_checkout">
                                        <input type="checkbox" id="tipping_show_on_checkout" name="<?php echo esc_attr(Options::OPTION); ?>[show_on_checkout]" value="1" <?php checked($this->options->showOnCheckout(), true); ?> />
                                        <?php esc_html_e('Checkout page', 'tipping'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Taxable', 'tipping'); ?>
                                    <?php $this->help(__('Most tips and donations are not taxable. Leave this off unless your jurisdiction requires tax on tips.', 'tipping')); ?>
                                </th>
                                <td>
                                    <label for="tipping_taxable">
                                        <input type="checkbox" id="tipping_taxable" name="<?php echo esc_attr(Options::OPTION); ?>[taxable]" value="1" <?php checked($this->options->isTaxable(), true); ?> />
                                        <?php esc_html_e('Apply tax to the tip.', 'tipping'); ?>
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php
                /**
                 * Fires inside the Tipping settings form, after the core cards and
                 * before the submit button. PRO add-ons render extra sections here;
                 * register settings against the 'tipping-settings' group so they
                 * save with the same form.
                 *
                 * @param array<string, mixed> $settings The current settings.
                 */
                do_action('tipping/admin_after_cards', $settings);
                ?>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * The settings group / page slug, exposed so PRO add-ons can register their
     * own settings against the same options.php form.
     */
    public static function pageSlug(): string
    {
        return self::PAGE;
    }

    /**
     * Sanitises, validates and clamps the submitted settings before save.
     *
     * @param mixed $raw
     * @return array<string, mixed>
     */
    public function sanitize(mixed $raw): array
    {
        if (! is_array($raw)) {
            $raw = [];
        }

        $type = isset($raw['type']) ? sanitize_key((string) $raw['type']) : 'percent';
        if (! in_array($type, Options::TYPES, true)) {
            $type = 'percent';
        }

        $presets = $this->parsePresets($raw['presets'] ?? '');

        $defaultSelection = 'none';
        if (isset($raw['default_selection']) && 'none' !== $raw['default_selection']) {
            $index = (int) $raw['default_selection'];
            if (isset($presets[$index])) {
                $defaultSelection = (string) $index;
            }
        }

        $sanitized = [
            'enabled'           => ! empty($raw['enabled']),
            'label'             => isset($raw['label']) ? sanitize_text_field((string) $raw['label']) : '',
            'description'       => isset($raw['description']) ? sanitize_textarea_field((string) $raw['description']) : '',
            'type'              => $type,
            'presets'           => $presets,
            'default_selection' => $defaultSelection,
            'allow_custom'      => ! empty($raw['allow_custom']),
            'show_on_cart'      => ! empty($raw['show_on_cart']),
            'show_on_checkout'  => ! empty($raw['show_on_checkout']),
            'taxable'           => ! empty($raw['taxable']),
        ];

        $this->options->flush();

        return $sanitized;
    }

    /**
     * Parse a comma-separated preset string into a clean list of positive
     * numbers, capped to keep the control usable.
     *
     * @param mixed $raw
     * @return list<float>
     */
    private function parsePresets(mixed $raw): array
    {
        if (is_array($raw)) {
            $parts = $raw;
        } else {
            $parts = explode(',', (string) $raw);
        }

        $out = [];
        foreach ($parts as $part) {
            $value = (float) wc_format_decimal((string) $part);
            if ($value > 0) {
                $out[] = round($value, 2);
            }
            if (count($out) >= 8) {
                break;
            }
        }

        return array_values($out);
    }

    /**
     * Format a stored number without trailing zeros for the admin text field.
     */
    public function formatNumber(float $value): string
    {
        if (floor($value) === $value) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * Accessible inline-help popover: a "?" button that toggles a tooltip
     * describing the adjacent setting. Uses the native Popover API and is wired
     * via aria-describedby for screen readers.
     */
    private function help(string $text): void
    {
        $id = 'tipping-help-' . (++$this->helpSeq);
        ?>
        <button
            type="button"
            class="tipping-help"
            aria-label="<?php esc_attr_e('More information', 'tipping'); ?>"
            aria-describedby="<?php echo esc_attr($id); ?>"
            aria-expanded="false"
            popovertarget="<?php echo esc_attr($id); ?>"
        >?</button>
        <div id="<?php echo esc_attr($id); ?>" class="tipping-tip" role="tooltip" popover hidden>
            <?php echo esc_html($text); ?>
        </div>
        <?php
    }
}
