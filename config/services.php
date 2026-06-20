<?php
/**
 * Service wiring. Returns a closure that registers every service in the
 * container. Services are thin and self-contained — no external runtime deps.
 *
 * @package Tipping
 */

declare(strict_types=1);

use Tipping\Admin\Settings;
use Tipping\Container;
use Tipping\Frontend\TipControl;
use Tipping\Migrator;
use Tipping\Service\Recipients;
use Tipping\Service\TipFee;
use Tipping\Service\TipSelection;
use Tipping\Settings\Options;

defined('ABSPATH') || exit;

return static function (Container $c): void {
    $c->singleton(Migrator::class, static fn (): Migrator => new Migrator());

    $c->singleton(Options::class, static fn (): Options => new Options());

    $c->singleton(Recipients::class, static fn (): Recipients => new Recipients());

    $c->singleton(TipSelection::class, static fn (Container $c): TipSelection => new TipSelection(
        $c->get(Options::class),
        $c->get(Recipients::class),
    ));

    $c->singleton(TipFee::class, static fn (Container $c): TipFee => new TipFee(
        $c->get(Options::class),
        $c->get(TipSelection::class),
    ));

    $c->singleton(TipControl::class, static fn (Container $c): TipControl => new TipControl(
        $c->get(Options::class),
        $c->get(TipSelection::class),
        $c->get(Recipients::class),
    ));

    // Admin (only needed in wp-admin context).
    if (is_admin()) {
        $c->singleton(Settings::class, static fn (Container $c): Settings => new Settings(
            $c->get(Options::class),
        ));
    }
};
