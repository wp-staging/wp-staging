<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\AnalyticsConsent;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Notices\Notices;
use WPStaging\Framework\Traits\PagesTrait;

trait NoticesTrait
{
    use PagesTrait;

 
    protected $noticesViewPath;

 
    public function getNoticesViewPath()
    {
        return $this->noticesViewPath;
    }




    public function showAnalyticsModal()
    {
        if (get_option(AnalyticsConsent::OPTION_NAME_ANALYTICS_MODAL_DISMISSED)) {
            return;
        }

        if (!$this->isWPStagingAdminPage()) {
            return;
        }

 
        if ($this->isWPStagingInstallPage()) {
            return;
        }

 
        if ($this->isWPStagingWelcomePage()) {
            return;
        }

        if (WPStaging::make(AnalyticsConsent::class)->hasUserConsent()) {
            return;
        }

        Hooks::doAction(Notices::ACTION_INJECT_ANALYTICS_CONSENT_ASSETS);

        require_once WPSTG_VIEWS_DIR . "notices/analytics-modal.php";
    }
}
