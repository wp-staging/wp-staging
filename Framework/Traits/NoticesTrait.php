<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\Facades\Sanitize;

trait NoticesTrait
{
    /** @var string */
    protected $noticesViewPath;

    /** @var string|null */
    protected $pluginPath = '';

    /** @return string */
    public function getPluginPath()
    {
        if ($this->pluginPath === '') {
            $this->pluginPath = WPSTG_PLUGIN_DIR;
        }

        return $this->pluginPath;
    }

    /** @param string $pluginPath */
    public function setPluginPath($pluginPath)
    {
        $this->pluginPath = $pluginPath;
    }

    /**
     * Check whether the page is WP Staging admin page or not
     * @return bool
     */
    public function isWPStagingAdminPage()
    {
        // Early bail if it is not an admin page
        if (!is_admin()) {
            return false;
        }

        $currentPage = isset($_GET["page"]) ? Sanitize::sanitizeString($_GET["page"]) : null;
        if (empty($currentPage)) {
            return false;
        }

        $allowedPrefixes = ["wpstg-", "wpstg_"];

        foreach ($allowedPrefixes as $prefix) {
            if (strpos($currentPage, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    /** @return string */
    public function getNoticesViewPath()
    {
        return $this->noticesViewPath;
    }
}
