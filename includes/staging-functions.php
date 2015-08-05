<?php
/**
 * Staging functions
 *
 * @package     WPSTG
 * @subpackage  includes/staging-functions
 * @copyright   Copyright (c) 2015, Rene Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.9.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Check if website is a clone or not. 
 * If it' s a clone website we allow access to the frontend only for administrators.
 * 
 * At init() stage most of WP is loaded, and the user is authenticated. 
 * Get the wpstg_options via get_option because our global $wpstg_option is only available on admin pages
 * 
 * @return string wp_die()
 */
function wpstg_staging_permissions(){
    $wpstg_options = get_option( 'wpstg_settings' );
    $wpstg_disable_admin_login = isset($wpstg_options['disable_admin_login']) ? $wpstg_options['disable_admin_login'] : 0;

    if ( wpstg_is_staging_site() && $wpstg_disable_admin_login === 0){
        if ( !current_user_can( 'administrator' ) && !wpstg_is_login_page() && !is_admin() )
        //wp_die( sprintf ( __('Access denied. <a href="%1$s" target="_blank">Login</a> first','wpstg'), './wp-admin/' ) );
        wp_die( sprintf ( __('Access denied. <a href="%1$s" target="_blank">Login</a> first','wpstg'), wp_login_url()  ) ); 
	}
    wpstg_reset_permalinks();
}
add_action( 'init', 'wpstg_staging_permissions' );

/**
 * Inject custom header for staging website
 * 
 * @deprecated since version 0.2
 */
/*function wpstg_inject_header(){
    if ( !wpstg_is_staging_site() ) {
        ?>
        <script type="text/javascript">
            jQuery(document).ready( function($) {
            var struct='<div id="wpstg_staging_header" style="display:block;position:fixed;background-color:#c12161;color:#fff;height:32px;top:0;left:0;width:100%;">Staging website!</div>';
            function 
            jQuery('body').append(struct);
        })
        </script>
            <?php
    }
}*/
//add_action('wp_head','wpstg_inject_header');

/**
 * Change admin_bar site_name
 * 
 * @global object $wp_admin_bar
 * @return void
 */
function wpstg_change_adminbar_name() {
    global $wp_admin_bar;
    if (wpstg_is_staging_site()) {
        // Main Title
        $wp_admin_bar->add_menu(array(
            'id' => 'site-name',
            'title' => is_admin() ? ('STAGING - ' . get_bloginfo( 'name' ) ) : ( 'STAGING ' . get_bloginfo( 'name' ) . ' Dashboard' ),
            'href' => is_admin() ? home_url('/') : admin_url(),
        ));
    }
}
add_filter('wp_before_admin_bar_render', 'wpstg_change_adminbar_name');

/**
 * Check if current wordpress instance is the main site or a clone
 * 
 * @global array $wpstg_options options
 * @return bool true if current website is a staging website
 */
function wpstg_is_staging_site(){
    $is_staging_site = get_option('wpstg_is_staging_site') ? get_option('wpstg_is_staging_site') : 'false';
    if ($is_staging_site === 'true'){
        return true;
    }
}

/**
 * Reset permalink structure of the clone to default index.php/p=123
 * This is used once
 * @global array $wpstg_options options
 */
function wpstg_reset_permalinks(){
    global $wp_rewrite;
    $permalink_structure = null;
    $already_executed = get_option('wpstg_rmpermalinks_executed') ? get_option('wpstg_rmpermalinks_executed') : 'false';
    if (wpstg_is_staging_site() && $already_executed !== 'true' ){ 
        $wp_rewrite->set_permalink_structure( $permalink_structure );
        flush_rewrite_rules();
        update_option('wpstg_rmpermalinks_executed', 'true');
    }
}

/**
 * Check if current page is a login page
 * 
 * @return bool true if page is login page
 */
function wpstg_is_login_page() {
    global $wpstg_options;
    //$wpstg_login_page = isset($wpstg_options['admin_login_page']) ? $wpstg_options['admin_login_page'] : '';
      return in_array( $GLOBALS['pagenow'], array('wp-login.php') );
}