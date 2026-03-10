<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\AnalyticsConsent;
use WPStaging\Backend\Administrator;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Notices\Notices;

trait NoticesTrait
{
    /** @var string */
    protected $noticesViewPath;

    /**
     * Check whether the page is WP Staging admin page or not
     * it also checks if it is WP Staging AJAX action
     * @return bool
     */
    public function isWPStagingAdminPage()
    {
        return $this->isWPStagingAdminScreen() || $this->isWPStagingAjaxAction();
    }

    /**
     * Exact match for page slugs to avoid false positives from third-party plugins
     * whose slugs happen to start with "wpstg-" or "wpstg_"
     *
     * @return bool
     */
    protected function isWPStagingAdminScreen(): bool
    {
        if (!is_admin()) {
            return false;
        }

        $currentPage = isset($_GET["page"]) ? Sanitize::sanitizeString($_GET["page"]) : null;
        return !empty($currentPage) && in_array($currentPage, Administrator::ADMIN_PAGE_SLUGS, true);
    }

    /**
     * Prefix match for AJAX actions is safe because WP Staging controls all its own AJAX handlers
     *
     * @return bool
     */
    protected function isWPStagingAjaxAction(): bool
    {
        if (!wp_doing_ajax()) {
            return false;
        }

        $ajaxAction = isset($_POST['action']) ? Sanitize::sanitizeString($_POST['action']) : null;
        return !empty($ajaxAction) && (strpos($ajaxAction, 'wpstg-') === 0 || strpos($ajaxAction, 'wpstg_') === 0);
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

        Hooks::doAction(Notices::ACTION_INJECT_ANALYTICS_CONSENT_ASSETS);

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
