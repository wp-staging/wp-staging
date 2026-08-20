<?php

namespace WPStaging\Framework\Notices;








class ObjectCacheNotice extends BooleanNotice
{





    const ACTION_NOTICE_DISMISS = 'object-cache-skipped';






    const OPTION_NAME = 'wpstg_skipped_object_cache_notice';

    public function getOptionName(): string
    {
        return self::OPTION_NAME;
    }
}
