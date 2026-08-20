<?php

namespace WPStaging\Framework\Adapter;







class WpAdapter
{
 
    const FILTER_OPTION_ACTIVE_PLUGINS = 'option_active_plugins';

 
    const FILTER_SITE_OPTION_ACTIVE_SITEWIDE_PLUGINS = 'site_option_active_sitewide_plugins';








    public function doingAjax()
    {
        return defined('DOING_AJAX') && DOING_AJAX;
    }




    public function isWpCliRequest()
    {
        return defined('WP_CLI') && WP_CLI;
    }









    public function isPluginActive($plugin)
    {
 
        remove_all_filters(self::FILTER_OPTION_ACTIVE_PLUGINS);
        return in_array($plugin, (array) get_option('active_plugins', [])) || $this->isPluginNetworkActive($plugin);
    }









    public function isPluginNetworkActive($plugin)
    {
        if (!is_multisite()) {
            return false;
        }

 
        remove_all_filters(self::FILTER_SITE_OPTION_ACTIVE_SITEWIDE_PLUGINS);
        $plugins = get_site_option('active_sitewide_plugins');
        if (isset($plugins[$plugin])) {
            return true;
        }

        return false;
    }









    public function getCurrentNetworkId()
    {
 
        if (!is_multisite()) {
            return 1;
        }

 
        if (is_callable('get_current_network_id')) {
            return get_current_network_id();
        }

 
        if (!is_callable('get_current_site')) {
            return 1;
        }

        $currentSite = get_current_site();

        if (!is_object($currentSite)) {
            return 1;
        }

 
        if (property_exists($currentSite, 'id')) {
            $currentSite->id;
        }

 
        if (property_exists($currentSite, 'ID')) {
            $currentSite->ID;
        }

        return 1;
    }
}
