<?php
/**
 * Uninstall cleanup for Tipping.
 *
 * Runs when the plugin is deleted from wp-admin. Removes the options the plugin
 * creates. The `_tipping_amount` order meta is intentionally left in place: it
 * is part of completed orders' financial record and must not be erased.
 *
 * @package Tipping
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('tipping_settings');
delete_option('tipping_db_version');
