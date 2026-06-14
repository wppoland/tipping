<?php

declare(strict_types=1);

namespace Tipping\Service;

use Tipping\Contract\HasHooks;
use Tipping\Settings\Options;

defined('ABSPATH') || exit;

/**
 * Applies the customer's chosen tip as a WooCommerce cart fee and persists it on
 * the resulting order. The fee is recomputed on every cart calculation, so it
 * stays in sync with the live subtotal (for percentage tips) and with the
 * customer's latest selection.
 */
final class TipFee implements HasHooks
{
    /** Order meta flag marking an order that carried a tip. */
    private const ORDER_META = '_tipping_amount';

    public function __construct(
        private readonly Options $options,
        private readonly TipSelection $selection,
    ) {
    }

    public function registerHooks(): void
    {
        add_action('woocommerce_cart_calculate_fees', [$this, 'addFee']);
        add_action('wp_ajax_tipping_set', [$this, 'handleAjax']);
        add_action('wp_ajax_nopriv_tipping_set', [$this, 'handleAjax']);
        add_action('woocommerce_checkout_create_order', [$this, 'persistOnOrder'], 10, 2);
    }

    /**
     * Add the tip as a cart fee. Renders nothing when the feature is unusable or
     * the resolved amount is zero, so an empty selection never adds a £0 line.
     */
    public function addFee(\WC_Cart $cart): void
    {
        if (! $this->options->isUsable()) {
            return;
        }

        // Avoid double-charging in nested calculate_totals calls.
        if (did_action('woocommerce_cart_calculate_fees') > 1 && $this->feeAlreadyAdded($cart)) {
            return;
        }

        $amount = $this->selection->resolveAmount();

        if ($amount <= 0.0) {
            return;
        }

        $cart->add_fee($this->feeLabel(), $amount, $this->options->isTaxable());
    }

    /**
     * AJAX endpoint: store the customer's choice, then let WooCommerce's checkout
     * AJAX re-render totals. Nonce-protected; no capability needed (front-end
     * shoppers, logged-in or guest).
     */
    public function handleAjax(): void
    {
        check_ajax_referer('tipping_set', 'nonce');

        $mode = isset($_POST['mode']) ? sanitize_key(wp_unslash((string) $_POST['mode'])) : 'none';

        $choice = ['mode' => $mode];

        if ('preset' === $mode && isset($_POST['preset'])) {
            $choice['preset'] = absint(wp_unslash((string) $_POST['preset']));
        }

        if ('custom' === $mode && isset($_POST['amount'])) {
            $choice['amount'] = wc_format_decimal(sanitize_text_field(wp_unslash((string) $_POST['amount'])));
        }

        $this->selection->store($choice);

        wp_send_json_success([
            'amount' => $this->selection->resolveAmount(),
        ]);
    }

    /**
     * Persist the applied tip onto the order at checkout, so it is auditable in
     * the admin and in emails (it is already part of the order totals as a fee).
     */
    public function persistOnOrder(\WC_Order $order, mixed $data): void
    {
        unset($data);

        if (! $this->options->isUsable()) {
            return;
        }

        $amount = $this->selection->resolveAmount();

        if ($amount > 0.0) {
            $order->update_meta_data(self::ORDER_META, wc_format_decimal($amount));
        }
    }

    private function feeLabel(): string
    {
        return $this->options->label();
    }

    /**
     * Whether a fee with our label is already present on the cart.
     */
    private function feeAlreadyAdded(\WC_Cart $cart): bool
    {
        $label = $this->feeLabel();

        foreach ($cart->get_fees() as $fee) {
            if (isset($fee->name) && $fee->name === $label) {
                return true;
            }
        }

        return false;
    }
}
