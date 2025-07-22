<?php

namespace WPStaging\Framework\Permalinks;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\ThirdParty\LiteSpeedCache;
use WPStaging\Framework\Traits\EventLoggerTrait;

class PermalinksPurge
{
    use EventLoggerTrait;

    const PLUGINS_LOADED_PRIORITY = 99999;
    const TRANSIENT = "wpstg_permalinks_do_purge";

    public function executeAfterPushing()
    {
        set_transient(self::TRANSIENT, "true");
        $this->logPushCompleted();
        set_transient(LiteSpeedCache::TRANSIENT_PURGE_LITESPEED_CACHE, "true");
    }

    public function purgePermalinks()
    {
        if (get_transient(self::TRANSIENT)) {
            delete_transient(self::TRANSIENT);
            flush_rewrite_rules(false);
        }

        /*
         * @see Issue: https://github.com/wp-staging/wp-staging-pro/issues/4392
         */
        if (class_exists('\WPStaging\Framework\ThirdParty\LiteSpeedCache') && get_transient(LiteSpeedCache::TRANSIENT_PURGE_LITESPEED_CACHE)) {
            WPStaging::make(LiteSpeedCache::class)->maybePurgeLiteSpeedCache();
        }
    }
}
