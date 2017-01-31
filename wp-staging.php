<?php

/**
 * Plugin Name: WP Staging
 * Plugin URI: wordpress.org/plugins/wp-staging
 * Description: Create a staging clone site for testing & developing
 * Author: WP-Staging, René Hermenau
 * Author URI: https://wordpress.org/plugins/wp-staging
 * Version: {{ version }}
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
 */
// Exit if accessed directly
if( !defined( 'ABSPATH' ) )
    exit;

// Plugin version
if( !defined( 'WPSTG_VERSION' ) ) {
    define( 'WPSTG_VERSION', '{{ version }}' );
}
// Is compatible up to WordPress version
if( !defined( 'WPSTG_WP_COMPATIBLE' ) ) {
    define( 'WPSTG_WP_COMPATIBLE', '4.6' );
}

// Plugin Folder Path
if( !defined( 'WPSTG_PLUGIN_DIR' ) ) {
    define( 'WPSTG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin Root File
if( !defined( 'WPSTG_PLUGIN_FILE' ) ) {
    define( 'WPSTG_PLUGIN_FILE', __FILE__ );
}

// Files that needs to be loaded early
if( !class_exists( 'WPSTG_Utils' ) ) {
    require dirname( __FILE__ ) . '/includes/wpstg-utils.php';
}

// Installation & activation
register_activation_hook( __FILE__, 'wpstg_activation' );
function wpstg_activation( $networkwide ) {
    require_once WPSTG_PLUGIN_DIR . '/includes/install.php';

    if( function_exists( 'is_multisite' ) && is_multisite() ) {
        // check if it is a network activation - if so, run the activation function for each blog id
        if( $networkwide ) {
            $old_blog = $wpdb->blogid;
            // Get all blog ids
            $blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
            foreach ( $blogids as $blog_id ) {
                switch_to_blog( $blog_id );
                wpstg_install();
            }
            switch_to_blog( $old_blog );
            return;
        }
    }

    wpstg_install();
}

/*
 * Main class wpstaging
 */
if( !class_exists( 'wpstaging' ) ) :

    /**
     * Main wpstg Class
     *
     * @since 0.9.0
     */
    final class wpstaging {

        /**
         * Main WP Staging __construct
         * 
         * @since 1.0
         * @static
         */
        public function __construct() {
            self::setup_constants();
            self::includes();
            self::load_textdomain();
        }

        /**
         * Setup plugin constants
         *
         * @access private
         * @since 1.0
         * @return void
         */
        private function setup_constants() {
            //global $wpdb;
            // Plugin Folder Path
            if( !defined( 'WPSTG_PLUGIN_DIR' ) ) {
                define( 'WPSTG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
            }

            // Plugin Folder URL
            if( !defined( 'WPSTG_PLUGIN_URL' ) ) {
                define( 'WPSTG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
            }

            if( !defined( 'WPSTG_NAME' ) ) {
                define( 'WPSTG_NAME', 'WP Staging' );
            }

            if( !defined( 'WPSTG_SLUG' ) ) {
                define( 'WPSTG_SLUG', 'wp-staging' );
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
            require_once WPSTG_PLUGIN_DIR . 'includes/scripts.php';
            require_once WPSTG_PLUGIN_DIR . 'includes/staging-functions.php';
            if( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/settings/register-settings.php';
                $wpstg_options = wpstg_get_settings(); // Load it on top of all
                require_once WPSTG_PLUGIN_DIR . 'includes/install.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/admin-actions.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/admin-notices.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/admin-footer.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/admin-pages.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/plugins.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/welcome.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/settings/display-settings.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/settings/contextual-help.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/tools.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/admin/upload-functions.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/class-wpstg-license-handler.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/debug/classes/wpstgDebug.interface.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/debug/classes/wpstgDebug.class.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/wpstg-sanitize.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/template-functions.php';
                require_once WPSTG_PLUGIN_DIR . 'includes/error-handling.php';
            }
        }

        public static function wpstg_activation( $networkwide ) {
            global $wpdb;

            if( function_exists( 'is_multisite' ) && is_multisite() ) {
                // check if it is a network activation - if so, run the activation function for each blog id
                if( $networkwide ) {
                    $old_blog = $wpdb->blogid;
                    // Get all blog ids
                    $blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
                    foreach ( $blogids as $blog_id ) {
                        switch_to_blog( $blog_id );
                        wpstg_install();
                    }
                    switch_to_blog( $old_blog );
                    return;
                }
            }
            wpstg_install();
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
            $wpstg_lang_dir = dirname( plugin_basename( WPSTG_PLUGIN_FILE ) ) . '/languages/';
            $wpstg_lang_dir = apply_filters( 'wpstg_languages_directory', $wpstg_lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'wpstg' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'wpstg', $locale );

            // Setup paths to current locale file
            $mofile_local = $wpstg_lang_dir . $mofile;
            $mofile_global = WP_LANG_DIR . '/wpstg/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/WPSTG folder
                load_textdomain( 'wpstg', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/wp-staging/languages/ folder
                load_textdomain( 'wpstg', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'wpstg', false, $wpstg_lang_dir );
            }
        }

    }

    endif; // End if class_exists check

/**
 * Populate the $wpstg global with an instance of the wpstaging class and return it.
 *
 * @return $wpstg a global instance class of the wpstaging class.
 */
function wp_staging_loaded() {

    global $wpstg;

    if( !is_null( $wpstg ) ) {
        return $wpstg;
    }

    $wpstg = new wpstaging();
    return $wpstg;
    //WPSTG();
}

add_action( 'plugins_loaded', 'wp_staging_loaded' );

/* function WPSTG() {
  global $wpstg;

  if ( !is_null($wpstg) ) {
  return $wpstg;
  }

  $wpstg = new wpstaging();
  return $wpstg;
  } */

// Deactivate WPSTG (Pro)
add_action( 'activated_plugin', array('WPSTG_Utils', 'deactivate_other_instances') );
