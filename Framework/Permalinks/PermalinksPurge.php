<?php

namespace WPStaging\Framework\Permalinks;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\ThirdParty\Elementor;
use WPStaging\Framework\ThirdParty\LiteSpeedCache;
use WPStaging\Framework\Traits\EventLoggerTrait;

class PermalinksPurge
{
    use EventLoggerTrait;

    const PLUGINS_LOADED_PRIORITY = 99999;
    const TRANSIENT = "wpstg_permalinks_do_purge";





    public function executeAfterPushing($stagingSite = [])
    {
        set_transient(self::TRANSIENT, "true");
        $this->logPushCompleted();
        set_transient(LiteSpeedCache::TRANSIENT_PURGE_LITESPEED_CACHE, "true");
        WPStaging::make(Elementor::class)->queueCssCacheClearAfterPush($this->pushedStagingSiteIsNetworkClone($stagingSite));
    }

    public function purgePermalinks()
    {
        if (get_transient(self::TRANSIENT)) {
            delete_transient(self::TRANSIENT);
            flush_rewrite_rules(false);
        }




        if (class_exists('\WPStaging\Framework\ThirdParty\LiteSpeedCache') && get_transient(LiteSpeedCache::TRANSIENT_PURGE_LITESPEED_CACHE)) {
            WPStaging::make(LiteSpeedCache::class)->maybePurgeLiteSpeedCache();
        }

        if (class_exists('\WPStaging\Framework\ThirdParty\Elementor') && get_option(Elementor::OPTION_CLEAR_CSS_CACHE)) {
            WPStaging::make(Elementor::class)->clearQueuedCssCache();
        }
    }





    private function pushedStagingSiteIsNetworkClone($stagingSite): bool
    {
        return is_array($stagingSite) && !empty($stagingSite['networkClone']);
    }
}
