<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\AnalyticsConsent;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Notices\Notices;

trait NoticesTrait
{
    /** @var string */
    protected $noticesViewPath;

    /**
     * Check whether the page is WP Staging admin page or not
     * @return bool
     */
    public function isWPStagingAdminPage()
    {
        // Early bail if it is not an admin page
        if (!is_admin()) {
            return false;
        }

        $currentPage = isset($_GET["page"]) ? Sanitize::sanitizeString($_GET["page"]) : null;
        if (empty($currentPage)) {
            return false;
        }

        $allowedPrefixes = ["wpstg-", "wpstg_"];

        foreach ($allowedPrefixes as $prefix) {
            if (strpos($currentPage, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    /** @return string */
    public function getNoticesViewPath()
    {
        return $this->noticesViewPath;
    }

    /**
     * @return void
     */
    public function showAnalyticsModal()
    {
        if (get_option(AnalyticsConsent::OPTION_NAME_ANALYTICS_MODAL_DISMISSED)) {
            return;
        }

        if (!$this->isWPStagingAdminPage()) {
            return;
        }

        // don't show analytics modal on wpstg-install page
        if ($this->isWpstgInstallPage()) {
            return;
        }

        if (WPStaging::make(AnalyticsConsent::class)->hasUserConsent()) {
            return;
        }

        Hooks::doAction(Notices::INJECT_ANALYTICS_CONSENT_ASSETS_ACTION);

        require_once WPSTG_VIEWS_DIR . "notices/analytics-modal.php";
    }

    /**
     * @return bool
     */
    public function isWpstgInstallPage(): bool
    {
        $currentPage = isset($_GET["page"]) ? Sanitize::sanitizeString($_GET["page"]) : null;

        if ($currentPage === 'wpstg-install') {
            return true;
        }

        return false;
    }

    /**
     * Check whether the page is WP Staging clone page
     * @return bool
     */
    public function isWPStagingClonePage(): bool
    {
        // Early bail if it is not an WPStaging admin page
        if (!$this->isWPStagingAdminPage()) {
            return false;
        }

        $currentPage = isset($_GET["page"]) ? Sanitize::sanitizeString($_GET["page"]) : null;
        if ($currentPage !== 'wpstg_clone') {
            return false;
        }

        return $currentPage === 'wpstg_clone';
    }
}
