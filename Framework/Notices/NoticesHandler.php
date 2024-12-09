<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Traits\NoticesTrait;

/**
 * @package WPStaging\Framework\Notices
 */
class NoticesHandler
{
    use NoticesTrait;

    public function __construct()
    {
        $this->defineHooks();
    }

    /**
     * @return void
     */
    public function removeOtherPluginAdminNotices()
    {
        if ($this->isWPStagingAdminPage()) {
            remove_all_actions('admin_notices');
            remove_all_actions('user_admin_notices');
            remove_all_actions('network_admin_notices');
            remove_all_actions('all_admin_notices');
        }

        if ($this->isWpstgInstallPage()) {
            return;
        }

        add_action('admin_notices', [$this, 'addWpstgAdminNotices']);
        add_action('network_admin_notices', [$this, 'addWpstgNetworkAdminNotices']);
        add_action('all_admin_notices', [$this, 'addWpstgAllAdminNotices']);// phpcs:ignore WPStaging.Security.AuthorizationChecked
    }

    /**
     * @return void
     */
    public function addWpstgAdminNotices()
    {
        Hooks::doAction('wpstg.admin_notices');
    }

    /**
     * @return void
     */
    public function addWpstgNetworkAdminNotices()
    {
        Hooks::doAction('wpstg.network_admin_notices');
    }

    /**
     * @return void
     */
    public function addWpstgAllAdminNotices()
    {
        Hooks::doAction('wpstg.all_admin_notices');
    }

    /**
     * @return void
     */
    private function defineHooks()
    {
        static $isRegistered = false;
        if ($isRegistered) {
            return;
        }

        add_action('in_admin_header', [$this, 'removeOtherPluginAdminNotices']);

        // loaded
        $isRegistered = true;
    }
}
