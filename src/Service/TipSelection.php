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
 *  - none (no tip).
 *
 * The resolved amount is always recomputed from the current cart, so a
 * percentage tip tracks the live subtotal and never goes stale.
 */
final class TipSelection
{
    private const SESSION_KEY = 'tipping_choice';

    public function __construct(
        private readonly Options $options,
        private readonly Recipients $recipients,
    ) {
    }

    /**
     * Persist a raw choice (from the AJAX handler) into the session, after
     * validating it against the current settings. Unknown / disabled choices
     * collapse to "no tip".
     *
     * @param array{mode?: string, preset?: int|string, recipient?: string} $choice
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
     * The stored choice, defaulting to "no tip" so tipping is fully opt-in.
     *
     * @return array{mode: string, preset: int, recipient: string}
     */
    public function current(): array
    {
        if ($this->sessionAvailable()) {
            $stored = WC()->session->get(self::SESSION_KEY);
            if (is_array($stored)) {
                return $this->normalize($stored);
            }
        }

        return ['mode' => 'none', 'preset' => 0, 'recipient' => ''];
    }

    public function resolveAmount(): float
    {
        return $this->resolveAmountForChoice($this->current(), $this->cartBase());
    }

    /**
     * Resolve a validated choice against an explicit subtotal base (cart or order).
     *
     * @param array{mode?: string, preset?: int|string, recipient?: string} $choice
     */
    public function resolveAmountForChoice(array $choice, float $base): float
    {
        if (! $this->options->isUsable()) {
            return 0.0;
        }

        $choice  = $this->normalize($choice);
        $presets = $this->options->presets();
        $amount  = 0.0;

        if ('preset' === $choice['mode'] && isset($presets[$choice['preset']])) {
            $value = $presets[$choice['preset']];

            if ($this->options->isPercent()) {
                $amount = $this->roundAmount($base * ($value / 100));
            } else {
                $amount = $this->roundAmount($value);
            }
        }

        /**
         * Filters the resolved tip amount before it is applied as a fee.
         *
         * @param float        $amount    The resolved tip amount in store currency.
         * @param TipSelection $selection The selection service.
         * @param float        $base      Subtotal base used for percentage tips.
         */
        $amount = (float) apply_filters('tipping/fee_amount', $amount, $this, $base);

        return $this->roundAmount($amount);
    }

    /**
     * Validate a raw choice array against current settings.
     *
     * @param array<string, mixed> $choice
     * @return array{mode: string, preset: int, recipient: string}
     */
    public function normalizeChoice(array $choice): array
    {
        return $this->normalize($choice);
    }

    /**
     * The pre-tip items subtotal (tax-exclusive) used as the base for percentage
     * tips and exposed to PRO add-ons via the `tipping/fee_amount` filter.
     */
    public function cartSubtotal(): float
    {
        return $this->cartBase();
    }

    /**
     * Normalise a raw choice array into the canonical shape, rejecting anything
     * that does not match the current settings.
     *
     * @param array<string, mixed> $choice
     * @return array{mode: string, preset: int, recipient: string}
     */
    private function normalize(array $choice): array
    {
        $recipient = isset($choice['recipient']) ? sanitize_key((string) $choice['recipient']) : '';

        if ('' !== $recipient && ! $this->recipients->isValid($recipient)) {
            $recipient = '';
        }

        $mode = isset($choice['mode']) ? sanitize_key((string) $choice['mode']) : 'none';

        if ('preset' === $mode) {
            $preset  = isset($choice['preset']) ? (int) $choice['preset'] : -1;
            $presets = $this->options->presets();

            if ($preset >= 0 && isset($presets[$preset])) {
                return ['mode' => 'preset', 'preset' => $preset, 'recipient' => $recipient];
            }
        }

        if ('none' === $mode) {
            return ['mode' => 'none', 'preset' => 0, 'recipient' => $recipient];
        }

        return ['mode' => 'none', 'preset' => 0, 'recipient' => ''];
    }

    /**
     * The cart base used for percentage tips: the pre-tip items subtotal,
     * tax-exclusive. Falls back to 0.
     */
    private function cartBase(): float
    {
        $cart = WC()->cart;

        if (! $cart instanceof \WC_Cart) {
            return 0.0;
        }

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
