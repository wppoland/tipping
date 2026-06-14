<?php

declare(strict_types=1);

namespace Tipping\Settings;

defined('ABSPATH') || exit;

/**
 * Typed accessor over the single `tipping_settings` option, merged on read over
 * the packaged defaults. Centralises option access and value coercion so the
 * service, frontend and admin layers all see the same shape.
 */
final class Options
{
    public const OPTION = 'tipping_settings';

    /** Allowed preset types. */
    public const TYPES = ['percent', 'fixed'];

    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    /**
     * All settings merged over the packaged defaults.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (null !== $this->cache) {
            return $this->cache;
        }

        $stored = get_option(self::OPTION, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require \TIPPING_DIR . 'config/defaults.php';

        return $this->cache = array_merge($defaults, $stored);
    }

    /**
     * Forget the in-request cache (used after a save in the same request).
     */
    public function flush(): void
    {
        $this->cache = null;
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->all()['enabled'] ?? false);
    }

    public function label(): string
    {
        $label = trim((string) ($this->all()['label'] ?? ''));

        return '' !== $label ? $label : __('Add a tip', 'tipping');
    }

    public function description(): string
    {
        return (string) ($this->all()['description'] ?? '');
    }

    /**
     * Preset type: 'percent' or 'fixed'.
     */
    public function type(): string
    {
        $type = (string) ($this->all()['type'] ?? 'percent');

        return in_array($type, self::TYPES, true) ? $type : 'percent';
    }

    public function isPercent(): bool
    {
        return 'percent' === $this->type();
    }

    /**
     * Cleaned, positive preset values in display order.
     *
     * @return list<float>
     */
    public function presets(): array
    {
        $raw = $this->all()['presets'] ?? [];

        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $value) {
            $value = (float) $value;
            if ($value > 0) {
                $out[] = $value;
            }
        }

        return array_values($out);
    }

    /**
     * The default selection: an integer preset index, or null for "no tip".
     */
    public function defaultSelectionIndex(): ?int
    {
        $value = (string) ($this->all()['default_selection'] ?? 'none');

        if ('' === $value || 'none' === $value) {
            return null;
        }

        $index   = (int) $value;
        $presets = $this->presets();

        return isset($presets[$index]) ? $index : null;
    }

    public function allowCustom(): bool
    {
        return (bool) ($this->all()['allow_custom'] ?? false);
    }

    public function showOnCart(): bool
    {
        return (bool) ($this->all()['show_on_cart'] ?? false);
    }

    public function showOnCheckout(): bool
    {
        return (bool) ($this->all()['show_on_checkout'] ?? false);
    }

    public function isTaxable(): bool
    {
        return (bool) ($this->all()['taxable'] ?? false);
    }

    /**
     * True when the control has something to render: enabled, and at least one
     * preset or a custom-amount field available.
     */
    public function isUsable(): bool
    {
        return $this->isEnabled() && ($this->presets() !== [] || $this->allowCustom());
    }
}
