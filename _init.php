<?php

use WPStaging\WPStaging;

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
    'php' => '5.5',
    'wp' => '4.0',
    'file' => __FILE__,
));

if ($pluginRequirements->passes()) {

    // @todo remove legacy custom auto-loader in WPStaging\Utils\Autoloader and use this composer based one instead!

    // Load composer autoloader
    require_once __DIR__ . '/vendor/autoload.php';

    require_once __DIR__ . '/constants.php';

    $wpStaging = WPStaging::getInstance();

    /*
     * Set the WPSTG_COMPATIBLE constant in the container,
     * so that we can change it for testing purposes.
     */
    $wpStaging->set('WPSTG_COMPATIBLE', WPSTG_COMPATIBLE);

    // Wordpress DB Object
    global $wpdb;

    if ($wpdb instanceof \wpdb) {
        $wpStaging->set("wpdb", $wpdb);
    }
}
