<?php

/**
 * Plugin Name: WP STAGING
 * Plugin URI: https://wordpress.org/plugins/wp-staging
 * Description: Create a staging clone site for testing & developing
 * Author: WP-STAGING
 * Author URI: https://wp-staging.com
 * Contributors: ReneHermi
 * Version: 2.8.0
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

namespace WPStaging\Bootstrap\V1;

if (!defined("WPINC")) {
    die;
}

if (!defined('WPSTG_FREE_LOADED')) {
    define('WPSTG_FREE_LOADED', __FILE__);
}

require_once __DIR__ . '/Bootstrap/V1/Requirements/WpstgFreeRequirements.php';
require_once __DIR__ . '/Bootstrap/V1/WpstgBootstrap.php';

if (!class_exists(WpstgFreeBootstrap::class)) {
    class WpstgFreeBootstrap extends WpstgBootstrap
    {
        protected function afterBootstrap()
        {
            if (!defined('WPSTG_PLUGIN_FILE')) {
                define('WPSTG_PLUGIN_FILE', __FILE__);
            }

            // WP STAGING version number
            if (!defined('WPSTG_VERSION')) {
                define('WPSTG_VERSION', '2.8.0');
            }

            // Compatible up to WordPress Version
            if (!defined('WPSTG_COMPATIBLE')) {
                define('WPSTG_COMPATIBLE', '5.6');
            }

            require_once __DIR__ . '/constants.php';

            require_once(__DIR__ . '/_init.php');
        }
    }
}

$bootstrap = new WpstgFreeBootstrap(__DIR__, new WpstgFreeRequirements(__FILE__));

add_action('plugins_loaded', [$bootstrap, 'checkRequirements'], 5);
add_action('plugins_loaded', [$bootstrap, 'bootstrap'], 10);

/** Installation Hooks */
if (!class_exists('WPStaging\Install')) {
    require_once __DIR__ . "/install.php";

    $install = new \WPStaging\Install($bootstrap);
    register_activation_hook(__FILE__, [$install, 'activation']);
}
