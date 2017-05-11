<?php

/**
 * Plugin Name: WP Staging Pro
 * Plugin URI: https://wp-staging.com
 * Description: Create a staging clone site for testing & developing
 * Author: WP-Staging, René Hermenau, Ilgıt Yıldırım
 * Author URI: https://wordpress.org/plugins/wp-staging
 * Version: 2.0.3
 * Text Domain: wpstg
 * Domain Path: /languages/
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

require_once plugin_dir_path(__FILE__) . "apps/Core/WPStaging.php";

$wpStaging = \WPStaging\WPStaging::getInstance();

// Load WP globals into WPStaging
if (isset($wpdb))
{
    $wpStaging->set("wpdb", $wpdb);
}


if (isset($wp_filter))
{
    $wpStaging->set("wp_filter", function() use(&$wp_filter) {
        return $wp_filter;
    });
}

$wpStaging->run();