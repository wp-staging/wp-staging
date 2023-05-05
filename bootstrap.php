<?php

/**
 * @var string $pluginFilePath The absolute path to the main file of this plugin.
 */

use WPStaging\Core\WPStaging;

if (file_exists(__DIR__ . '/autoloader_dev.php')) {
    include_once __DIR__ . '/autoloader_dev.php';
} else {
    include_once __DIR__ . '/autoloader.php';
}

// Early bail: Unexpected behavior from the autoloader
if (!class_exists('\WPStaging\Core\WPStaging')) {
    return;
}

// Register common constants.
if (!defined('WPSTG_PLUGIN_FILE')) {
    define('WPSTG_PLUGIN_FILE', $pluginFilePath);
}

// Absolute path to plugin dir /var/www/.../plugins/wp-staging(-pro)/
if (!defined('WPSTG_PLUGIN_DIR')) {
    define('WPSTG_PLUGIN_DIR', plugin_dir_path($pluginFilePath));
}

// URL of the base folder
if (!defined('WPSTG_PLUGIN_URL')) {
    define('WPSTG_PLUGIN_URL', plugin_dir_url($pluginFilePath));
}

// Expected version number of the must-use plugin 'optimizer'. Used for automatic updates of the mu-plugin
if (!defined('WPSTG_OPTIMIZER_MUVERSION')) {
    define('WPSTG_OPTIMIZER_MUVERSION', '1.5.1');
}

// /var/www/single/wp-content/plugins/wp-staging-pro/wp-staging-pro.php => wp-staging-pro
if (!defined('WPSTG_PLUGIN_SLUG')) {
    define('WPSTG_PLUGIN_SLUG', basename(dirname($pluginFilePath)));
}

// An identifier that is the same both for WP STAGING Free and WP STAGING | PRO
if (!defined('WPSTG_PLUGIN_DOMAIN')) {
    define('WPSTG_PLUGIN_DOMAIN', 'wp-staging');
}

// Features
if (!defined('WPSTG_FEATURE_ENABLE_BACKUP')) {
    define('WPSTG_FEATURE_ENABLE_BACKUP', true);
}

// Define WordPress default constants if not already defined in outdated WP version for backward compatibility
if (!defined('KB_IN_BYTES')) {
    define('KB_IN_BYTES', 1024);
}

if (!defined('MB_IN_BYTES')) {
    define('MB_IN_BYTES', 1024 * KB_IN_BYTES);
}

if (!defined('GB_IN_BYTES')) {
    define('GB_IN_BYTES', 1024 * MB_IN_BYTES);
}

if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS);
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS);
}

if (!defined('WEEK_IN_SECONDS')) {
    define('WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS);
}

if (!defined('MONTH_IN_SECONDS')) {
    define('MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS);
}

if (!defined('YEAR_IN_SECONDS')) {
    define('YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS);
}

// PHP 5.6 compatibility
if (file_exists(__DIR__ . '/php56-compatibilty.php')) {
    include_once __DIR__ . '/php56-compatibilty.php';
}

/**
 * Register specific Pro and Free constants. We register them here instead of on the
 * entrypoint because we want to make sure we are defining constants for the plugins
 * actually being bootstrapped.
 */
if (file_exists(__DIR__ . '/constantsPro.php')) {
    include_once __DIR__ . '/constantsPro.php';
} elseif (file_exists(__DIR__ . '/constantsFree.php')) {
    include_once __DIR__ . '/constantsFree.php';
}

if (file_exists(__DIR__ . '/wp-staging-error-handler.php')) {
    include_once __DIR__ . '/wp-staging-error-handler.php';
}

$wpStaging = WPStaging::getInstance();
$wpStaging->registerErrorHandler();

/*
 * Set the WPSTG_COMPATIBLE constant in the container,
 * so that we can change it for testing purposes.
 */
$wpStaging->set('WPSTG_COMPATIBLE', WPSTG_COMPATIBLE);

// Wordpress DB Object
global $wpdb;

if ($wpdb instanceof wpdb) {
    $wpStaging->set("wpdb", $wpdb);
}
