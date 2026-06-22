<?php

declare(strict_types=1);

namespace Tipping\Admin;

defined('ABSPATH') || exit;

use Tipping\Contract\HasHooks;
use Tipping\Settings\Options;

/**
 * Admin settings page registered under the WooCommerce submenu.
 *
 * Stores everything in the `tipping_settings` option (array): the master toggle,
 * customer-facing label/description, preset type (percentage vs fixed) and preset
 * values. All output is escaped; all input is sanitised and clamped on save.
 */
final class Settings implements HasHooks
{
    private const PAGE = 'tipping-settings';

    public function __construct(private readonly Options $options)
    {
    }

    /**
     * The settings page / option-group slug.
     *
     * Exposed so PRO add-ons can register their own settings against the same
     * options.php group and render under the same WooCommerce submenu.
     */
    public static function pageSlug(): string
    {
        return self::PAGE;
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
                    <?php esc_html_e('A friendly, optional tip control on the checkout. Pick preset amounts (fixed or a percentage of the order) and the tip is added to the order totals as a fee. Updates live as the customer chooses.', 'tipping'); ?>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <div class="tipping-admin__card">
                    <h2><?php esc_html_e('General', 'tipping'); ?></h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e('Enable tipping', 'tipping'); ?></th>
                                <td>
                                    <label for="tipping_enabled">
                                        <input type="checkbox" id="tipping_enabled" name="<?php echo esc_attr(Options::OPTION); ?>[enabled]" value="1" <?php checked($this->options->isEnabled(), true); ?> />
                                        <?php esc_html_e('Show the tip control on the checkout.', 'tipping'); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('When off, the control never renders and no assets load on the storefront.', 'tipping'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="tipping_label"><?php esc_html_e('Label', 'tipping'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="tipping_label" name="<?php echo esc_attr(Options::OPTION); ?>[label]" value="<?php echo esc_attr((string) ($settings['label'] ?? '')); ?>" class="regular-text" placeholder="<?php esc_attr_e('Add a tip', 'tipping'); ?>" />
                                    <p class="description"><?php esc_html_e('The heading shown above the tip buttons, e.g. “Add a tip” or “Support our shelter”.', 'tipping'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="tipping_description"><?php esc_html_e('Description', 'tipping'); ?></label>
                                </th>
                                <td>
                                    <textarea id="tipping_description" name="<?php echo esc_attr(Options::OPTION); ?>[description]" rows="2" class="large-text"><?php echo esc_textarea((string) ($settings['description'] ?? '')); ?></textarea>
                                    <p class="description"><?php esc_html_e('Optional supporting text shown under the label. Tipping is always optional for the customer.', 'tipping'); ?></p>
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
                                </th>
                                <td>
                                    <select id="tipping_type" name="<?php echo esc_attr(Options::OPTION); ?>[type]">
                                        <option value="percent" <?php selected($type, 'percent'); ?>><?php esc_html_e('Percentage of cart', 'tipping'); ?></option>
                                        <option value="fixed" <?php selected($type, 'fixed'); ?>><?php esc_html_e('Fixed amount', 'tipping'); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e('Percentage presets scale with the cart: a 5 preset adds 5% of the subtotal, so a larger order means a larger tip. Fixed presets stay the same flat amount in your store currency whatever the cart total.', 'tipping'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="tipping_presets"><?php esc_html_e('Preset values', 'tipping'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="tipping_presets" name="<?php echo esc_attr(Options::OPTION); ?>[presets]" value="<?php echo esc_attr(implode(', ', array_map([$this, 'formatNumber'], $presets))); ?>" class="regular-text" placeholder="5, 10, 15" />
                                    <p class="description"><?php esc_html_e('Comma-separated values. For percentages use whole numbers (5, 10, 15); for fixed amounts use currency values (2, 5, 10). Up to eight are shown; the rest are ignored. Leave empty and the control is hidden until you add at least one.', 'tipping'); ?></p>
                                    <?php $this->renderPresetPreview($type, $presets); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php
                /**
                 * Fires inside the settings form, after the FREE setting cards and
                 * before the submit button. PRO add-ons hook here to render their
                 * own `.tipping-admin__card` sections, which save with this form.
                 */
                do_action('tipping/admin_after_cards');
                ?>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render a tiny, non-interactive preview of the preset pills as the customer
     * will see them — so the merchant can read the effect of their values at a
     * glance instead of imagining it. Presentation only; no settings are read or
     * written here beyond the values already passed in.
     *
     * @param string      $type    'percent' or 'fixed'.
     * @param list<float> $presets Cleaned preset values.
     */
    private function renderPresetPreview(string $type, array $presets): void
    {
        if ([] === $presets) {
            return;
        }

        ?>
        <ul class="tipping-admin__example" aria-label="<?php esc_attr_e('Preview of the tip buttons', 'tipping'); ?>">
            <?php foreach ($presets as $preset) : ?>
                <li class="tipping-admin__pill">
                    <?php
                    if ('percent' === $type) {
                        /* translators: %s: a whole-number percentage, e.g. 5. */
                        echo esc_html(sprintf(__('%s%%', 'tipping'), $this->formatNumber($preset)));
                    } else {
                        echo wp_kses_post(wc_price($preset));
                    }
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
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

        $sanitized = [
            'enabled'     => ! empty($raw['enabled']),
            'label'       => isset($raw['label']) ? sanitize_text_field((string) $raw['label']) : '',
            'description' => isset($raw['description']) ? sanitize_textarea_field((string) $raw['description']) : '',
            'type'        => $type,
            'presets'     => $this->parsePresets($raw['presets'] ?? ''),
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
}
