<?php
/**
 * Plugin Name: WP Staging - Create a staging clone site for testing & developing
 * Plugin URI: wordpress.org/plugins/wp-staging
 * Description: WP-Staging - Create a staging clone site for testing & developing
 * Author: WP-Staging, René Hermenau
 * Author URI: https://wordpress.org/plugins/wp-staging
 * Version: 0.9.9
 * Text Domain: wpstg
 * Domain Path: languages

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
 * @author René Hermenau
 * @version 0.9.1
 */
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

// Plugin version
if (!defined('WPSTG_VERSION')) {
    define('WPSTG_VERSION', '0.9.9');
}
// Plugin version
if (!defined('WPSTG_WP_COMPATIBLE')) {
    define('WPSTG_WP_COMPATIBLE', '4.3.1');
}

if (!class_exists('wpstaging')) :

    /**
     * Main wpstg Class
     *
     * @since 0.9.0
     */
    final class wpstaging {
        /** Singleton ************************************************************ */

        /**
         * @var WP-Staging The one and only WP-Staging
         * @since 1.0
         */
        private static $instance;

        /**
         * WPSTG HTML Element Helper Object
         *
         * @var object
         * @since 2.0.0
         */
        //public $html;
        
        /* WPSTG LOGGER Class
         * 
         */
        public $logger;
        
        

        /**
         * Main WP-Staging Instance
         *
         * Insures that only one instance of wp-staging exists in memory at any one
         * time. Also prevents needing to define globals all over the place.
         *
         * @since 1.0
         * @static
         * @staticvar array $instance
         * @uses wp-staging::setup_constants() Setup the constants needed
         * @uses wp-staging::includes() Include the required files
         * @uses wp-staging::load_textdomain() load the language files
         * @see WPSTG()
         * @return The one true wp-staging
         */
        public static function instance() {
            if (!isset(self::$instance) && !( self::$instance instanceof wpstaging )) {
                self::$instance = new wpstaging;
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                //self::$instance->html = new WPSTG_HTML_Elements();
                self::$instance->logger = new wpstgLogger("wpstglog_" . date("Y-m-d") . ".log", wpstgLogger::INFO);
            }
            return self::$instance;
        }

        /**
         * Throw error on object clone
         *
         * The whole idea of the singleton design pattern is that there is a single
         * object therefore, we don't want the object to be cloned.
         *
         * @since 1.0
         * @access protected
         * @return void
         */
        public function __clone() {
            // Cloning instances of the class is forbidden
            _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'WPSTG'), '1.0');
        }

        /**
         * Disable unserializing of the class
         *
         * @since 1.0
         * @access protected
         * @return void
         */
        public function __wakeup() {
            // Unserializing instances of the class is forbidden
            _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'WPSTG'), '1.0');
        }

        /**
         * Setup plugin constants
         *
         * @access private
         * @since 1.0
         * @return void
         */
        private function setup_constants() {
            global $wpdb;

            // Plugin Folder Path
            if (!defined('WPSTG_PLUGIN_DIR')) {
                define('WPSTG_PLUGIN_DIR', plugin_dir_path(__FILE__));
            }

            // Plugin Folder URL
            if (!defined('WPSTG_PLUGIN_URL')) {
                define('WPSTG_PLUGIN_URL', plugin_dir_url(__FILE__));
            }

            // Plugin Root File
            if (!defined('WPSTG_PLUGIN_FILE')) {
                define('WPSTG_PLUGIN_FILE', __FILE__);
            }

            // Plugin database
            // Plugin Root File
            if (!defined('WPSTG_TABLE')) {
                define('WPSTG_TABLE', $wpdb->prefix . 'wp-staging');
            }
        }

        /**
         * Include required files
         *
         * @access private
         * @since 1.0
         * @return void
         */
        private function includes() {
            global $wpstg_options;
                require_once WPSTG_PLUGIN_DIR . 'includes/logger.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/staging-functions.php';
            if (is_admin() || ( defined('WP_CLI') && WP_CLI )) {
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/settings/register-settings.php';
                $wpstg_options = wpstg_get_settings(); // Load it on top of all
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/admin-actions.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/admin-notices.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/admin-footer.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/admin-pages.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/plugins.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/welcome.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/settings/display-settings.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/settings/contextual-help.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/install.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/tools.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/upload-functions.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/scripts.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/class-wpstg-license-handler.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/debug/classes/wpstgDebug.interface.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/debug/classes/wpstgDebug.class.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/wpstg-sanitize.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/template-functions.php';
            }
        }

        /**
         * Loads the plugin language files
         *
         * @access public
         * @since 1.0
         * @return void
         */
        public function load_textdomain() {
            // Set filter for plugin's languages directory
            $wpstg_lang_dir = dirname(plugin_basename(WPSTG_PLUGIN_FILE)) . '/languages/';
            $wpstg_lang_dir = apply_filters('wpstg_languages_directory', $wpstg_lang_dir);

            // Traditional WordPress plugin locale filter
            $locale = apply_filters('plugin_locale', get_locale(), 'wpstg');
            $mofile = sprintf('%1$s-%2$s.mo', 'wpstg', $locale);

            // Setup paths to current locale file
            $mofile_local = $wpstg_lang_dir . $mofile;
            $mofile_global = WP_LANG_DIR . '/wpstg/' . $mofile;

            if (file_exists($mofile_global)) {
                // Look in global /wp-content/languages/WPSTG folder
                load_textdomain('wpstg', $mofile_global);
            } elseif (file_exists($mofile_local)) {
                // Look in local /wp-content/plugins/wp-staging/languages/ folder
                load_textdomain('wpstg', $mofile_local);
            } else {
                // Load the default language files
                load_plugin_textdomain('wpstg', false, $wpstg_lang_dir);
            }
        }

    }

    endif; // End if class_exists check

/**
 * The main function responsible for returning the one true wpstaging
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $WPSTG = WPSTG(); ?>
 *
 * @since 0.9.0
 * @return object The one true wpstaging Instance
 */
function WPSTG() {
    return wpstaging::instance();
}

// Get WPSTG Running
WPSTG();