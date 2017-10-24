<?php

namespace WPStaging\Backend\Notices;

/*
 *  Admin Notices | Warnings | Messages
 */

// No Direct Access
if( !defined( "WPINC" ) ) {
    die;
}

use WPStaging\WPStaging;

/**
 * Class Notices
 * @package WPStaging\Backend\Notices
 */
class Notices {

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $url;

    public function __construct( $path, $url ) {
        $this->path = $path;
        $this->url = $url;
    }

    /**
     * Check whether the page is an WP QUADS admin settings page or not
     * @return bool
     */
    private function isAdminPage() {
        $currentPage = (isset( $_GET["page"] )) ? $_GET["page"] : null;

        $availablePages = array(
            "wpstg-settings", "wpstg-addons", "wpstg-tools", "wpstg-clone", "wpstg_clone"
        );

        //if( !is_admin() || !did_action( "wp_loaded" ) || !in_array( $currentPage, $availablePages, true ) ) {
        if( !is_admin() || !in_array( $currentPage, $availablePages, true ) ) {
            return false;
        }

        return true;
    }

    /**
     * Check if notice should be shown after certain days of installation
     * @param int $days default 10
     * @return bool
     */
    private function canShow( $option, $days = 10 ) {

        if( empty( $option ) ) {
            return false;
        }

        $installDate = new \DateTime( get_option( "wpstg_installDate" ) );
        $now = new \DateTime( "now" );

        // Get days difference
        $difference = $now->diff( $installDate )->days;

        return ($days <= $difference && "no" !== get_option( $option ));

        return false;
    }

    public function messages() {

      $this->plugin_deactivated_notice();

      // Do not display notices to user_roles lower than 'update_plugins'
      if( !current_user_can( 'update_plugins' ) ) {
         return;
      }
      
      $viewsNoticesPath = "{$this->path}views/_includes/messages/";

      // Show rating review message on all admin pages
      if( $this->canShow( "wpstg_rating", 7 ) ) {
         require_once "{$viewsNoticesPath}rating.php";
      }


      // Display messages below on wp quads admin page only
      if( !$this->isAdminPage() ) {
         return;
      }


      $varsDirectory = \WPStaging\WPStaging::getContentDir();


      // Poll do not show any longer
      /* if( $this->canShow( "wpstg_poll", 7 ) ) {
        require_once "{$viewsNoticesPath}poll.php";
        } */

      // Cache directory in uploads is not writable
      if( !wp_is_writable( $varsDirectory ) ) {
         require_once "{$viewsNoticesPath}/uploads-cache-directory-permission-problem.php";
      }
      // Staging directory is not writable
      if( !wp_is_writable( get_home_path() ) ) {
         require_once "{$viewsNoticesPath}/staging-directory-permission-problem.php";
      }

      // Version Control
      if( version_compare( WPStaging::WP_COMPATIBLE, get_bloginfo( "version" ), "<" ) ) {
         require_once "{$viewsNoticesPath}wp-version-compatible-message.php";
      }

      // Beta
      if( false === get_option( "wpstg_beta" ) || "no" !== get_option( "wpstg_beta" ) ) {
         require_once "{$viewsNoticesPath}beta.php";
      }

      // WP Staging Pro and Free can not be activated both
      if( false !== ( $deactivatedNoticeID = get_transient( "wp_staging_deactivated_notice_id" ) ) ) {
         require_once "{$viewsNoticesPath}transient.php";
         delete_transient( "wp_staging_deactivated_notice_id" );
      }
   }

   /**
     * Show a message when pro or free plugin becomes deactivated
     * 
     * @return void
     */
    private function plugin_deactivated_notice() {
        if( false !== ( $deactivated_notice_id = get_transient( 'wp_staging_deactivated_notice_id' ) ) ) {
            if( '1' === $deactivated_notice_id ) {
                $message = __( "WP Staging and WP Staging Pro cannot both be active. We've automatically deactivated WP Staging.", 'wpstg' );
            } else {
                $message = __( "WP Staging and WP Staging Pro cannot both be active. We've automatically deactivated WP Staging Pro.", 'wpstg' );
            }
            ?>
            <div class="updated notice is-dismissible" style="border-left: 4px solid #ffba00;">
                <p><?php echo esc_html( $message ); ?></p>
            </div> <?php
            delete_transient( 'wp_staging_deactivated_notice_id' );
        }
    }

}