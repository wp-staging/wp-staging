<?php

namespace WPStaging\Framework\Adapter;

/**
 * Class WP
 * Adapter to maintain wordpress core function for WP backward compatibility support and deprecated functions
 *
 * @package WPStaging\Framework\Adapter
 */
class WpAdapter
{
    /**
     * Is the current request doing some ajax
     * Alternative to wp_doing_ajax() as it is not available for WP < 4.7
     * This implementation is without hooks
     *
     * @return bool
     */
    public function doingAjax()
    {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

    /**
     * @return bool
     */
    public function isWpCliRequest()
    {
        return defined('WP_CLI') && WP_CLI;
    }

    /**
     * Alternative to is_plugin_active.
     * WordPress is_plugin_active is not available until admin_init hook,
     * We needs its functionality before that.
     *
     * @param string $plugin
     * @return bool
     */
    public function isPluginActive($plugin)
    {
        // Prevent filters tampering with the active plugins list, such as wpstg-optimizer.php itself.
        remove_all_filters('option_active_plugins');
        return in_array($plugin, (array) get_option('active_plugins', [])) || $this->isPluginNetworkActive($plugin);
    }

    /**
     * Alternative to is_plugin_active_for_network.
     * WordPress is_plugin_active_for_network is not available until admin_init hook,
     * We needs its functionality before that.
     *
     * @param string $plugin
     * @return bool
     */
    public function isPluginNetworkActive($plugin)
    {
        if (!is_multisite()) {
            return false;
        }

        // Prevent filters tampering with the active plugins list, such as wpstg-optimizer.php itself.
        remove_all_filters('site_option_active_sitewide_plugins');
        $plugins = get_site_option('active_sitewide_plugins');
        if (isset($plugins[$plugin])) {
            return true;
        }

        return false;
    }

    /*
     * Return the current network id
     * Use get_current_network_id in WP >= 4.6
     * Use get_current_site()->id in WP < 4.6, >= 3.7
     * Use get_current_site()->ID in WP < 3.7
     *
     * @return int
     */
    public function getCurrentNetworkId()
    {
        // Early bail if not multisite
        if (!is_multisite()) {
            return 1;
        }

        // For WP >= 4.6
        if (is_callable('get_current_network_id')) {
            return get_current_network_id();
        }

        // If get_current_site is not available return 1
        if (!is_callable('get_current_site')) {
            return 1;
        }

        $currentSite = get_current_site();

        // For WP >= 3.7 and < 4.6
        if (property_exists($currentSite, 'id')) {
            $currentSite->id;
        }

        // For WP < 3.7
        if (property_exists($currentSite, 'ID')) {
            $currentSite->ID;
        }

        return 1;
    }
}
