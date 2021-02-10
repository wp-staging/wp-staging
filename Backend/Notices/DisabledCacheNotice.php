<?php

namespace WPStaging\Backend\Notices;

/**
 * Class DisabledCacheNotice
 *
 * This class is used to show notice about why cache is disabled on the staging site
 *
 * @package WPStaging\Backend\Notices;
 */
class DisabledCacheNotice extends BooleanNotice
{
    /**
     * The option name to store the visibility of disabled cache notice
     */
    const OPTION_NAME = 'wpstg_disabled_cache_notice';

    public function getOptionName()
    {
        return self::OPTION_NAME;
    }
}