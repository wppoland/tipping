<?php

declare(strict_types=1);

namespace Tipping\Service;

use Tipping\Settings\Options;

defined('ABSPATH') || exit;

/**
 * Reads, validates and persists the customer's current tip choice in the
 * WooCommerce session, and resolves it to a concrete fee amount for the cart.
 *
 * A selection is one of:
 *  - a preset index (0-based) into {@see Options::presets()};
 *  - a custom amount (when custom amounts are allowed);
 *  - none (no tip).
 *
 * The resolved amount is always recomputed from the current cart, so a
 * percentage tip tracks the live subtotal and never goes stale.
 */
final class TipSelection
{
    private const SESSION_KEY = 'tipping_choice';

    public function __construct(private readonly Options $options)
    {
    }

    /**
     * Persist a raw choice (from the AJAX handler) into the session, after
     * validating it against the current settings. Unknown / disabled choices
     * collapse to "no tip".
     *
     * @param array{mode?: string, preset?: int|string, amount?: int|float|string} $choice
     */
    public function store(array $choice): void
    {
        if (! $this->sessionAvailable()) {
            return;
        }

        $clean = $this->normalize($choice);

        WC()->session->set(self::SESSION_KEY, $clean);
    }

    /**
     * The stored choice, or the configured default when nothing is set yet.
     *
     * @return array{mode: string, preset: int, amount: float}
     */
    public function current(): array
    {
        if ($this->sessionAvailable()) {
            $stored = WC()->session->get(self::SESSION_KEY);
            if (is_array($stored)) {
                return $this->normalize($stored);
            }
        }

        $default = $this->options->defaultSelectionIndex();

        if (null !== $default) {
            return ['mode' => 'preset', 'preset' => $default, 'amount' => 0.0];
        }

        return ['mode' => 'none', 'preset' => 0, 'amount' => 0.0];
    }

    /**
     * Resolve the current choice to a concrete, non-negative fee amount in the
     * store currency, based on the live cart subtotal for percentage tips.
     */
    public function resolveAmount(): float
    {
        return $this->filterAmount($this->computeAmount());
    }

    /**
     * The raw resolved amount before any extension adjusts it.
     */
    private function computeAmount(): float
    {
        if (! $this->options->isUsable()) {
            return 0.0;
        }

        $choice  = $this->current();
        $presets = $this->options->presets();

        if ('preset' === $choice['mode']) {
            if (! isset($presets[$choice['preset']])) {
                return 0.0;
            }

            $value = $presets[$choice['preset']];

            if ($this->options->isPercent()) {
                return $this->roundAmount($this->cartBase() * ($value / 100));
            }

            return $this->roundAmount($value);
        }

        if ('custom' === $choice['mode'] && $this->options->allowCustom()) {
            return $this->roundAmount($choice['amount']);
        }

        return 0.0;
    }

    /**
     * Let extensions adjust the resolved tip amount (e.g. PRO round-up). The
     * result is clamped to a non-negative, currency-rounded value.
     *
     * @param float $amount The computed amount.
     */
    private function filterAmount(float $amount): float
    {
        /**
         * Filters the resolved tip amount before it is applied as a cart fee.
         *
         * @param float        $amount  The computed tip amount.
         * @param TipSelection $service The selection service.
         */
        $filtered = (float) apply_filters('tipping/fee_amount', $amount, $this);

        return $this->roundAmount(max(0.0, $filtered));
    }

    /**
     * The cart base (pre-tip subtotal), exposed for extensions that need it.
     */
    public function cartSubtotal(): float
    {
        return $this->cartBase();
    }

    /**
     * Normalise a raw choice array into the canonical shape, clamping values and
     * rejecting anything that does not match the current settings.
     *
     * @param array<string, mixed> $choice
     * @return array{mode: string, preset: int, amount: float}
     */
    private function normalize(array $choice): array
    {
        $mode = isset($choice['mode']) ? sanitize_key((string) $choice['mode']) : 'none';

        if ('preset' === $mode) {
            $preset  = isset($choice['preset']) ? (int) $choice['preset'] : -1;
            $presets = $this->options->presets();

            if ($preset >= 0 && isset($presets[$preset])) {
                return ['mode' => 'preset', 'preset' => $preset, 'amount' => 0.0];
            }

            return ['mode' => 'none', 'preset' => 0, 'amount' => 0.0];
        }

        if ('custom' === $mode && $this->options->allowCustom()) {
            $amount = isset($choice['amount']) ? (float) wc_format_decimal((string) $choice['amount']) : 0.0;
            $amount = max(0.0, $amount);

            if ($amount <= 0.0) {
                return ['mode' => 'none', 'preset' => 0, 'amount' => 0.0];
            }

            return ['mode' => 'custom', 'preset' => 0, 'amount' => $amount];
        }

        return ['mode' => 'none', 'preset' => 0, 'amount' => 0.0];
    }

    /**
     * The cart base used for percentage tips: the cart contents total plus any
     * discounts already applied (i.e. the pre-tip subtotal). Falls back to 0.
     */
    private function cartBase(): float
    {
        $cart = WC()->cart;

        if (! $cart instanceof \WC_Cart) {
            return 0.0;
        }

        // Subtotal of items (excluding the tip fee itself), tax-exclusive.
        return (float) $cart->get_subtotal();
    }

    private function roundAmount(float $amount): float
    {
        $decimals = function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2;

        return max(0.0, round($amount, $decimals));
    }

    private function sessionAvailable(): bool
    {
        return function_exists('WC') && WC() instanceof \WooCommerce && WC()->session instanceof \WC_Session;
    }
}
