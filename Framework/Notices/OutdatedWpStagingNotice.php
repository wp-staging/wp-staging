<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Core\WPStaging;
use Countable;







class OutdatedWpStagingNotice
{



    private $currentWpstgVersion = null;




    private $latestWpstgVersion = null;

    public function showNotice($viewsNoticesPath)
    {
 
        if (!Notices::SHOW_ALL_NOTICES && (WPStaging::isPro() || !$this->isOutdatedWpStagingVersion())) {
            return;
        }

        require "{$viewsNoticesPath}outdated-wp-staging-version.php";
    }




    public function getCurrentWpstgVersion()
    {
        if ($this->currentWpstgVersion === null) {
            $this->currentWpstgVersion = WPStaging::getVersion();
        }

        return $this->currentWpstgVersion;
    }




    public function getLatestWpstgVersion()
    {
        if ($this->latestWpstgVersion === null) {
            $this->latestWpstgVersion = $this->getNewestVersionToUpdateBySlug('wp-staging');
        }

        return $this->latestWpstgVersion;
    }





    private function getNewestVersionToUpdateBySlug($slug)
    {
        $plugins = get_site_transient('update_plugins');
        if (!is_object($plugins)) {
            return null;
        }

        if (!property_exists($plugins, 'response')) {
            return null;
        }

        $plugins = $plugins->response;

        if (empty($plugins) || (!is_array($plugins) || (!is_array($plugins) && $plugins instanceof Countable === false))) {
            return null;
        }

        foreach ($plugins as $plugin) {
            if ($plugin->slug === $slug) {
                return $plugin->new_version;
            }
        }

        return null;
    }




    private function isOutdatedWpStagingVersion()
    {
 
        if ($this->getLatestWpstgVersion() === null) {
            return false;
        }

        return version_compare($this->getLatestWpstgVersion(), $this->getCurrentWpstgVersion(), '>') ? true : false;
    }
}
