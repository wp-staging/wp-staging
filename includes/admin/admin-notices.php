<?php
/**
 * Admin Notices
 *
 * @package     WPSTG
 * @subpackage  Admin/Notices
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin Messages
 *
 * @since 1.0.0
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_admin_messages() {
	global $wpstg_options;
        
        $install_date = get_option('wpstg_installDate');
        $display_date = date('Y-m-d h:i:s');
	$datetime1 = new DateTime($install_date);
	$datetime2 = new DateTime($display_date);
	$diff_intrval = round(($datetime2->format('U') - $datetime1->format('U')) / (60*60*24));
        if($diff_intrval >= 7 && get_option('wpstg_RatingDiv')=="no")
    {
	 echo '<div class="wpstg_fivestar" style="box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);">
    	<p>Awesome, you\'ve been using <strong>WP-Staging Social Sharing</strong> for more than 1 week. May we ask you to give it a <strong>5-star</strong> rating on Wordpress? 
        <br><strong>Your WP-Staging Team</strong>
        <ul>
        	<li><a href="https://wordpress.org/support/view/plugin-reviews/wp-staging" class="thankyou" target="_new" title="Ok, you deserved it" style="font-weight:bold;">Ok, you deserved it</a></li>
            <li><a href="javascript:void(0);" class="wpstgHideRating" title="I already did" style="font-weight:bold;">I already did</a></li>
            <li><a href="javascript:void(0);" class="wpstgHideRating" title="No, not good enough" style="font-weight:bold;">No, not good enough</a></li>
        </ul>
    </div>
    <script>
    jQuery( document ).ready(function( $ ) {

    jQuery(\'.wpstgHideRating\').click(function(){
        var data={\'action\':\'hideRating\'}
             jQuery.ajax({
        
        url: "'.admin_url( 'admin-ajax.php' ).'",
        type: "post",
        data: data,
        dataType: "json",
        async: !0,
        success: function(e) {
            if (e=="success") {
               jQuery(\'.wpstg_fivestar\').slideUp(\'slow\');
			   
            }
        }
         });
        })
    
    });
    </script>
    ';
    }
}
//add_action( 'admin_notices', 'wpstg_admin_messages' );

/* Hide the rating div
 * 
 * @subpackage  Admin/Notices
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 * 
 * @return json string
 * 
 */

function wpstg_HideRatingDiv(){
    update_option('wpstg_RatingDiv','yes');
    echo json_encode(array("success")); exit;
}
add_action('wp_ajax_hideRating','wpstg_HideRatingDiv');

/**
 * Admin Add-ons Notices
 *
 * @since 1.0.0
 * @return void
*/
function wpstg_admin_addons_notices() {
	add_settings_error( 'wpstg-notices', 'wpstg-addons-feed-error', __( 'There seems to be an issue with the server. Please try again in a few minutes.', 'wpstg' ), 'error' );
	settings_errors( 'wpstg-notices' );
}

/**
 * Dismisses admin notices when Dismiss links are clicked
 *
 * @since 1.0.0
 * @return void
*/
function wpstg_dismiss_notices() {

	$notice = isset( $_GET['wpstg_notice'] ) ? $_GET['wpstg_notice'] : false;
	if( ! $notice )
		return; // No notice, so get out of here

	update_user_meta( get_current_user_id(), '_wpstg_' . $notice . '_dismissed', 1 );
      
	wp_redirect( esc_url(remove_query_arg( array( 'wpstg_action', 'wpstg_notice' ) ) ) ); exit;

}
add_action( 'wpstg_dismiss_notices', 'wpstg_dismiss_notices' );

/*
 * Show big colored update information below the official update notification in /wp-admin/plugins
 * @since 1.0.0
 * @return void
 * 
 */

function wpstg_plugin_update_message( $args ) {
    $transient_name = 'wpstg_upgrade_notice_' . $args['Version'];

    if ( false === ( $upgrade_notice = get_transient( $transient_name ) ) ) {

      $response = wp_remote_get( 'https://plugins.svn.wordpress.org/wp-staging/trunk/readme.txt' );

      if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {

        // Output Upgrade Notice
        $matches        = null;
        $regexp         = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( WC_VERSION ) . '\s*=|$)~Uis';
        $upgrade_notice = '';

        if ( preg_match( $regexp, $response['body'], $matches ) ) {
          $version        = trim( $matches[1] );
          $notices        = (array) preg_split('~[\r\n]+~', trim( $matches[2] ) );
          
          if ( version_compare( WC_VERSION, $version, '<' ) ) {

            $upgrade_notice .= '<div class="wpstg_plugin_upgrade_notice" style="padding:10px;background-color: #479CCF;color: #FFF;">';

            foreach ( $notices as $index => $line ) {
              $upgrade_notice .= wp_kses_post( preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}" style="text-decoration:underline;color:#ffffff;">${1}</a>', $line ) );
            }

            $upgrade_notice .= '</div> ';
          }
        }

        set_transient( $transient_name, $upgrade_notice, DAY_IN_SECONDS );
      }
    }

    echo wp_kses_post( $upgrade_notice );
  }
 add_action ( "in_plugin_update_message-wp-staging/wp-staging.php", 'wpstg_plugin_update_message'  );