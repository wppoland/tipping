<?php

declare(strict_types=1);

namespace Tipping\Service;

defined('ABSPATH') || exit;

/**
 * Resolves the list of tip recipients shoppers can choose from.
 *
 * The FREE plugin exposes an empty list by default; PRO add-ons populate it via
 * the `tipping/recipients` filter.
 */
final class Recipients
{
    /**
     * @return list<array{id: string, label: string}>
     */
    public function all(): array
    {
        /**
         * Filters the tip recipients shown on checkout.
         *
         * @param list<array{id: string, label: string}> $recipients Recipient rows.
         */
        $raw = apply_filters('tipping/recipients', []);

        if (! is_array($raw)) {
            return [];
        }

        $out   = [];
        $seen  = [];

        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = isset($row['id']) ? sanitize_key((string) $row['id']) : '';

            if ('' === $id || isset($seen[$id])) {
                continue;
            }

            $label = isset($row['label']) ? sanitize_text_field((string) $row['label']) : '';

            if ('' === $label) {
                continue;
            }

            $seen[$id] = true;
            $out[]     = [
                'id'    => $id,
                'label' => $label,
            ];
        }

        return $out;
    }

    public function isValid(string $id): bool
    {
        if ('' === $id) {
            return false;
        }

        foreach ($this->all() as $recipient) {
            if ($recipient['id'] === $id) {
                return true;
            }
        }

        return false;
    }
}
