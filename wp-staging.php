<?php

/**
 * Plugin Name: WP STAGING
 * Plugin URI: https://wordpress.org/plugins/wp-staging
 * Description: Create a staging clone site for testing & developing
 * Author: WP-STAGING
 * Author URI: https://wp-staging.com
 * Contributors: ReneHermi
 * Version: 2.7.7
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

if (!defined('WPSTG_FREE_LOADED')) {
    define('WPSTG_FREE_LOADED', __FILE__);
}

// Standalone requirement-checking script
require_once 'Requirements/WpstgFreeRequirements.php';

if (!class_exists('WpstgFreeBootstrap')) {
    class WpstgFreeBootstrap
    {
        private $shouldBootstrap = true;
        private $requirements;

        public function __construct(WpstgRequirements $requirements)
        {
            $this->requirements = $requirements;
        }

        public function checkRequirements()
        {
            try {
                $this->requirements->checkRequirements();
            } catch (Exception $e) {
                $this->shouldBootstrap = false;

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf("[Activation] WP STAGING: %s", $e->getMessage()));
                }
            }
        }

        public function bootstrap()
        {
            // Early bail: Requirements not met.
            if (!$this->shouldBootstrap) {
                return;
            }

            // Absolute path to plugin dir /var/www/.../plugins/wp-staging(-pro)
            if (!defined('WPSTG_PLUGIN_DIR')) {
                define('WPSTG_PLUGIN_DIR', plugin_dir_path(__FILE__));
            }

            // Absolute path and name to main plugin entry file /var/www/.../plugins/wp-staging(-pro)/wp-staging(-pro).php
            if (!defined('WPSTG_PLUGIN_FILE')) {
                define('WPSTG_PLUGIN_FILE', __FILE__);
            }

            require_once(__DIR__ . '/_init.php');
        }
    }
}

$bootstrap = new WpstgFreeBootstrap(new WpstgFreeRequirements(__FILE__));

add_action('plugins_loaded', [$bootstrap, 'checkRequirements'], 5);
add_action('plugins_loaded', [$bootstrap, 'bootstrap'], 10);
