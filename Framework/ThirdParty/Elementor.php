<?php

namespace WPStaging\Framework\ThirdParty;

use WPStaging\Framework\Adapter\WpAdapter;

use function WPStaging\functions\debug_log;




class Elementor
{





    const OPTION_CLEAR_CSS_CACHE = 'wpstg_clear_elementor_css_cache';







    const PLUGIN_CLASS = '\Elementor\Plugin';

 
    protected $wpAdapter;




    public function __construct(WpAdapter $wpAdapter)
    {
        $this->wpAdapter = $wpAdapter;
    }








    public function queueCssCacheClearAfterPush(bool $isNetworkClone)
    {
        if (!$isNetworkClone || !is_multisite() || !is_main_site()) {
            $this->queueCssCacheClearOnCurrentSite();
            return;
        }

        foreach ($this->getSiteIdsAbleToServeRequests() as $siteId) {
            switch_to_blog($siteId);
            $this->queueCssCacheClearOnCurrentSite();
            restore_current_blog();
        }
    }






    public function clearQueuedCssCache()
    {
        if (!$this->isActive()) {
            delete_option(self::OPTION_CLEAR_CSS_CACHE);
            return;
        }

        $filesManager = $this->getFilesManager();
        if ($filesManager === null && $this->queuedCssCacheClearHasExpired()) {
            delete_option(self::OPTION_CLEAR_CSS_CACHE);
            return;
        }

        if ($filesManager === null) {
            debug_log('Elementor CSS cache clear stays queued: Elementor did not load its files manager in this request.', 'debug');
            return;
        }

        $filesManager->clear_cache();
        debug_log('Elementor CSS cache cleared after push.');
        delete_option(self::OPTION_CLEAR_CSS_CACHE);
    }




    protected function isActive(): bool
    {
        return $this->wpAdapter->isPluginActive('elementor/elementor.php');
    }






    protected function getFilesManager()
    {
        $pluginClass = static::PLUGIN_CLASS;
        if (!class_exists($pluginClass, false) || !isset($pluginClass::$instance->files_manager)) {
            return null;
        }

        $filesManager = $pluginClass::$instance->files_manager;
        if (!method_exists($filesManager, 'clear_cache')) {
            return null;
        }

        return $filesManager;
    }




    private function queueCssCacheClearOnCurrentSite()
    {
        update_option(self::OPTION_CLEAR_CSS_CACHE, time() + WEEK_IN_SECONDS, true);
    }




    private function queuedCssCacheClearHasExpired(): bool
    {
        return (int)get_option(self::OPTION_CLEAR_CSS_CACHE) < time();
    }






    private function getSiteIdsAbleToServeRequests(): array
    {
        return get_sites([
            'fields'     => 'ids',
            'number'     => 0,
            'archived'   => 0,
            'deleted'    => 0,
            'spam'       => 0,
            'network_id' => get_current_network_id(),
        ]);
    }
}
