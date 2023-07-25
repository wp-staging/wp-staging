<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Framework\SiteInfo;

/**
 * Class ObjectCacheNotice
 *
 * This class is used to show notice whether object cache skipped during restore
 *
 * @see \WPStaging\Framework\Notices\Notices;
 */
class ObjectCacheNotice extends BooleanNotice
{
    /**
     * The action name to dismiss this notice
     */
    const NOTICE_DISMISS_ACTION = 'object-cache-skipped';

    /**
     * The option name to store the visibility of skipped object cache notice
     */
    const OPTION_NAME = 'wpstg_skipped_object_cache_notice';

    public function getOptionName()
    {
        return self::OPTION_NAME;
    }
}
