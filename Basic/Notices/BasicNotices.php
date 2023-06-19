<?php

namespace WPStaging\Basic\Notices;

use Exception;
use WPStaging\Basic\Ajax\ProCronsCleaner;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\Notices\Notices;
use WPStaging\Framework\Traits\NoticesTrait;

class BasicNotices
{
    use NoticesTrait;

    /** @var bool */
    private $showAllNotices;

    /** @var RatingNotice */
    private $ratingNotice;

    /** @var ProCronsCleaner */
    private $proCronsCleaner;

    /** @var Assets */
    private $assets;

    public function __construct(Assets $assets, RatingNotice $ratingNotice, ProCronsCleaner $proCronsCleaner)
    {
        $this->showAllNotices  = Notices::SHOW_ALL_NOTICES;
        $this->noticesViewPath = $this->getPluginPath() . "/Backend/views/notices/";
        $this->assets          = $assets;
        $this->ratingNotice    = $ratingNotice;
        $this->proCronsCleaner = $proCronsCleaner;
    }

    /**
     * Load admin notices for FREE version only
     * @throws Exception
     */
    public function renderNotices()
    {
        $viewsNoticesPath = $this->noticesViewPath;
        // Show notice "rate the plugin"
        if ($this->showAllNotices || $this->ratingNotice->shouldShowRatingNotice()) {
            require_once "{$viewsNoticesPath}rating.php";
        }

        // Only show below notices on WP Staging admin pages
        if (!$this->isWPStagingAdminPage()) {
            return;
        }

        if ($this->showAllNotices || $this->proCronsCleaner->haveProCrons()) {
            require_once "{$viewsNoticesPath}pro-crons-notice.php";
        }

        // Show notice WP STAGING is not tested with current WordPress version
        if ($this->showAllNotices || version_compare(WPStaging::getInstance()->get('WPSTG_COMPATIBLE'), get_bloginfo("version"), "<")) {
            require_once "{$viewsNoticesPath}wp-version-compatible-message.php";
        }
    }
}
