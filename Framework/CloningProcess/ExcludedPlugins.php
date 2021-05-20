<?php

namespace WPStaging\Framework\CloningProcess;

use WPStaging\Framework\Staging\CloneOptions;
use WPStaging\Framework\Utils\WpDefaultDirectories;

/**
 * Add here the list of excluded plugins to make sure code remain DRY
 */
class ExcludedPlugins
{
    /**
     * @var string
     */
    const EXCLUDED_PLUGINS_KEY = 'excluded_plugins';

    /**
     * @var array
     */
    private $excludedPlugins;

    /**
     * Place below the list of plugins to exclude
     * If any of these below plugins are installed in user site they will be skipped during cloning
     * And a message will be shown to them about their exclusion
     */
    public function __construct()
    {
        $this->excludedPlugins = [
            'wps-hide-login',
            'wp-super-cache',
            'peters-login-redirect',
            'wp-spamshield',
        ];
    }

    /**
     * Get List of excluded plugins
     * The array can contain:
     * - A parent dir of a plugin like `woocommerce`
     * - A single file plugin like `hello-dolly.php`
     *
     * Here single file plugin means the plugins which consist of only a single file i.e. Hello Dolly plugin,
     * These single file plugins can be placed directly in the plugin path without the need of subfolder.
     * @see https://developer.wordpress.org/plugins/intro/ see 2nd paragraph under "Why We Make Plugins" to understand single file plugin better.
     *
     * @return array
     */
    public function getPluginsToExclude()
    {
        return $this->excludedPlugins;
    }

    /**
     * Get list of excluded plugins with relative path to wp root
     *
     * @return array
     */
    public function getPluginsToExcludeWithRelativePath()
    {
        return array_map(function ($plugin) {
            return '/' . trailingslashit((new WpDefaultDirectories())->getRelativePluginPath()) . $plugin;
        }, $this->excludedPlugins);
    }

    /**
     * Get List of excluded plugins defined by WP Staging and by excluded path hooks
     *
     * @param array $installedPlugins - Used for unit testing
     *
     * @return array
     */
    public function getFilteredPluginsToExclude($installedPlugins = [])
    {
        // Apply filter
        if (is_multisite()) {
            $filteredExcludedPlugins = apply_filters('wpstg_clone_mu_excl_folders', $this->getPluginsToExcludeWithRelativePath());
        } else {
            $filteredExcludedPlugins = apply_filters('wpstg_clone_excl_folders', $this->getPluginsToExcludeWithRelativePath());
        }

        if ($installedPlugins === []) {
            $installedPlugins = get_plugins();
            $installedPlugins = array_keys($installedPlugins);
        }

        // Remove all paths other than plugins not in installed plugins
        $filteredExcludedPlugins = array_filter($filteredExcludedPlugins, function ($path) use ($installedPlugins) {
            foreach ($installedPlugins as $plugin) {
                $plugin = '/' . trailingslashit((new WpDefaultDirectories())->getRelativePluginPath()) . explode('/', $plugin)[0];
                if (strpos($path, $plugin) !== false) {
                    return true;
                }
            }

            return false;
        });

        // Reindex the array
        $filteredExcludedPlugins = array_values($filteredExcludedPlugins);
        /*
         * Remove plugins dir from the paths and
         * only return plugin dir if inside directory otherwise return file
         * /wp-content/plugins/some-plugin/some-plugin.php will return some-plugin
         * /wp-content/plugins/single-file-plugin.php will return single-file-plugin.php
         * /wp-content/plugins/plugin-dir will return plugin-dir
         */
        return array_map(function ($path) {
            $plugin = str_replace('/' . trailingslashit((new WpDefaultDirectories())->getRelativePluginPath()), '', $path);
            return explode('/', $plugin)[0];
        }, $filteredExcludedPlugins);
    }

    /**
     * This returns the actual excluded plugins during cloning/updating/resetting
     *
     * @return array
     */
    public function getExcludedPlugins()
    {
        return (new CloneOptions())->get(self::EXCLUDED_PLUGINS_KEY);
    }
}
