<?php

namespace WPStaging\Basic\Notices;

use Exception;
use WPStaging\Basic\Ajax\ProCronsCleaner;
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
        $this->noticesViewPath = WPSTG_VIEWS_DIR . "notices/";
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

        // Only show below notices on WP Staging admin pages
        if (!$this->isWPStagingAdminPage()) {
            return;
        }

        // The "rate the plugin" prompt is no longer an admin notice or dashboard
        // text. It is a success-based ask rendered inside the staging/backup
        // completion modals (see views/notices/review-prompt-modal.php), gated by
        // RatingNotice::isReviewPromptEligible().

        if ($this->showAllNotices || $this->proCronsCleaner->haveProCrons()) {
            require_once "{$viewsNoticesPath}pro-crons-notice.php";
        }
    }
}
