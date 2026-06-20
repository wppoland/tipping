<?php

declare(strict_types=1);

namespace Tipping\Frontend;

use Tipping\Contract\HasHooks;
use Tipping\Service\Recipients;
use Tipping\Service\TipSelection;
use Tipping\Settings\Options;

defined('ABSPATH') || exit;

/**
 * Renders the customer-facing tip control on the checkout, and enqueues the
 * stylesheet + the small progressive-enhancement script that drives live
 * updates via WooCommerce's checkout AJAX (`update_checkout`).
 *
 * The markup degrades gracefully: without JS the presets still post, and an
 * empty / disabled / misconfigured state renders nothing at all.
 */
final class TipControl implements HasHooks
{
    public function __construct(
        private readonly Options $options,
        private readonly TipSelection $selection,
        private readonly Recipients $recipients,
    ) {
    }

    public function registerHooks(): void
    {
        add_action('woocommerce_review_order_before_payment', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Load assets only on the checkout, only when the control is usable.
     */
    public function enqueueAssets(): void
    {
        if (! $this->options->isUsable()) {
            return;
        }

        if (! function_exists('is_checkout') || ! is_checkout()) {
            return;
        }

        wp_enqueue_style(
            'tipping',
            \TIPPING_URL . 'assets/css/tipping.css',
            [],
            \Tipping\VERSION,
        );

        wp_enqueue_script(
            'tipping',
            \TIPPING_URL . 'assets/js/tipping.js',
            ['jquery'],
            \Tipping\VERSION,
            ['in_footer' => true, 'strategy' => 'defer'],
        );

        wp_localize_script('tipping', 'TippingData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('tipping_set'),
        ]);
    }

    /**
     * Render the control. Guards every exit so a misconfigured or disabled state
     * shows nothing rather than a broken widget.
     */
    public function render(): void
    {
        if (! $this->options->isUsable()) {
            return;
        }

        $presets = $this->options->presets();
        $current = $this->selection->current();

        $template = \TIPPING_DIR . 'templates/tip-control.php';

        if (! is_readable($template)) {
            return;
        }

        $context = [
            'options'     => $this->options,
            'presets'     => $presets,
            'current'     => $current,
            'recipients'  => $this->recipients->all(),
            'isPercent'   => $this->options->isPercent(),
            'label'       => $this->options->label(),
            'description' => $this->options->description(),
        ];

        $this->renderTemplate($template, $context);
    }

    /**
     * Format a preset value for display: "10%" for percentages, a currency
     * amount for fixed presets.
     */
    public static function formatPreset(float $value, bool $isPercent): string
    {
        if ($isPercent) {
            /* translators: %s: a whole-number percentage, e.g. 10. */
            return sprintf(__('%s%%', 'tipping'), (string) (int) round($value));
        }

        return wp_strip_all_tags(wc_price($value));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function renderTemplate(string $file, array $context): void
    {
        extract($context, EXTR_SKIP);
        require $file;
    }
}
