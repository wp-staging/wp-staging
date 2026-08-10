<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Onboarding\FreeOnboarding;
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
        $isWPStagingAdminPage = $this->isWPStagingAdminPage();

        if ($isWPStagingAdminPage) {
            remove_all_actions('admin_notices');
            remove_all_actions('user_admin_notices');
            remove_all_actions('network_admin_notices');
            remove_all_actions('all_admin_notices');
        }

        if ($this->isWpstgInstallPage()) {
            return;
        }

        if ($isWPStagingAdminPage && $this->isOnboardingFocusMode()) {
            return;
        }

        add_action('admin_notices', [$this, 'addWpstgAdminNotices']);
        add_action('network_admin_notices', [$this, 'addWpstgNetworkAdminNotices']);
        add_action('all_admin_notices', [$this, 'addWpstgAllAdminNotices']);// phpcs:ignore WPStaging.Security.AuthorizationChecked
    }

    /**
     * Whether the redesigned first-run selector owns the screen being rendered.
     * Only WP STAGING's own notices stand down for it; the call above has already
     * cleared everyone else's, and WordPress core chrome is untouched.
     *
     * Not applied before the consent decision is settled — the consent modal is
     * itself rendered through this dispatcher, so the notice that has to stand
     * down there withdraws on its own instead.
     *
     * @return bool
     */
    private function isOnboardingFocusMode(): bool
    {
        $onboarding = FreeOnboarding::resolve();

        return $onboarding !== null && $onboarding->ownsCurrentScreen();
    }

    /**
     * @return void
     */
    public function addWpstgAdminNotices()
    {
        Hooks::doAction(Notices::ACTION_ADMIN_NOTICES);
    }

    /**
     * @return void
     */
    public function addWpstgNetworkAdminNotices()
    {
        Hooks::doAction(Notices::ACTION_NETWORK_ADMIN_NOTICES);
    }

    /**
     * @return void
     */
    public function addWpstgAllAdminNotices()
    {
        Hooks::doAction(Notices::ACTION_ALL_ADMIN_NOTICES);
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
