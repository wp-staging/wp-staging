<?php

namespace WPStaging\Backend\Notices;

/*
 *  Admin Notices | Warnings | Messages
 */

// No Direct Access
if( !defined( "WPINC" ) ) {
    die;
}

use WPStaging\Backend\Pro\Notices\Notices as ProNotices;

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
        $this->url  = $url;
    }

    /**
     * Check whether the page is admin page or not
     * @return bool
     */
    public function isAdminPage() {
        $currentPage = (isset( $_GET["page"] )) ? $_GET["page"] : null;

        $availablePages = array(
            "wpstg-settings", "wpstg-addons", "wpstg-tools", "wpstg-clone", "wpstg_clone"
        );

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

        // Do not show notice
        if( empty( $option ) ) {
            return false;
        }

        $dbOption = get_option( $option );

        // Do not show notice
        if ("no" === $dbOption){
            return false;
        }

        $now = new \DateTime( "now" );

        // Check if user clicked on "rate later" button and if there is a valid 'later' date
        if( wpstg_is_valid_date( $dbOption ) ) {
            // Do not show before this date
            $show = new \DateTime( $dbOption );
            if ($now < $show){
                return false;
            }
        }


        // Show X days after installation
        $installDate = new \DateTime( get_option( "wpstg_installDate" ) );

        // get number of days between installation date and today
        $difference = $now->diff( $installDate )->days;

        if( $days <= $difference ) {
            return true;
        }


        return false;
    }
    /**
     * Get current page
     * @return string | post, page
     */
    private function getCurrentScreen() {
        if( function_exists( 'get_current_screen' ) ) {
            return \get_current_screen()->post_type;
        }
    }

    /**
     * Load admin notices
     * @return string
     */
    public function messages() {

        $viewsNoticesPath = "{$this->path}views/notices/";

        // Free and pro version have been activated at the same time
        $this->plugin_deactivated_notice();

        // Show "rate the plugin". Free version only
        if (!defined('WPSTGPRO_VERSION')) {
            if ($this->canShow("wpstg_rating", 7)  && $this->getCurrentScreen() !== 'page' && $this->getCurrentScreen() !== 'post') {
                require_once "{$viewsNoticesPath}rating.php";
            }

        }

        // Show all pro version notices
        if (defined('WPSTGPRO_VERSION')) {
            $proNotices = new ProNotices($this);
            $proNotices->getNotices();
        }

        // Display notices below in wp staging admin pages only
        if( !current_user_can( "update_plugins" ) || !$this->isAdminPage() ) {
            return;
        }

        // Cache directory in uploads is not writable
        $varsDirectory = \WPStaging\WPStaging::getContentDir();
        if( !is_writable( $varsDirectory ) ) {
            require_once "{$viewsNoticesPath}/uploads-cache-directory-permission-problem.php";
        }

        // Staging directory is not writable
        if( !is_writeable( ABSPATH ) ) {
            require_once "{$viewsNoticesPath}/staging-directory-permission-problem.php";
        }

        // Version Control
        if( version_compare(  WPSTG_COMPATIBLE, get_bloginfo( "version" ), "<" ) ) {
            require_once "{$viewsNoticesPath}wp-version-compatible-message.php";
        }

        // Beta
        /*if( false === get_option( "wpstg_beta" ) || "no" !== get_option( "wpstg_beta" ) ) {
            require_once "{$viewsNoticesPath}beta.php";
        }*/

        // WP Staging Pro and Free can not be activated both
        if( false !== ( $deactivatedNoticeID = get_transient( "wp_staging_deactivated_notice_id" ) ) ) {
            require_once "{$viewsNoticesPath}transient.php";
            delete_transient( "wp_staging_deactivated_notice_id" );
        }

        // Different scheme in home and siteurl
        if( $this->isDifferentScheme() ) {
            require_once "{$viewsNoticesPath}wrong-scheme.php";
        }
    }

    /**
     * Check if the url scheme of siteurl and home is identical
     * @return boolean
     */
    private function isDifferentScheme() {
        $siteurlScheme = parse_url( get_option( 'siteurl' ), PHP_URL_SCHEME );
        $homeScheme    = parse_url( get_option( 'home' ), PHP_URL_SCHEME );

        if( $siteurlScheme === $homeScheme ) {
            return false;
        }
        return true;
    }

    /**
     * Show a message when pro or free plugin becomes deactivated
     * 
     * @return void
     */
    private function plugin_deactivated_notice() {
        if( false !== ( $deactivated_notice_id = get_transient( 'wp_staging_deactivated_notice_id' ) ) ) {
            if( '1' === $deactivated_notice_id ) {
                $message = __( "WP Staging and WP Staging Pro cannot both be active. We've automatically deactivated WP Staging.", 'wp-staging' );
            } else {
                $message = __( "WP Staging and WP Staging Pro cannot both be active. We've automatically deactivated WP Staging Pro.", 'wp-staging' );
            }
            ?>
            <div class="updated notice is-dismissible" style="border-left: 4px solid #ffba00;">
                <p><?php echo esc_html( $message ); ?></p>
            </div> <?php
            delete_transient( 'wp_staging_deactivated_notice_id' );
        }
    }

}
