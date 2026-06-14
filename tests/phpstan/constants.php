<?php
/**
 * Constants needed by PHPStan to analyse the plugin without bootstrapping
 * WordPress. These are defined at runtime in tipping.php.
 *
 * @package Tipping
 */

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
    if (! defined('TIPPING_DIR')) {
        define('TIPPING_DIR', '/tmp/tipping/');
    }
    if (! defined('TIPPING_URL')) {
        define('TIPPING_URL', 'https://example.test/wp-content/plugins/tipping/');
    }
}

namespace Tipping {
    if (! defined('Tipping\\VERSION')) {
        define('Tipping\\VERSION', '0.1.0');
    }
    if (! defined('Tipping\\PLUGIN_FILE')) {
        define('Tipping\\PLUGIN_FILE', '/tmp/tipping/tipping.php');
    }
}
