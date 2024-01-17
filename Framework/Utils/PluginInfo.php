<?php

namespace WPStaging\Framework\Utils;

class PluginInfo
{
    /**
     * Checks if the admin menu can be displayed. The different cases are:
     *  - if the free only version is active;
     *  - if the pro version is active then the free version must be active and compatible with the pro version.
     *  - if the free is not require for pro.
     *
     * @return bool
     */
    public function canShowAdminMenu(): bool
    {
        if (!defined('WPSTGPRO_VERSION')) {
            return true;
        }

        if (defined('WPSTG_REQUIRE_FREE') && !WPSTG_REQUIRE_FREE) {
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

    /**
     * @return array
     */
    public function getAllActivePluginsInSubsites(): array
    {
        if (!is_multisite()) {
            return [];
        }

        $activePlugins = [];
        $sites         = get_sites();

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);

            $activeForCurrent = (array) get_option('active_plugins', []);
            $activeWithPath = array_map(
                function ($plugin) {
                    return trailingslashit(WP_PLUGIN_DIR) . $plugin;
                },
                $activeForCurrent
            );

            $activePlugins = array_merge($activePlugins, $activeWithPath);

            restore_current_blog();
        }

        return array_unique($activePlugins);
    }

    /**
     * @return array
     */
    public function getAllActiveThemesInSubsites(): array
    {
        if (!is_multisite()) {
            return [];
        }

        $activeThemes = [];
        $sites        = get_sites();

        remove_all_filters('stylesheet_directory'); // to get the real value of get_stylesheet_directory().
        remove_all_filters('template_directory'); // to get the real value of get_template_directory().

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);

            $activeThemes[] = get_stylesheet_directory();
            $activeThemes[] = get_template_directory();

            restore_current_blog();
        }

        return array_unique($activeThemes);
    }

    /**
     * Get active parent and child themes
     * @return array
     */
    public function getActiveThemes(): array
    {
        $activeThemes = [];

        remove_all_filters('stylesheet_directory'); // to get the real value of get_stylesheet_directory().
        remove_all_filters('template_directory'); // to get the real value of get_template_directory().

        $activeThemes[] = get_stylesheet_directory(); // child theme
        $activeThemes[] = get_template_directory(); // parent theme

        return array_unique($activeThemes);
    }
}
