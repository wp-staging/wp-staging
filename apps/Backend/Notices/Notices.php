<?php

namespace WPStaging\Backend\Notices;

/* 
 *  Admin Notices | Warnings | Messages
 */

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

use WPStaging\WPStaging;

class Notices {
    
    public function __construct($path) {
        $this->path = $path;
    }
    
    /**
     * Check whether the page is admin page or not
     * @return bool
     */
    private function isAdminPage()
    {
        $currentPage    = (isset($_GET["page"])) ? $_GET["page"] : null;

        $availablePages = array(
            "wpstg-settings", "wpstg-addons", "wpstg-tools", "wpstg-clone", "wpstg_clone"
        );

        if (!is_admin() || !did_action("wp_loaded") || !in_array($currentPage, $availablePages, true))
        {
            return false;
        }

        return true;
    }
    
    /**
     * Check whether we can show poll or not
     * @param string $type
     * @param int $days
     * @return bool
     */
    private function canShow($type, $days = 10)
    {
        $installDate= new \DateTime(get_option("wpstg_installDate"));
        $now        = new \DateTime("now");

        // Get days difference
        $difference = $now->diff($installDate)->days;

        return ($days <= $difference && "no" !== get_option("wpstg_start_poll"));
    }

    public function messages()
    {
        // Display messages to only admins, only on admin panel
        if (!current_user_can("update_plugins") || !$this->isAdminPage())
        {
            return;
        }

        $messagesDirectory  = "{$this->path}views/_includes/messages/";

        $varsDirectory      = \WPStaging\WPStaging::getContentDir();
        
        
        // Poll
        if ($this->canShow("wpstg_start_poll"))
        {
            require_once "{$messagesDirectory}poll.php";
        }
        
    // Cache directory in uploads is not writable
        if (!wp_is_writable($varsDirectory))
        {
            require_once "{$messagesDirectory}/uploads-cache-directory-permission-problem.php";
        }
// Cache dir is not available after installation
//        // Cache directory is not writable
//        if (!wp_is_writable("{$varsDirectory}cache"))
//        {
//            require_once "{$messagesDirectory}/cache-directory-permission-problem.php";
//        }
//
        // Logs directory is not writable
//        if (!wp_is_writable("{$varsDirectory}logs"))
//        {
//            require_once "{$messagesDirectory}/logs-directory-permission-problem.php";
//        }

        // Vars directory is not writable
//        if (!wp_is_writable($varsDirectory))
//        {
//            require_once "{$messagesDirectory}/vars-directory-permission-problem.php";
//        }



        // Version Control
        if (version_compare(WPStaging::WP_COMPATIBLE, get_bloginfo("version"), "<"))
        {
            require_once "{$messagesDirectory}wp-version-compatible-message.php";
        }

        // Beta
        if ("no" === get_option("wpstg_hide_beta"))
        {
            require_once "{$messagesDirectory}beta.php";
        }

        // Transient
        if (false !== ( $deactivatedNoticeID = get_transient("wp_staging_deactivated_notice_id") ))
        {
            require_once "{$messagesDirectory}transient.php";
            delete_transient("wp_staging_deactivated_notice_id");
        }

        if ($this->canShow("wpstg_installDate", 7))
        {
            require_once "{$messagesDirectory}rating.php";

        }
    }
}