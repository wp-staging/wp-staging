<?php

namespace WPStaging\Framework\Utils;

class PluginInfo
{
 
    const FILTER_STYLESHEET_DIRECTORY = 'stylesheet_directory';

 
    const FILTER_TEMPLATE_DIRECTORY = 'template_directory';









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




    public function getAllActiveThemesInSubsites(): array
    {
        if (!is_multisite()) {
            return [];
        }

        $activeThemes = [];
        $sites        = get_sites();

        remove_all_filters(self::FILTER_STYLESHEET_DIRECTORY); 
        remove_all_filters(self::FILTER_TEMPLATE_DIRECTORY); 

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);

            $activeThemes[] = get_stylesheet_directory();
            $activeThemes[] = get_template_directory();

            restore_current_blog();
        }

        return array_unique($activeThemes);
    }





    public function getActiveThemes(): array
    {
        $activeThemes = [];

        remove_all_filters(self::FILTER_STYLESHEET_DIRECTORY); 
        remove_all_filters(self::FILTER_TEMPLATE_DIRECTORY); 

        $activeThemes[] = get_stylesheet_directory(); 
        $activeThemes[] = get_template_directory(); 

        return array_unique($activeThemes);
    }
}
