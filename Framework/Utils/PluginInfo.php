<?php

namespace WPStaging\Framework\Utils;

class PluginInfo
{
    /**
     * Checks if the admin menu can be displayed. The different cases are:
     *  - if the free only version is active;
     *  - if the pro version is active then the free version must be active and compatible with the pro version.
     *
     * @return bool
     */
    public function canShowAdminMenu(): bool
    {
        if (!defined('WPSTGPRO_VERSION')) {
            return true;
        }

        if (wpstgIsFreeActiveInNetworkOrCurrentSite()) {
            return true;
        }

        if (wpstgIsFreeVersionCompatible()) {
            return true;
        }

        return false;
    }
}
