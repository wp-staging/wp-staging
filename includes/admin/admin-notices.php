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
 * @since 0.9.0
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_admin_messages() {
	global $wpstg_options;
        
        // show admin notices only to admins
        if( !current_user_can( 'update_plugins' ) ) {
            return;
        }
        
        wpstg_start_poll();
        
        if ( wpstg_is_admin_page() && !wp_is_writable( wpstg_get_upload_dir() ) ){
            echo '<div class="error">';
			echo '<p><strong>WP Staging Folder Permission error: </strong>' . wpstg_get_upload_dir() . ' is not write and/or readable. <br> Check if the folder <strong>'.wpstg_get_upload_dir().'</strong> exists! File permissions should be chmod 755 or 777.</p>';
		echo '</div>';
        }
        if ( wpstg_is_admin_page() && !wp_is_writable( WPSTG_PLUGIN_DIR . 'logs' ) ){
            echo '<div class="error">';
			echo '<p><strong>WP Staging Folder Permission error: </strong>' . WPSTG_PLUGIN_DIR . 'logs' . ' is not write and/or readable. <br> Check if the folder <strong>'.WPSTG_PLUGIN_DIR . 'logs'.'</strong> exists! File permissions should be chmod 755 or 777.</p>';
		echo '</div>';
        }
        $path = wpstg_get_upload_dir() . '/clone_details.json';
        if ( wpstg_is_admin_page() && !wpstg_clonedetailsjson_exists() || !is_readable( $path ) ){
            echo '<div class="error">';
			echo '<p><strong>WP Staging File Permission error: </strong>' . $path . ' is not write and/or readable. <br> Check if the file <strong>'.$path.'</strong> exists! File permissions should be chmod 644.</p>';
		echo '</div>';
        }
         $path = wpstg_get_upload_dir() . '/remaining_files.json';
         if ( wpstg_is_admin_page() && !wpstg_remainingjson_exists() || !is_readable( $path ) ){
            echo '<div class="error">';
			echo '<p><strong>WP Staging File Permission error: </strong>' . $path . ' is not write and/or readable . <br> Check if the file <strong>'.$path.'</strong> exists! File permissions should be chmod 644.</p>';
		echo '</div>';
        }
             if ( wpstg_is_admin_page() && version_compare( WPSTG_WP_COMPATIBLE, get_bloginfo('version'), '<' )){
        echo '<div class="error"><p>';
        echo sprintf( __('You are using a version of WP Staging which has not been tested with your WordPress version %2$s.<br> 
            As WP Staging is using crucial db and file functions it\'s important that you are using a WP Staging version<br> 
            which has been verified to be working with your WordPress version. You risk unexpected results up to data lose if you do not so.
            <p>Please look at <a href="%1$s" target="_blank">%s</a> for the latest WP Staging version.', 'wpstg') ,
                'https://wordpress.org/plugins/wp-staging/',
                get_bloginfo('version')
                );
        echo '</p></div>';
        }

        echo wpstg_show_beta_message();
        wpstg_plugin_deactivated_notice();
        
        $install_date = get_option('wpstg_installDate');
        $display_date = date('Y-m-d h:i:s');
	$datetime1 = new DateTime($install_date);
	$datetime2 = new DateTime($display_date);
	$diff_intrval = round(($datetime2->format('U') - $datetime1->format('U')) / (60*60*24));

        if($diff_intrval >= 7 && get_option('wpstg_RatingDiv')=="no")
    {
	 echo '<div class="wpstg_fivestar updated" style="box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);">
    	<p>Awesome, you\'ve been using <strong>WP Staging </strong> for more than 1 week. May i ask you to give it a <strong>5-star</strong> rating on Wordpress? 
        <p><strong>Regards,<br>René Hermenau</strong>
        <ul>
            <li><a href="https://wordpress.org/support/plugin/wp-staging/reviews/?filter=5#new-post" class="thankyou" target="_new" title="Ok, you deserved it" style="font-weight:bold;">Ok, you deserved it</a></li>
            <li><a href="javascript:void(0);" class="wpstg_hide_rating" title="I already did" style="font-weight:bold;">I already did</a></li>
            <li><a href="javascript:void(0);" class="wpstg_hide_rating" title="No, not good enough" style="font-weight:bold;">No, not good enough</a></li>
        </ul>
    </div>
    <script>
    jQuery( document ).ready(function( $ ) {
        jQuery(\'.wpstg_hide_rating\').click(function(){
                 var data={\'action\':\'wpstg_hide_rating\'}
                jQuery.ajax({
                    url: "'.admin_url( 'admin-ajax.php' ).'",
                    type: "post",
                    data: data,
                    dataType: "json",
                    async: !0,
                    success: function(e) {
                        if (e=="success") {
                           jQuery(\'.wpstg_fivestar\').slideUp(\'fast\');
                        }
                    }
                });
        })
        jQuery(\'.wpstg_hide_beta\').click(function(){
                 var data={\'action\':\'wpstg_hide_beta\'}
                jQuery.ajax({
                    url: "'.admin_url( 'admin-ajax.php' ).'",
                    type: "post",
                    data: data,
                    dataType: "json",
                    async: !0,
                    success: function(e) {
                        if (e=="success") {
                           jQuery(\'.wpstg_beta_notice\').slideUp(\'fast\');
                        }
                    }
                });
        })
    });
    </script>
    ';
    }
}
add_action( 'admin_notices', 'wpstg_admin_messages' );

/**
 * Ask for a doing a poll
 */
function wpstg_start_poll(){
    
        $install_date = get_option('wpstg_installDate');
        $display_date = date('Y-m-d h:i:s');
	$datetime1 = new DateTime($install_date);
	$datetime2 = new DateTime($display_date);
	$diff_intrval = round(($datetime2->format('U') - $datetime1->format('U')) / (60*60*24));
   
        if($diff_intrval >= 10 && get_option('wpstg_start_poll') !== 'no')
    {
	 echo '<div class="wpstg_poll update-nag" style="box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);">
    	<p>Great, You are using <strong>WP Staging</strong> for a while. Hope you are happy with it.<br><br>Are you interested in copying changes from WPStaging staging site back to your live site?
        <br><br>Click on the <a href="" target="_blank"><i>Yes, i am interested</i></a> Button and fill out the poll!<br>It only takes one (1) minute of your time - I promise!
        <br><br>Cheers,<br>René
        <ul>
            <li class="float:left"><a href="https://docs.google.com/forms/d/e/1FAIpQLScZ-dO5WffV3xObn16LwG05tr1HrADD_8L4wbTxPHqoPssVcg/viewform?c=0&w=1&usp=mail_form_link" class="thankyou button button-primary" target="_new" title=Yes, i am interested" style="color: #ffffff;font-weight: normal;margin-right:10px;float:left;">Yes, i am interested</a></li>
            <li><a href="javascript:void(0);" class="wpstg_hide_poll" title="Close It" style="vertical-align:middle;">Do Not Ask Again</a></li>
        </ul>
    </div>
    <script>
    jQuery( document ).ready(function( $ ) {
        jQuery(\'.wpstg_hide_poll\').click(function(){
                 var data={\'action\':\'wpstg_hide_poll\'}
                jQuery.ajax({
                    url: "'.admin_url( 'admin-ajax.php' ).'",
                    type: "post",
                    data: data,
                    dataType: "json",
                    async: !0,
                    success: function(e) {
                        if (e=="success") {
                           jQuery(\'.wpstg_poll\').slideUp(\'fast\');
                        }
                    }
                });
        })
    });
    </script>
    ';
    }
}


/* Hide the poll notice
 * 
 * @subpackage  Admin/Notices
 * @copyright   Copyright (c) 2016, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.1.0
 * 
 * @return json string
 * 
 */

function wpstg_hide_poll(){
    update_option('wpstg_start_poll','no');
    echo json_encode(array("success")); exit;
}
add_action('wp_ajax_wpstg_hide_poll','wpstg_hide_poll');


/**
 * Show a message when pro or free plugin become disabled
 * 
 * @return void
 */
function wpstg_plugin_deactivated_notice() {
    if (false !== ( $deactivated_notice_id = get_transient('wp_staging_deactivated_notice_id') )) {
        if ('1' === $deactivated_notice_id) {
            $message = __("WP Staging and WP Staging Pro cannot both be active. We've automatically deactivated WP Staging.", 'wpstg');
        } else {
            $message = __("WP Staging and WP Staging Pro cannot both be active. We've automatically deactivated WP Staging Pro.", 'wpstg');
        }
?>
        <div class="updated notice is-dismissible" style="border-left: 4px solid #ffba00;">
                <p><?php echo esc_html($message); ?></p>
        </div> <?php
        delete_transient('wp_staging_deactivated_notice_id');
    }
}

/* Hide the rating div
 * 
 * @subpackage  Admin/Notices
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.9.0
 * 
 * @return json string
 * 
 */

function wpstg_hide_rating_div(){
    update_option('wpstg_RatingDiv','yes');
    echo json_encode(array("success")); exit;
}
add_action('wp_ajax_wpstg_hide_rating','wpstg_hide_rating_div');

/**
 * Admin Add-ons Notices
 *
 * @since 0.9.0
 * @return void
*/
function wpstg_admin_addons_notices() {
	add_settings_error( 'wpstg-notices', 'wpstg-addons-feed-error', __( 'There seems to be an issue with the server. Please try again in a few minutes.', 'wpstg' ), 'error' );
	settings_errors( 'wpstg-notices' );
}

/**
 * Dismisses admin notices when Dismiss links are clicked
 *
 * @since 0.9.0
 * @return void
*/
function wpstg_dismiss_notices() {

	if( ! is_user_logged_in() ) {
		return;
	}

	$notice = isset( $_GET['wpstg_notice'] ) ? $_GET['wpstg_notice'] : false;

	if( ! $notice )
		return; // No notice, so get out of here

	update_user_meta( get_current_user_id(), '_wpstg_' . $notice . '_dismissed', 1 );
	wp_redirect( remove_query_arg( array( 'wpstg_action', 'wpstg_notice' ) ) ); exit;

}
add_action( 'wpstg_dismiss_notices', 'wpstg_dismiss_notices' );


/*
 * Show big colored update information below the official update notification in /wp-admin/plugins
 * @since 0.9.0
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
        $regexp         = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( WPSTG_VERSION ) . '\s*=|$)~Uis';
        $upgrade_notice = '';

        if ( preg_match( $regexp, $response['body'], $matches ) ) {
          $version        = trim( $matches[1] );
          $notices        = (array) preg_split('~[\r\n]+~', trim( $matches[2] ) );
          
          if ( version_compare( WPSTG_VERSION, $version, '<' ) ) {

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
 
 /**
  * Show a admin notice that this software is beta 
  */
 function wpstg_show_beta_message(){
     	 $notice = '<div class="wpstg_beta_notice error" style="box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);">
    	<p>WP Staging is well tested and i did my best to catch every possible error i can forecast but i can not handle all possible combinations of different server, plugins and themes. <br><strong>BEFORE</strong> you create your first staging site it´s highly recommended <strong>to make a full backup of your website</strong> first!
      <p>
        One of the best free plugins for an entire wordpress backup is the free one <a href="https://wordpress.org/plugins/backwpup/" target="_blank">BackWPup</a> 
        <p>I am not responsible for any damages this plugin will cause to your site. <br>Do a full backup first!</p>
        <ul>
            <li><a href="javascript:void(0);" class="wpstg_hide_beta" title="I understand" style="font-weight:bold;color:#00a0d2;">I understand! (Do not show this again)</a></li>
        </ul>
    </div>
    <script>
    jQuery( document ).ready(function( $ ) {
        jQuery(\'.wpstg_hide_beta\').click(function(){
                 var data={\'action\':\'wpstg_hide_beta\'}
                jQuery.ajax({
                    url: "'.admin_url( 'admin-ajax.php' ).'",
                    type: "post",
                    data: data,
                    dataType: "json",
                    async: !0,
                    success: function(e) {
                        if (e=="success") {
                           jQuery(\'.wpstg_beta_notice\').slideUp(\'slow\');
                        }
                    }
                });
        })
    });
    </script>
    ';
   
    if( get_option('wpstg_hide_beta') === "no" && wpstg_is_admin_page() )
         return $notice;     
 }
 
 function wpstg_hide_beta_div(){
         update_option('wpstg_hide_beta','yes');
         echo json_encode(array("success")); exit;
 }
 add_action('wp_ajax_wpstg_hide_beta','wpstg_hide_beta_div');