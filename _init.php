<?php

use WPStaging\WPStaging;

// Compatible maximum WordPress Version
if (!defined('WPSTG_COMPATIBLE')) {
    define('WPSTG_COMPATIBLE', '5.5.1');
}

// Compatible minimum WordPress version
if (!defined('WPSTG_MIN_WP_VERSION')) {
    define('WPSTG_MIN_WP_VERSION', '4.0');
}

// Compatible up to PHP Version
if (!defined('WPSTG_PHP_COMPATIBLE')) {
    define('WPSTG_PHP_COMPATIBLE', '5.5');
}

// Expected version number of the must-use plugin 'optimizer'. Used for automatic updates of the mu-plugin
if (!defined('WPSTG_OPTIMIZER_MUVERSION')) {
    define('WPSTG_OPTIMIZER_MUVERSION', 1.3);
}

// URL of the base folder
if (!defined('WPSTG_PLUGIN_URL')) {
    define('WPSTG_PLUGIN_URL', plugin_dir_url(WPSTG_PLUGIN_FILE));
}

/**
 * Load Basic/Pro related constants and due to that the rest of the basic or pro version
 */
if (file_exists(plugin_dir_path(__FILE__) . 'Pro/constants.php')) {
    require_once plugin_dir_path(__FILE__) . 'Pro/constants.php';
} else {
    require_once plugin_dir_path(__FILE__) . 'constants.php';
}


/**
 * Do not show update notifications for WP STAGING Pro on the staging site
 * @param object
 * @return object
 * @todo Move this to separate class, e.g. wpstaging/Core/Wpcore/PluginUpdateNotify()
 */
if (!function_exists('wpstg_filter_plugin_updates')) {
    function wpstg_filter_plugin_updates($value)
    {
        if (wpstg_is_stagingsite()) {
            if (isset($value->response['wp-staging-pro/wp-staging-pro.php'])) {
                unset($value->response['wp-staging-pro/wp-staging-pro.php']);
            }
        }
        return $value;
    }
}
add_filter('site_transient_update_plugins', 'wpstg_filter_plugin_updates');

/**
 * Path to main WP Staging class
 * Make sure to not redeclare class in case free version has been installed previously
 */
if (!class_exists('WPStaging\WPStaging')) {
    require_once plugin_dir_path(__FILE__) . "Core/WPStaging.php";
}

if (!class_exists('Wpstg_Requirements_Check')) {
    include(__DIR__ . '/Core/Utils/requirements-check.php');
}

$pluginRequirements = new Wpstg_Requirements_Check(array(
    'title' => 'WP STAGING',
    'php' => WPSTG_PHP_COMPATIBLE,
    'wp' => WPSTG_MIN_WP_VERSION,
    'file' => __FILE__,
));


if ($pluginRequirements->passes()) {

    // @todo remove legacy custom auto-loader in WPStaging\Utils\Autoloader and use this composer based one instead!

    // Load composer autoloader
    require_once __DIR__ . '/vendor/autoload.php';

    $wpStaging = WPStaging::getInstance();

    /**
     * Load important WP globals into WPStaging class to make them available via dependancy injection
     */

    // Wordpress DB Object
    if (isset($wpdb)) {
        $wpStaging->set("wpdb", $wpdb);
    }

    // WordPress Filter Object
    if (isset($wp_filter)) {
        $wpStaging->set("wp_filter", function () use (&$wp_filter) {
            return $wp_filter;
        });
    }

    /**
     * Inititalize WPStaging
     */
    $wpStaging->run();


    /**
     * Installation Hooks
     */
    if (!class_exists('WPStaging\Install')) {
        require_once WPSTG_PLUGIN_DIR . "install.php";
    }
}
