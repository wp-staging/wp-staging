<?php

use WPStaging\Core\WPStaging;

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

// Todo: Rename class to WPStaging\Core\WPStaging to comply with PSR-4
if (!class_exists('WPStaging\Core\WPStaging')) {
    require_once plugin_dir_path(__FILE__) . "Core/WPStaging.php";
}

// Todo: Rename class to WPStaging\Core\Utils\WPSTG_Requirements_Check to comply with PSR-4
if (!class_exists('Wpstg_Requirements_Check')) {
    include(__DIR__ . '/Core/Utils/requirements-check.php');
}

$pluginRequirements = new Wpstg_Requirements_Check([
    'title' => 'WP STAGING',
    'php' => '5.5',
    'wp' => '4.0',
    'file' => __FILE__,
]);

if ($pluginRequirements->passes()) {

    $wpStaging = WPStaging::getInstance(new \WPStaging\Framework\DI\Container);

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
