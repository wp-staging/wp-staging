<?php

/**
 * Plugin Name: WP Staging
 * Plugin URI: https://wordpress.org/plugins/wp-staging
 * Description: Create a staging clone site for testing & developing
 * Author: WP-Staging
 * Author URI: https://wp-staging.com
 * Contributors: ReneHermi, ilgityildirim
 * Version: {{version}}
 * Text Domain: wp-staging
 * Domain Path: /languages/
 *
 * WP-Staging is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WP-Staging is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Staging. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package WPSTG
 * @category Development, Migrating, Staging
 * @author WP Staging
 */
// No Direct Access
if (!defined("WPINC")) {
    die;
}

if (!defined('WPSTG_PLUGIN_SLUG')) {
    define('WPSTG_PLUGIN_SLUG', 'wp-staging');
}

// Plugin Version
if (!defined('WPSTG_VERSION')) {
    define('WPSTG_VERSION', '{{version}}');
}

// Compatible up to WordPress Version
if (!defined('WPSTG_COMPATIBLE')) {
    define('WPSTG_COMPATIBLE', '5.3.2');
}

// Folder Path
if (!defined('WPSTG_PLUGIN_DIR')) {
    define('WPSTG_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// Version number of the must-use plugin 'optimizer'
if (!defined('WPSTG_OPTIMIZER_MUVERSION')) {
    define('WPSTG_OPTIMIZER_MUVERSION', 1.1);
}

// URL of the base folder
if( !defined( 'WPSTG_PLUGIN_URL' ) ) {
    define( 'WPSTG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Fix nonce check
 * Bug: https://core.trac.wordpress.org/ticket/41617#ticket
 * @todo Move this to separate class wpstaging/Core/Wpcore/WpFixes()
 * @param int $seconds
 * @return int
 */
if ( !function_exists( 'wpstg_overwrite_nonce')){
    function wpstg_overwrite_nonce( $seconds ) {
        return 86400;
    }
}

add_filter( 'nonce_life', 'wpstg_overwrite_nonce', 99999 );

/**
 * Do not show update notifications for WP Staging Pro on the staging site
 * @todo Move this to separate class wpstaging/Core/Wpcore/PluginUpdateNotify()
 * @param type object
 * @return object
 */
if ( !function_exists( 'wpstg_filter_plugin_updates')) {
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
    include(dirname(__FILE__) . '/Core/Utils/requirements-check.php');
}

$plugin_requirements = new Wpstg_Requirements_Check(array(
    'title' => 'WP STAGING',
    'php' => '5.3',
    'wp' => '3.0',
    'file' => __FILE__,
));

if ($plugin_requirements->passes()) {

    $wpStaging = \WPStaging\WPStaging::getInstance();

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
        require_once plugin_dir_path(__FILE__) . "/install.php";
    }
}
