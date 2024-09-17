<?php

namespace WPStaging\Basic\Notices;

use Exception;
use WPStaging\Basic\Ajax\ProCronsCleaner;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\Notices\FreeBackupUpdateNotice;
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

    /**
     * @var FreeBackupUpdateNotice
     */
    private $freeBackupUpdateNotice;

    public function __construct(Assets $assets, RatingNotice $ratingNotice, ProCronsCleaner $proCronsCleaner, FreeBackupUpdateNotice $freeBackupUpdateNotice)
    {
        $this->showAllNotices  = Notices::SHOW_ALL_NOTICES;
        $this->noticesViewPath = WPSTG_VIEWS_DIR . "notices/";
        $this->assets          = $assets;
        $this->ratingNotice    = $ratingNotice;
        $this->proCronsCleaner = $proCronsCleaner;
        $this->freeBackupUpdateNotice = $freeBackupUpdateNotice;
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

        // Show notice "rate the plugin"
        // We used to have this message on all pages but added a new nonce based authentication check.
        // As the nonce is not loaded on all pages we had to move this message to wp staging pages only.
        // @todo add our nonce to all wp staging pages and move this message back to all pages
        if ($this->showAllNotices || $this->ratingNotice->shouldShowRatingNotice()) {
            require_once "{$viewsNoticesPath}rating.php";
        }

        if ($this->showAllNotices || $this->proCronsCleaner->haveProCrons()) {
            require_once "{$viewsNoticesPath}pro-crons-notice.php";
        }

        // Show notice WP STAGING is not tested with current WordPress version
        if ($this->showAllNotices || version_compare(WPStaging::getInstance()->get('WPSTG_COMPATIBLE'), get_bloginfo("version"), "<")) {
            require_once "{$viewsNoticesPath}wp-version-compatible-message.php";
        }

        if ($this->showAllNotices || $this->freeBackupUpdateNotice->isEnabled()) {
            require_once "{$viewsNoticesPath}free-backup-update-notice.php";
        }
    }
}
