<?php

namespace WPStaging\Framework\ThirdParty;

use WPStaging\Framework\Adapter\WpAdapter;

use function WPStaging\functions\debug_log;

class LiteSpeedCache
{



    const TRANSIENT_PURGE_LITESPEED_CACHE = "wpstg_purge_litespeed_cache";




    protected $wpAdapter;




    public function __construct(WpAdapter $wpAdapter)
    {
        $this->wpAdapter = $wpAdapter;
    }




    public function maybePurgeLiteSpeedCache()
    {
        if (!$this->isLiteSpeedCacheActive()) {
            delete_transient(self::TRANSIENT_PURGE_LITESPEED_CACHE);
            return;
        }

        if (!class_exists('\LiteSpeed\Purge', false) || !method_exists('\LiteSpeed\Purge', 'purge_all')) {
            return;
        }

        \LiteSpeed\Purge::purge_all('wp-staging');
        debug_log('LiteSpeed Cache cache cleared.');
        delete_transient(self::TRANSIENT_PURGE_LITESPEED_CACHE);
    }






    private function isLiteSpeedCacheActive(): bool
    {
        return $this->wpAdapter->isPluginActive('litespeed-cache/litespeed-cache.php');
    }
}
