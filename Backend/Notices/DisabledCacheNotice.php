<?php

namespace WPStaging\Backend\Notices;

/**
 * Class DisabledCacheNotice
 *
 * This class is used to show notice about why cache is disabled on the staging site
 *
 * @package WPStaging\Backend\Notices;
 */
class DisabledCacheNotice
{
    /**
     * The option name to detect whether to show this notice or not.
     */
    const OPTION_NAME = 'wpstg_disabled_cache_notice';

    /**
     * Enable the option in database to show this notice
     */
    public function enable()
    {
        return add_option(self::OPTION_NAME, true);  
    }

    /** 
     * Check whether to show this notice or not 
     *  
     * @return bool 
     */
    public function isEnabled()
    {
        return get_option(self::OPTION_NAME, false);
    }

    /**
     * Delete the option in database to disable showing the notice
     * 
     * @return bool 
     */
    public function disable()
    {
        return delete_option(self::OPTION_NAME);
    }
}