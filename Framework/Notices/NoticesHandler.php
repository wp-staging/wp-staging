<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Onboarding\FreeOnboarding;
use WPStaging\Framework\Traits\NoticesTrait;




class NoticesHandler
{
    use NoticesTrait;

    public function __construct()
    {
        $this->defineHooks();
    }




    public function removeOtherPluginAdminNotices()
    {
        $isWPStagingAdminPage = $this->isWPStagingAdminPage();

        if ($isWPStagingAdminPage) {
            remove_all_actions('admin_notices');
            remove_all_actions('user_admin_notices');
            remove_all_actions('network_admin_notices');
            remove_all_actions('all_admin_notices');
        }

        if ($this->isWPStagingInstallPage()) {
            return;
        }

        if ($isWPStagingAdminPage && $this->isOnboardingFocusMode()) {
            return;
        }

        add_action('admin_notices', [$this, 'addWpstgAdminNotices']);
        add_action('network_admin_notices', [$this, 'addWpstgNetworkAdminNotices']);
        add_action('all_admin_notices', [$this, 'addWpstgAllAdminNotices']);// phpcs:ignore WPStaging.Security.AuthorizationChecked
    }












    private function isOnboardingFocusMode(): bool
    {
        $onboarding = FreeOnboarding::resolve();

        return $onboarding !== null && $onboarding->ownsCurrentScreen();
    }




    public function addWpstgAdminNotices()
    {
        Hooks::doAction(Notices::ACTION_ADMIN_NOTICES);
    }




    public function addWpstgNetworkAdminNotices()
    {
        Hooks::doAction(Notices::ACTION_NETWORK_ADMIN_NOTICES);
    }




    public function addWpstgAllAdminNotices()
    {
        Hooks::doAction(Notices::ACTION_ALL_ADMIN_NOTICES);
    }




    private function defineHooks()
    {
        static $isRegistered = false;
        if ($isRegistered) {
            return;
        }

        add_action('in_admin_header', [$this, 'removeOtherPluginAdminNotices']);

 
        $isRegistered = true;
    }
}
