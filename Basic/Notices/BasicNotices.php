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

 
    private $showAllNotices;

 
    private $ratingNotice;

 
    private $proCronsCleaner;

 
    private $assets;

    public function __construct(Assets $assets, RatingNotice $ratingNotice, ProCronsCleaner $proCronsCleaner)
    {
        $this->showAllNotices  = Notices::SHOW_ALL_NOTICES;
        $this->noticesViewPath = WPSTG_VIEWS_DIR . "notices/";
        $this->assets          = $assets;
        $this->ratingNotice    = $ratingNotice;
        $this->proCronsCleaner = $proCronsCleaner;
    }





    public function renderNotices()
    {
        $viewsNoticesPath = $this->noticesViewPath;

 
        if (!$this->isWPStagingAdminPage()) {
            return;
        }

 
 
 
 

        if ($this->showAllNotices || $this->proCronsCleaner->haveProCrons()) {
            require_once "{$viewsNoticesPath}pro-crons-notice.php";
        }
    }
}
