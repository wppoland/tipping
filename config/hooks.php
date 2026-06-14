<?php
/**
 * Boot order: services listed here are resolved from the container and have
 * their registerHooks() called during Plugin::boot(). Each must implement
 * Tipping\Contract\HasHooks.
 *
 * @package Tipping
 *
 * @return array<class-string>
 */

declare(strict_types=1);

use Tipping\Admin\Settings;
use Tipping\Frontend\TipControl;
use Tipping\Service\TipFee;

defined('ABSPATH') || exit;

return is_admin()
    ? [
        TipFee::class,
        Settings::class,
    ]
    : [
        TipFee::class,
        TipControl::class,
    ];
