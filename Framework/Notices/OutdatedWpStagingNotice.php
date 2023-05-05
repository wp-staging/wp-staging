<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Core\WPStaging;
use Countable;

/**
 * Class OutdatedWpStagingNotice
 *
 * Show a notification if installed WP STAGING free or pro version is outdated and if there is a new plugin update available
 * @see \WPStaging\Framework\Notices\Notices
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
        // Early bail if it's PRO version and not an outdated version
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
     * @return boolean
     */
    public function isOutdatedWpStagingProVersion()
    {
        // If latest pro version is not available there is no need to update
        if ($this->getLatestWpstgProVersion() === null) {
            return false;
        }

        return version_compare($this->getLatestWpstgProVersion(), $this->getCurrentWpstgVersion(), '>') ? true : false;
    }

    /**
     * Get the latest available WP STAGING PRO version
     * @return string
     */
    public function getLatestWpstgProVersion()
    {
        return $this->getNewestVersionToUpdateBySlug('wp-staging-pro');
    }

    /**
     * @param string $slug
     * @return null|string
     */
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

    /**
     * @return bool
     */
    private function isOutdatedWpStagingVersion()
    {
        // If latest version is not available there is no need to update
        if ($this->getLatestWpstgVersion() === null) {
            return false;
        }

        return version_compare($this->getLatestWpstgVersion(), $this->getCurrentWpstgVersion(), '>') ? true : false;
    }
}
