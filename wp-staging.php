<?php

/**
 * Plugin Name: WP Staging
 * Plugin URI: https://wordpress.org/plugins/wp-staging
 * Description: Create a staging clone site for testing & developing
 * Author: WP-Staging, René Hermenau, Ilgıt Yıldırım
 * Author URI: https://wordpress.org/plugins/wp-staging
 * Version: 2.0.5
 * Text Domain: wpstg
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
 * @category Core
 * @author René Hermenau, Ilgıt Yıldırım
 */

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

// Plugin Folder Path
if( !defined( 'WPSTG_PLUGIN_DIR' ) ) {
   define( 'WPSTG_PLUGIN_DIR', plugin_dir_path(  __FILE__ ) );
}
// Plugin Folder URL
if( !defined( 'WPSTG_PLUGIN_URL' ) ) {
   define( 'WPSTG_PLUGIN_URL', plugin_dir_url(  __FILE__ ) );
}

//require_once WPSTG_PLUGIN_DIR . 'apps/Backend/Install/install.php';

/**
 * Path to main WP Staging class
 * Make sure to not redeclare class in case free version has been installed previosly
 */
if (!class_exists( 'WPStaging\WPStaging' )){
   require_once plugin_dir_path(__FILE__) . "apps/Core/WPStaging.php";
}

$wpStaging = \WPStaging\WPStaging::getInstance();

/**
 * Load a few important WP globals into WPStaging class to make them available via dependancy injection
 */

// Wordpress DB Object
if (isset($wpdb))
{
    $wpStaging->set("wpdb", $wpdb);
}

// WordPress Filter Object
if (isset($wp_filter))
{
    $wpStaging->set("wp_filter", function() use(&$wp_filter) {
        return $wp_filter;
    });
}

/**
 * Inititalize WPStaging
 */
$wpStaging->run();
