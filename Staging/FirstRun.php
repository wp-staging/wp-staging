<?php

namespace WPStaging\Staging;

use WPStaging\Frontend\LoginNotice;
use WPStaging\Framework\Notices\DisabledItemsNotice;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\ThirdParty\WordFence;
use WPStaging\Framework\ThirdParty\ThirdPartyCacheHandler;
use WPStaging\Framework\Filesystem\OPcache;








class FirstRun
{



    const FIRST_RUN_KEY = 'wpstg_execute';




    const MAILS_DISABLED_KEY = 'wpstg_emails_disabled';

 
    const WOO_SCHEDULER_ENABLED_KEY = 'wpstg_woo_scheduler_enabled';

    public function init()
    {
        if (!(new SiteInfo())->isStagingSite()) {
            return;
        }

        if (!get_option(self::FIRST_RUN_KEY)) {
            return;
        }

        $this->initActions();

        $this->removeInitialRunOption();
    }





    private function initActions()
    {
 
        (new LoginNotice())->setTransient();

 
        delete_transient(Assets::TRANSIENT_REST_URL);

 
        WPStaging::make(DisabledItemsNotice::class)->enable();

 
 
 

 
        (new WordFence())->renameUserIni();

        if (class_exists('\WPStaging\Pro\Staging\NetworkClone')) {
            (new \WPStaging\Pro\Staging\NetworkClone())->init();
        }

 
        $cacheHandler = WPStaging::make(ThirdPartyCacheHandler::class);
        $cacheHandler->purgeEnduranceCache();

 
        do_action('wpstg.clone_first_run');

 
        WPStaging::make(OPcache::class)->maybeInvalidate();
    }




    private function removeInitialRunOption()
    {
        delete_option(static::FIRST_RUN_KEY);
    }
}
