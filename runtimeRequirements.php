<?php

/**
 * @var string $pluginFilePath The absolute path to the main file of this plugin.
 */
// TODO: refactor this and implement our own methods to get rid of hard loading wp core functions which is not recommended by WP.
require_once(trailingslashit(ABSPATH) . 'wp-admin/includes/plugin.php');

if (!defined('WPSTGPRO_MINIMUM_FREE_VERSION')) {
    /** Expected version number of the free plugin in order to activate it at the same time with pro */
    define('WPSTGPRO_MINIMUM_FREE_VERSION', '3.8.0');
}

if (!defined('WPSTG_FREE_VERSION_PLUGIN_FILE')) {
    define('WPSTG_FREE_VERSION_PLUGIN_FILE', 'wp-staging.php');
}

if (!defined('WPSTG_PRO_VERSION_PLUGIN_FILE')) {
    define('WPSTG_PRO_VERSION_PLUGIN_FILE', 'wp-staging-pro.php');
}

if (!function_exists('wpstgIsProPluginActive')) {
    /**
     * @return bool
     */
    function wpstgIsProPluginActive(): bool
    {
        return wpstgIsPluginActivated(WPSTG_PRO_VERSION_PLUGIN_FILE);
    }
}

if (!function_exists('wpstgIsProPluginActiveInNetwork')) {
    /**
     * @return bool
     */
    function wpstgIsProPluginActiveInNetwork(): bool
    {
        return wpstgIsPluginActiveInNetwork(WPSTG_PRO_VERSION_PLUGIN_FILE);
    }
}

if (!function_exists('wpstgIsFreeVersionRequiredForPro')) {
    /**
     * @return bool
     */
    function wpstgIsFreeVersionRequiredForPro(): bool
    {
        return apply_filters('wpstg.free_required_by_pro', true);
    }
}

if (!function_exists('wpstgIsProActiveInNetworkOrInCurrentSite')) {
    /**
     * @return bool
     */
    function wpstgIsProActiveInNetworkOrInCurrentSite(): bool
    {
        return wpstgIsProPluginActiveInNetwork() || wpstgIsProPluginActive();
    }
}

if (!function_exists('wpstgIsFreeVersionActive')) {
    /**
     * @return bool
     */
    function wpstgIsFreeVersionActive(): bool
    {
        return wpstgIsPluginActivated(WPSTG_FREE_VERSION_PLUGIN_FILE);
    }
}

if (!function_exists('wpstgIsFreeVersionActiveInNetwork')) {
    /**
     * @return bool
     */
    function wpstgIsFreeVersionActiveInNetwork(): bool
    {
        return wpstgIsPluginActiveInNetwork(WPSTG_FREE_VERSION_PLUGIN_FILE);
    }
}

if (!function_exists('wpstgIsFreeActiveInNetworkOrCurrentSite')) {
    /**
     * @return bool
     */
    function wpstgIsFreeActiveInNetworkOrCurrentSite(): bool
    {
        return wpstgIsFreeVersionActiveInNetwork() || wpstgIsFreeVersionActive();
    }
}

if (!function_exists('wpstgGetPluginSlug')) {
    /**
     * @param string $pluginFileName
     *
     * @return bool|string false if plugin is not installed otherwise return the plugin slug/basename.
     */
    function wpstgGetPluginSlug(string $pluginFileName)
    {
        $allPlugins = get_plugins();
        foreach ($allPlugins as $key => $value) {
            if (strpos($key, $pluginFileName) !== false) {
                return $key;
            }
        }

        return false;
    }
}

if (!function_exists('wpstgGetPluginData')) {
    /**
     * @param string $pluginFileName
     *
     * @return array
     */
    function wpstgGetPluginData(string $pluginFileName): array
    {
        $allPlugins = get_plugins();
        foreach ($allPlugins as $key => $value) {
            if (strpos($key, $pluginFileName) !== false) {
                return $value;
            }
        }

        return [];
    }
}

if (!function_exists('wpstgGetFreeVersionNumberIfInstalled')) {
    /**
     * @return string returns empty string if free is not installed.
     */
    function wpstgGetFreeVersionNumberIfInstalled(): string
    {
        $freeData                   = wpstgGetPluginData(WPSTG_FREE_VERSION_PLUGIN_FILE);
        $installedFreeVersionNumber = isset($freeData['Version']) ? $freeData['Version'] : '';

        return $installedFreeVersionNumber;
    }
}

if (!function_exists('wpstgGetProVersionNumberIfInstalled')) {
    /**
     * @return string returns empty string if pro is not installed.
     */
    function wpstgGetProVersionNumberIfInstalled(): string
    {
        $freeData                   = wpstgGetPluginData(WPSTG_PRO_VERSION_PLUGIN_FILE);
        $installedFreeVersionNumber = isset($freeData['Version']) ? $freeData['Version'] : '';

        return $installedFreeVersionNumber;
    }
}

if (!function_exists('wpstgIsFreeVersionCompatible')) {
    /**
     * @return bool
     */
    function wpstgIsFreeVersionCompatible(): bool
    {
        return defined('WPSTGPRO_MINIMUM_FREE_VERSION') && version_compare(wpstgGetFreeVersionNumberIfInstalled(), WPSTGPRO_MINIMUM_FREE_VERSION, '>=');
    }
}

if (!function_exists('wpstgIsFreeActiveButOutdated')) {
    /**
     * @return bool
     */
    function wpstgIsFreeActiveButOutdated(): bool
    {
        if (wpstgIsFreeActiveInNetworkOrCurrentSite() && !wpstgIsFreeVersionCompatible()) {
            return true;
        }

        return false;
    }
}

if (!function_exists('wpstgDeactivatePlugin')) {
    /**
     * @param  mixed $pluginFilePath
     * @return void
     */
    function wpstgDeactivatePlugin($pluginFilePath)
    {
        if (is_network_admin()) {
            deactivate_plugins($pluginFilePath, false, true);
        } else {
            deactivate_plugins($pluginFilePath);
        }
    }
}

if (!function_exists('wpstgCanShowAnotherInstanceRunningNotice')) {
    /**
     * @param string $pluginFilePath
     * @return bool
     */
    function wpstgCanShowAnotherInstanceRunningNotice(string $pluginFilePath): bool
    {
        if (!current_user_can('activate_plugins')) {
            return false;
        }

        if (strpos($pluginFilePath, 'wp-staging-pro.php') !== false && wpstgIsProActiveInNetworkOrInCurrentSite() && !wpstgIsFreeActiveInNetworkOrCurrentSite()) {
            return true;
        }

        if (strpos($pluginFilePath, 'wp-staging.php') !== false && !wpstgIsProActiveInNetworkOrInCurrentSite() && wpstgIsFreeActiveInNetworkOrCurrentSite()) {
            return true;
        }

        return false;
    }
}

if (!function_exists('wpstgCanThrowAnotherInstanceLoadedException')) {
    /**
     * @param string $pluginFilePath
     * @return bool
     */
    function wpstgCanThrowAnotherInstanceLoadedException(string $pluginFilePath = ''): bool
    {
        if (defined('WPSTG_VERSION') && version_compare(WPSTG_VERSION, WPSTGPRO_MINIMUM_FREE_VERSION, '<')) {
            return true;
        }

        if (defined('WPSTGPRO_VERSION') && version_compare(WPSTGPRO_VERSION, '5.1.0', '<')) {
            return true;
        }

        if (!wpstgIsProActiveInNetworkOrInCurrentSite() && strpos($pluginFilePath, 'wp-staging-pro.php') === false) {
            return true;
        }

        return false;
    }
}

if (!function_exists('wpstgIsPluginActivated')) {
    /**
     * This function checks if a plugin is activated on single site.
     *
     * @param string $pluginFileName
     *
     * @return bool
     */
    function wpstgIsPluginActivated(string $pluginFileName): bool
    {
        $activePlugins = wp_get_active_and_valid_plugins();
        foreach ($activePlugins as $sitewidePlugin) {
            if (strpos($sitewidePlugin, $pluginFileName) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('wpstgIsPluginActiveInNetwork')) {
    /**
     * @param string $pluginFileName
     *
     * @return bool
     */
    function wpstgIsPluginActiveInNetwork(string $pluginFileName): bool
    {
        if (!is_multisite()) {
            return false;
        }

        $activePlugins = wp_get_active_network_plugins();
        foreach ($activePlugins as $sitewidePlugin) {
            if (strpos($sitewidePlugin, $pluginFileName) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('wpstgDoLoadPluginAutoLoad')) {
    /**
     * @param string $pluginFilePath
     * @return void
     */
    function wpstgDoLoadPluginAutoLoad(string $pluginFilePath): bool
    {
        if (class_exists('\WPStaging\Core\WPStaging')) {
            return false;
        }

        if (strpos($pluginFilePath, 'wp-staging.php') === false) {
            return true;
        }

        if (strpos($pluginFilePath, 'wp-staging.php') !== false && (!is_network_admin() && !wpstgIsProPluginActive())) {
            return true;
        }

        if (strpos($pluginFilePath, 'wp-staging.php') !== false && (is_network_admin() && !wpstgIsProPluginActiveInNetwork())) {
            return true;
        }

        return false;
    }
}

/**
 * Early bail: Deactivate outdated free version.
 */
if (strpos($pluginFilePath, 'wp-staging-pro.php') !== false && wpstgIsFreeActiveButOutdated()) {
    // Deactivate free plugin.
    $pluginSlug = wpstgGetPluginSlug(WPSTG_FREE_VERSION_PLUGIN_FILE);
    wpstgDeactivatePlugin($pluginSlug);
}

/**
 * Early bail: Activating another WPSTAGING Plugin.
 *             This is the only scenario where the plugin would be included after "plugins_loaded",
 *             therefore we need to detect earlier, from the context of the request, whether this is going to happen,
 *             to disable this plugin early and bail the bootstrap process to not conflict with the one being activated.
 *
 *             Covers both clicking on the "Activate" button and selecting the "Activate" bulk-action.
 */
if (isset($_REQUEST['action'])) {
    switch ($_REQUEST['action']) :
        case 'activate':
        case 'error_scrape':
            if (isset($_REQUEST['plugin'])) {
                $plugin = (string)wp_unslash(sanitize_text_field($_REQUEST['plugin']));

                $isActivatingWpStaging        = strpos($plugin, 'wp-staging.php') || strpos($plugin, 'wp-staging-pro.php');
                $isActivatingAnotherWpStaging = plugin_basename($plugin) !== plugin_basename($pluginFilePath);

                if ($isActivatingWpStaging && $isActivatingAnotherWpStaging && wpstgCanThrowAnotherInstanceLoadedException($plugin) && current_user_can('deactivate_plugin', plugin_basename($pluginFilePath))) {
                    throw new Exception("Activating another WPSTAGING Plugin. Plugin that bailed bootstrapping: $pluginFilePath");
                }
            }

            break;
        case 'activate-selected':
        case 'activate-multi':
            if (isset($_REQUEST['checked'])) {
                $plugins = array_map('sanitize_text_field', (array)wp_unslash($_REQUEST['checked']));

                foreach ($plugins as $i => $plugin) {
                    $isActivatingWpStaging        = strpos($plugin, 'wp-staging.php') || strpos($plugin, 'wp-staging-pro.php');
                    $isActivatingAnotherWpStaging = plugin_basename($plugin) !== plugin_basename($pluginFilePath);

                    if ($isActivatingWpStaging && $isActivatingAnotherWpStaging && wpstgCanThrowAnotherInstanceLoadedException($plugin) && current_user_can('deactivate_plugin', plugin_basename($pluginFilePath))) {
                        throw new Exception("Activating another WPSTAGING Plugin. Plugin that bailed bootstrapping: $pluginFilePath");
                    }
                }
            }

            break;
    endswitch;
}

/**
 * Early bail: Another instance of WPSTAGING active.
 */
if (
    // WPSTAGING <= 2.7.5
    class_exists('\WPStaging\WPStaging') ||
    // WPSTAGING >= 2.7.6
    class_exists('\WPStaging\Core\WPStaging')
) {
    if (wpstgCanShowAnotherInstanceRunningNotice($pluginFilePath)) {
        add_action(is_network_admin() ? 'network_admin_notices' : 'admin_notices', function () { // phpcs:ignore WPStaging.Security.FirstArgNotAString, WPStaging.Security.AuthorizationChecked
            echo '<div class="notice-warning notice is-dismissible another-wpstaging-active">';
            echo '<p style="font-weight: bold;">' . esc_html__('WP STAGING Already Active', 'wp-staging') . '</p>';
            echo '<p>' . esc_html__('Another WP STAGING is already activated, please leave only one instance of the WP STAGING plugin active at the same time.', 'wp-staging') . '</p>';
            echo '</div>';
        });
    }

    if (!wpstgCanThrowAnotherInstanceLoadedException($pluginFilePath)) {
        return;
    }

    throw new Exception("Another instance of WPSTAGING active. Plugin that bailed bootstrapping: $pluginFilePath");
}

/**
 * Early bail: Unsupported WordPress version.
 *             We check on runtime instead of activation so we can display the notice.
 */
if (!version_compare($currentWordPressVersion = (string)get_bloginfo('version'), $minimumWordPressVersion = '4.4', '>=')) {
    if (current_user_can('activate_plugins')) {
        add_action(is_network_admin() ? 'network_admin_notices' : 'admin_notices', function () use ($currentWordPressVersion, $minimumWordPressVersion) { // phpcs:ignore WPStaging.Security.FirstArgNotAString, WPStaging.Security.AuthorizationChecked
            echo '<div class="notice-warning notice is-dismissible">';
            echo '<p style="font-weight: bold;">' . esc_html__('WP STAGING', 'wp-staging') . '</p>';
            echo '<p>' . sprintf(esc_html__('WP STAGING requires at least WordPress %s to run. You have WordPress %s.', 'wp-staging'), esc_html($minimumWordPressVersion), esc_html($currentWordPressVersion)) . '</p>';
            echo '</div>';
        });
    }

    throw new Exception("Unsupported WordPress version. Plugin that bailed bootstrapping: $pluginFilePath");
}
