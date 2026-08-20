<?php

use WPStaging\Core\WPStaging;




$pluginFilePath = $pluginFilePath ?? '';
if (file_exists(__DIR__ . '/autoloader_dev.php')) {
    include_once __DIR__ . '/autoloader_dev.php';
} elseif (wpstgDoLoadPluginAutoLoad($pluginFilePath)) {
    include_once __DIR__ . '/autoloader.php';
}

 
 
 
 
 
 
if (
    !class_exists('\WPStaging\Vendor\lucatume\DI52\Container')
    || !class_exists('\WPStaging\Core\WPStaging')
) {
    add_action('admin_notices', function () {
        if (!current_user_can('manage_options')) {
            return;
        }

 
 
        echo '<div class="notice notice-error"><p><strong>WP STAGING:</strong> '
            . esc_html('Plugin files appear to be missing or corrupted. Please reinstall the plugin or contact support@wp-staging.com for help.')
            . '</p></div>';
    });

    return;
}

 
if (!defined('WPSTG_PLUGIN_FILE')) {
    define('WPSTG_PLUGIN_FILE', $pluginFilePath);
}

 
if (!defined('WPSTG_PLUGIN_DIR')) {
    define('WPSTG_PLUGIN_DIR', plugin_dir_path($pluginFilePath));
}

 
if (!defined('WPSTG_PLUGIN_URL')) {
    define('WPSTG_PLUGIN_URL', plugin_dir_url($pluginFilePath));
}

 
if (!defined('WPSTG_OPTIMIZER_MUVERSION')) {
    define('WPSTG_OPTIMIZER_MUVERSION', '1.6.2');
}

 
if (!defined('WPSTG_PLUGIN_SLUG')) {
    define('WPSTG_PLUGIN_SLUG', basename(dirname($pluginFilePath)));
}

 
if (!defined('WPSTG_PLUGIN_DOMAIN')) {
    define('WPSTG_PLUGIN_DOMAIN', 'wp-staging');
}

 
if (!defined('WPSTG_VIEWS_DIR')) {
    define('WPSTG_VIEWS_DIR', WPSTG_PLUGIN_DIR . 'views/');
}

 
if (!defined('WPSTG_RESOURCES_DIR')) {
    define('WPSTG_RESOURCES_DIR', WPSTG_PLUGIN_DIR . 'resources/');
}

 
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






if (file_exists(__DIR__ . '/constantsPro.php')) {
    include_once __DIR__ . '/constantsPro.php';
} elseif (file_exists(__DIR__ . '/constantsFree.php')) {
    include_once __DIR__ . '/constantsFree.php';
}

if (!function_exists('\WPStaging\functions\debug_log') && file_exists(__DIR__ . '/wp-staging-error-handler.php')) {
    include_once __DIR__ . '/wp-staging-error-handler.php';
}

 
if (php_sapi_name() === "cli" && defined("WPSTG_UNIT_TESTS") && constant("WPSTG_UNIT_TESTS")) {
    WPStaging::setUseBaseContainerSingleton(true);
}

$wpStaging = WPStaging::getInstance();
$wpStaging->registerErrorHandler();





$wpStaging->set('WPSTG_COMPATIBLE', WPSTG_COMPATIBLE);




$wpStaging->set('WPSTG_ALLOW_VFS', false);

 
global $wpdb;

if ($wpdb instanceof wpdb) {
    $wpStaging->set("wpdb", $wpdb);
}
