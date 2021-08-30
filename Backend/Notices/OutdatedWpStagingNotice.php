<?php

namespace WPStaging\Backend\Notices;

use WPStaging\Core\WPStaging;

/**
 * Class OutdatedWpStagingNotice
 *
 * Check if using an outdated version of WP Staging Plugin
 *
 * @see \WPStaging\Backend\Notices\Notices
 */
class OutdatedWpStagingNotice
{
    /**
     * @var string
     */
    private $currentWpstgVersion = null;

    /**
     * @var string
     */
    private $latestWpstgVersion = null;

    public function showNotice($viewsNoticesPath)
    {
        // Early bail if PRO version and not an outdated version
        if (!Notices::SHOW_ALL_NOTICES && (WPStaging::isPro() || !$this->isOutdatedWpStagingVersion())) {
            return;
        }

        require "{$viewsNoticesPath}outdated-wp-staging-version.php";
    }

    /**
     * @return string
     */
    public function getCurrentWpstgVersion()
    {
        if ($this->currentWpstgVersion === null) {
            $this->currentWpstgVersion = WPStaging::getVersion();
        }

        return $this->currentWpstgVersion;
    }

    /**
     * @return string
     */
    public function getLatestWpstgVersion()
    {
        if ($this->latestWpstgVersion === null) {
            $this->latestWpstgVersion = $this->getNewestVersionToUpdateBySlug('wp-staging');
        }

        return $this->latestWpstgVersion;
    }

    /**
     * @param string $slug
     * @return null|string
     */
    private function getNewestVersionToUpdateBySlug($slug)
    {
        $plugins = get_site_transient('update_plugins');
        $plugins = $plugins->response;
        foreach ($plugins as $plugin) {
            if ($plugin->slug === $slug) {
                return $plugin->new_version;
            }
        }

        return null;
    }

    private function isOutdatedWpStagingVersion()
    {
        // If latest version is not available that mean to update
        if ($this->getLatestWpstgVersion() === null) {
            return false;
        }

        return version_compare($this->getLatestWpstgVersion(), $this->getCurrentWpstgVersion(), '>=') ? true : false;
    }
}
