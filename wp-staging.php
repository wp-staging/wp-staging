<?php

/**
 * Plugin Name: WP Staging
 * Plugin URI: wordpress.org/plugins/wp-staging
 * Description: Create a staging clone site for testing & developing
 * Author: WP-Staging, René Hermenau, Ilgıt Yıldırım
 * Author URI: https://wordpress.org/plugins/wp-staging
 * Version: 2.0.0
 * Text Domain: wpstg
 * Domain Path: /vars/languages/

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

require_once plugin_dir_path(__FILE__) . "apps/Core/WPStaging.php";

$wpStaging = \WPStaging\WPStaging::getInstance();

if (isset($wpdb))
{
    $wpStaging->set("wpdb", $wpdb);
}

$wpStaging->run();