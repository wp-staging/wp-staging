<?php

namespace WPStaging\Framework\Support\ThirdParty;

/**
 * Class FreemiusScript
 *
 * Provide special treatments for cloning and pushing when a site is using freemius library
 *
 * @package WPStaging\Framework\Support\ThirdParty
 */
class FreemiusScript
{
    /**
     * The option_name that is stored in the database to check if the freemius notice to be shown
     * on the staging site
     */
    const NOTICE_OPTION = 'wpstg_freemius_notice';

    /**
     * Get the list of freemius option to be deleted during cloning,
     * And preserve during push.
     *
     * @return array<string>
     */
    public function getFreemiusOptions()
    {
        return [
            'fs_accounts',
            'fs_dbg_accounts',
            'fs_active_plugins',
            'fs_api_cache',
            'fs_dbg_api_cache',
            'fs_debug_mode'
        ];
    }

    /**
     * Check if freemius options exist
     * Most likely if the production db have fs_accounts it will also have other freemius options.
     *
     * @return boolean
     */
    public function hasFreemiusOptions()
    {
        return get_option('fs_accounts') !== false;
    }

    /**
     * Check whether to show the freemius notice
     * If the option is not present then it should not be shown.
     *
     * @return bool
     */
    public function isNoticeEnabled()
    {
        return get_option(self::NOTICE_OPTION, false);
    }

    /**
     * Delete the option in database to disable showing the notice
     * Should be called only on the staging site as notice should be only shown on the staging site.
     *
     * @return bool
     */
    public function disableNotice()
    {
        return delete_option(self::NOTICE_OPTION);
    }
}
