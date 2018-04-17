<?php

namespace WPStaging\Frontend;

use WPStaging\DI\InjectionAware;
use WPStaging\Frontend\loginForm;

/**
 * Class Frontend
 * @package WPStaging\Frontend
 */
class Frontend extends InjectionAware {

   /**
    * @var object
    */
   private $settings;
   
   /**
    *
    * @var string
    */
   private $loginSlug;

   /**
    * Frontend initialization.
    */
   public function initialize() {
      $this->defineHooks();

      $this->settings = json_decode( json_encode( get_option( "wpstg_settings", array() ) ) );
      
      $this->loginSlug = isset( $this->settings->loginSlug ) ? $this->settings->loginSlug : '';
   }

   /**
    * Define Hooks
    */
   private function defineHooks() {
      // Get loader
      $loader = $this->di->get( "loader" );
      $loader->addAction( "init", $this, "checkPermissions" );
      $loader->addFilter( "wp_before_admin_bar_render", $this, "changeSiteName" );
   }

   /**
    * Change admin_bar site_name
    * 
    * @global object $wp_admin_bar
    * @return void
    */
   public function changeSiteName() {
      global $wp_admin_bar;
      if( $this->isStagingSite() ) {
         // Main Title
         $wp_admin_bar->add_menu( array(
             'id' => 'site-name',
             'title' => is_admin() ? ('STAGING - ' . get_bloginfo( 'name' ) ) : ( 'STAGING - ' . get_bloginfo( 'name' ) . ' Dashboard' ),
             'href' => is_admin() ? home_url( '/' ) : admin_url(),
         ) );
      }
   }

   /**
    * Check permissions for the page to decide whether or not to disable the page
    */
//   public function checkPermissions() {
//      $this->resetPermaLinks();
//
//      if( $this->disableLogin() ) {
//         wp_die( sprintf( __( 'Access denied. <a href="%1$s">Login</a> first to access this site', 'wpstg' ), $this->getLoginUrl() ) );
//      }
//   }
   public function checkPermissions() {
      $this->resetPermaLinks();

      if($this->disableLogin() ) {
         //wp_die( sprintf( __( 'Access denied. <a href="%1$s">Login</a> first to access this site', 'wpstg' ), $this->getLoginUrl() ) );
         //}
         	$args = array(
		'echo' => true,
		// Default 'redirect' value takes the user back to the request URI.
		'redirect' => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		'form_id' => 'loginform',
		'label_username' => __( 'Username or Email Address' ),
		'label_password' => __( 'Password' ),
		'label_remember' => __( 'Remember Me' ),
		'label_log_in' => __( 'Log In' ),
		'id_username' => 'user_login',
		'id_password' => 'user_pass',
		'id_remember' => 'rememberme',
		'id_submit' => 'wp-submit',
		'remember' => true,
		'value_username' => '',
		// Set 'value_remember' to true to default the "Remember me" checkbox to checked.
		'value_remember' => false,
                  );


         /**
          * Lines below are not used at the moment but are fully functional
          */
         $login = new loginForm();
         $login->renderForm($args);
         die();
      }
   }

   /**
    * Get login link
    * @return string
    */
//   private function getLoginUrl() {
//      
//      if( empty( $this->loginSlug ) ) {
//         //return get_home_url() . '/wp-admin';
//         return get_site_url() . '/wp-admin';
//
//      }
//
//      return get_home_url() . '/?' . $this->loginSlug;
//   }
   /**
    * Get path to wp-login.php
    * @return string
    */
   private function getLoginUrl() {
      return get_site_url() . '/wp-login.php';
      }

   /**
    * Check if the page should be blocked
    * @return bool
    */
   private function disableLogin() {
      // Is not staging site
      if( !$this->isStagingSite() ) {
         return false;
      }

      // Allow access for user role administrator in any case
      if( current_user_can( 'administrator' ) ) {
         return false;
      }

      return (
              (!isset( $this->settings->disableAdminLogin ) || '1' !== $this->settings->disableAdminLogin) &&
              (!current_user_can( "administrator" ) && !$this->isLoginPage() && !is_admin())
              );
   }

   /**
    * Check if it is a staging site
    * @return bool
    */
   private function isStagingSite() {
      return ("true" === get_option( "wpstg_is_staging_site" ));
   }

   /**
    * Check if it is the login page
    * @return bool
    */
   private function isLoginPage() {
      
      return (
              in_array( $GLOBALS["pagenow"], array("wp-login.php") ) ||
              in_array( $this->loginSlug, $_GET ) ||
              array_key_exists( $this->loginSlug, $_GET )
              );
   }

   /**
    * Reset permalink structure of the clone to default; index.php?p=123
    */
   private function resetPermaLinks() {
      if( !$this->isStagingSite() || "true" === get_option( "wpstg_rmpermalinks_executed" ) ) {
         return;
      }
      // $wp_rewrite is not available before the init hook. So we need to use the global declaration
      global $wp_rewrite;
      $wp_rewrite->set_permalink_structure( null );

      flush_rewrite_rules();

      update_option( "wpstg_rmpermalinks_executed", "true" );
   }

}
