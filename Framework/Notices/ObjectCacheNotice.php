<?php

namespace WPStaging\Framework\Notices;

/**
 * Class ObjectCacheNotice
 *
 * This class is used to show notice whether object cache has been skipped during restore
 *
 * @see Notices;
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

    public function getOptionName(): string
    {
        return self::OPTION_NAME;
    }
}
