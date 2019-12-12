<?php

/*
 * Plugin Name: WP Staging Optimizer
 * Plugin URI: https://wp-staging.com
 * Description: Prevents 3rd party plugins from being loaded during WP Staging specific operations.
 *
 * This is a must-use standalone plugin -
 * Do not use any of these methods in wp staging code base as this plugin can be missing!
 *
 * Author: René Hermenau
 * Version: 1.2
 * Author URI: https://wp-staging.com
 * Credit: Original version made by Delicious Brains (WP Migrate DB). Thank you guys!
 */


if( !defined( 'WPSTG_OPTIMIZER_VERSION' ) ) {
    define( 'WPSTG_OPTIMIZER_VERSION', 1.1 );
}

/**
 * Get plugins dir 
 * @return string
 * @todo Logic error here. If WP_CONTENT_DIR or WP_PLUGIN_DIR do not exists no value is returned.
 * Does not throw error because WP_PLUGIN_DIR is defined with default value in WP core.
 */
function wpstg_get_plugins_dir()
{

    if (defined('WP_PLUGIN_DIR')) {
	$pluginsDir = trailingslashit(WP_PLUGIN_DIR);
    } else if (defined('WP_CONTENT_DIR')) {
	$pluginsDir = trailingslashit(WP_CONTENT_DIR).'plugins/';
    }
    return $pluginsDir;
}
/*
 * Check if optimizer is enabled
 * @return bool false if it's disabled
 *
 */

function wpstg_is_enabled_optimizer() {
    $status = ( object ) get_option( 'wpstg_settings' );

   if( $status && isset( $status->optimizer ) && $status->optimizer == 1 ) {
      return true;
   }
   // Activate the Optimizer all the times. 
   // Until now we never had any issue with the Optimizer so its default state is activated
   return true;
}

/**
 * remove all plugins except wp-staging and wp-staging-pro from blog-active plugins
 *
 * @param array $plugins numerically keyed array of plugin names
 *
 * @return array
 */
function wpstg_exclude_plugins( $plugins ) {
   if( !is_array( $plugins ) || empty( $plugins ) ) {
      return $plugins;
   }

   if( !wpstg_is_compatibility_mode_request() ) {
      return $plugins;
   }

   foreach ( $plugins as $key => $plugin ) {
      if( false !== strpos( $plugin, 'wp-staging' ) ) {
         continue;
      }
      unset( $plugins[$key] );
   }

   return $plugins;
}

add_filter( 'option_active_plugins', 'wpstg_exclude_plugins' );

/**
 *
 * Disables the theme during WP Staging AJAX requests
 *
 *
 * @param $dir
 *
 * @return string
 */
function wpstg_disable_theme( $dir ) {
   $enableTheme = apply_filters( 'wpstg_optimizer_enable_theme', false );

   if( wpstg_is_compatibility_mode_request() && false === $enableTheme ) {
      $wpstgRootPro = wpstg_get_plugins_dir() . 'wp-staging-pro';
      $wpstgRoot = wpstg_get_plugins_dir() . 'wp-staging';

      $file = DIRECTORY_SEPARATOR . 'Backend' . DIRECTORY_SEPARATOR . 'Optimizer' . DIRECTORY_SEPARATOR . 'blank-theme' . DIRECTORY_SEPARATOR . 'functions.php';
      $theme = DIRECTORY_SEPARATOR . 'Backend' . DIRECTORY_SEPARATOR . 'Optimizer' . DIRECTORY_SEPARATOR . 'blank-theme';


      if( file_exists( $wpstgRoot . $file ) ) {
         return $wpstgRoot . $theme;
      } elseif( file_exists( $wpstgRootPro . $file ) ) {
         return $wpstgRootPro . $theme;
      } else {
         return '';
      }
      return $themeDir;
   }

   return $dir;
}

add_filter( 'stylesheet_directory', 'wpstg_disable_theme' );
add_filter( 'template_directory', 'wpstg_disable_theme' );

/**
 * remove all plugins except wp-staging and wp-staging-pro from network-active plugins
 *
 * @param array $plugins array of plugins keyed by name (name=>timestamp pairs)
 *
 * @return array
 */
function wpstg_exclude_site_plugins( $plugins ) {
   if( !is_array( $plugins ) || empty( $plugins ) ) {
      return $plugins;
   }

   if( !wpstg_is_compatibility_mode_request() ) {
      return $plugins;
   }


   foreach ( array_keys( $plugins ) as $plugin ) {
      if( false !== strpos( $plugin, 'wp-staging' ) || !isset( $blacklist_plugins[$plugin] ) ) {
         continue;
      }
      unset( $plugins[$plugin] );
   }

   return $plugins;
}

add_filter( 'site_option_active_sitewide_plugins', 'wpstg_exclude_site_plugins' );

/**
 * Should the current request be processed by Compatibility Mode?
 *
 * @return bool
 */
function wpstg_is_compatibility_mode_request() {

   // Optimizer not enabled  
   if( !wpstg_is_enabled_optimizer() ) {
      return false;
   }

   if( !defined( 'DOING_AJAX' ) ||
           !DOING_AJAX ||
           !isset( $_POST['action'] ) ||
           false === strpos( $_POST['action'], 'wpstg' )
   ) {

      return false;
   }

   return true;
}

/**
 * Remove TGM Plugin Activation 'force_activation' admin_init action hook if present.
 *
 * This is to stop excluded plugins being deactivated after a migration, when a theme uses TGMPA to require a plugin to be always active.
 */
function wpstg_tgmpa_compatibility() {
   $remove_function = false;

   // run on wpstg page
   if( isset( $_GET['page'] ) && 'wpstg_clone' == $_GET['page'] ) {
      $remove_function = true;
   }
   // run on wpstg ajax requests
   if( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_POST['action'] ) && false !== strpos( $_POST['action'], 'wpstg' ) ) {
      $remove_function = true;
   }

   if( $remove_function ) {
      global $wp_filter;
      $admin_init_functions = $wp_filter['admin_init'];
      foreach ( $admin_init_functions as $priority => $functions ) {
         foreach ( $functions as $key => $function ) {
            // searching for function this way as can't rely on the calling class being named TGM_Plugin_Activation
            if( false !== strpos( $key, 'force_activation' ) ) {
               unset( $wp_filter['admin_init'][$priority][$key] );

               return;
            }
         }
      }
   }
}

add_action( 'admin_init', 'wpstg_tgmpa_compatibility', 1 );
