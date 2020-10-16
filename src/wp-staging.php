<?php

/**
 * Plugin Name: WP STAGING
 * Plugin URI: https://wordpress.org/plugins/wp-staging
 * Description: Create a staging clone site for testing & developing
 * Author: WP-STAGING
 * Author URI: https://wp-staging.com
 * Contributors: ReneHermi
 * Version: 2.7.6
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
 * @author WP STAGING
 */

if (!defined("WPINC")) {
    die;
}

// Absolute path to plugin dir /var/www/.../plugins/wp-staging(-pro)
if (!defined('WPSTG_PLUGIN_DIR')) {
    define('WPSTG_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// Absolute path and name to main plugin entry file /var/www/.../plugins/wp-staging(-pro)/wp-staging(-pro).php
if (!defined('WPSTG_PLUGIN_FILE')) {
    define('WPSTG_PLUGIN_FILE', __FILE__);
}

require_once (__DIR__ . '/_init.php');
