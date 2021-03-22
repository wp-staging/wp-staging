<?php

namespace WPStaging\Framework\Staging;

use WPStaging\Frontend\LoginNotice;
use WPStaging\Backend\Notices\DisabledCacheNotice;
use WPStaging\Framework\SiteInfo;

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
        if (!(new SiteInfo)->isStaging()) {
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

        // Enable the disabled cache notice to be shown on the staging site admin.
        (new DisabledCacheNotice())->enable();

        // Enable the disabled mail notice to be shown on the staging site admin.
        if (defined('WPSTGPRO_VERSION')) {
            (new \WPStaging\Backend\Pro\Notices\DisabledMailNotice())->enable();
        }

        // Allow users to attach custom actions by using this hook
        do_action('wpstg.clone_first_run');

        // Deprecated on Oct-2020.
        do_action_deprecated('wpstg_clone_action_staging', [], '2.7.6', 'wpstg.clone_first_run');
    }

    /**
     * Remove the first run flag from database
     */
    private function removeInitialRunOption()
    {
        delete_option(static::FIRST_RUN_KEY);
    }

}
