<?php

namespace WPStaging\Framework\Staging;

use WPStaging\Frontend\LoginNotice;
use WPStaging\Framework\Notices\DisabledItemsNotice;
use WPStaging\Framework\Notices\WarningsNotice;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Support\ThirdParty\WordFence;

/**
 * Class FirstRun
 *
 * This class is executed only on first run when the cloned site is loaded initially
 *
 * @package WPStaging\Framework\Staging
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

        // Allow users to attach custom actions by using this hook
        do_action('wpstg.clone_first_run');
    }

    /**
     * Remove the first run flag from database
     */
    private function removeInitialRunOption()
    {
        delete_option(static::FIRST_RUN_KEY);
    }
}
