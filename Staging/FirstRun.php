<?php

namespace WPStaging\Staging;

use WPStaging\Frontend\LoginNotice;
use WPStaging\Framework\Notices\DisabledItemsNotice;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\ThirdParty\WordFence;
use WPStaging\Framework\ThirdParty\ThirdPartyCacheHandler;
use WPStaging\Framework\Filesystem\OPcache;

/**
 * Class FirstRun
 *
 * This class is executed only on first run when the cloned site is loaded initially
 *
 * @package WPStaging\Staging
 */
class FirstRun
{
    /**
     * The option_name that is stored in the database to check first run is executed or not
     */
    const FIRST_RUN_KEY = 'wpstg_execute';

    /**
     * The option_name that is stored in the database to check whether mails are disabled or not
     */
    const MAILS_DISABLED_KEY = 'wpstg_emails_disabled';

    /** @var string */
    const WOO_SCHEDULER_DISABLED_KEY = 'wpstg_woo_scheduler_disabled';

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

    /**
     * Initialize actions and classes which can be hooked in by custom functions
     * Add all classes here that you want to run on first time loading.
     */
    private function initActions()
    {
        // Show one time login notice on staging site.
        (new LoginNotice())->setTransient();

        // Enable the notice which show what WP Staging Disabled on staging site admin.
        WPStaging::make(DisabledItemsNotice::class)->enable();

        // Enable the notice which show what WP Staging Disabled on staging site admin.
        // This notice is disabled at the moment. Code below can be uncommented and notice can be tweaked if needed later.
        // WPStaging::make(WarningsNotice::class)->enable();

        // If user.ini present rename it to user.ini.bak and enable notice
        (new WordFence())->renameUserIni();

        if (class_exists('\WPStaging\Pro\Staging\NetworkClone')) {
            (new \WPStaging\Pro\Staging\NetworkClone())->init();
        }

        /** @var ThirdPartyCacheHandler $cacheHandler */
        $cacheHandler = WPStaging::make(ThirdPartyCacheHandler::class);
        $cacheHandler->purgeEnduranceCache();

        // Allow users to attach custom actions by using this hook
        do_action('wpstg.clone_first_run');

        // Flush OPcache
        WPStaging::make(OPcache::class)->maybeInvalidate();
    }

    /**
     * Remove the first run flag from database
     */
    private function removeInitialRunOption()
    {
        delete_option(static::FIRST_RUN_KEY);
    }
}
