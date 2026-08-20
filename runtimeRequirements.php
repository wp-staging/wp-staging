<?php





$pluginFilePath = $pluginFilePath ?? '';
 
require_once(trailingslashit(ABSPATH) . 'wp-admin/includes/plugin.php');

if (!defined('WPSTGPRO_MINIMUM_FREE_VERSION')) {








    define('WPSTGPRO_MINIMUM_FREE_VERSION', '3.8.0');
}

if (!defined('WPSTG_FREE_VERSION_PLUGIN_FILE')) {
    define('WPSTG_FREE_VERSION_PLUGIN_FILE', 'wp-staging.php');
}

if (!defined('WPSTG_PRO_VERSION_PLUGIN_FILE')) {
    define('WPSTG_PRO_VERSION_PLUGIN_FILE', 'wp-staging-pro.php');
}

if (!function_exists('wpstgIsProPluginActive')) {



    function wpstgIsProPluginActive(): bool
    {
        return wpstgIsPluginActivated(WPSTG_PRO_VERSION_PLUGIN_FILE);
    }
}

if (!function_exists('wpstgIsProPluginActiveInNetwork')) {



    function wpstgIsProPluginActiveInNetwork(): bool
    {
        return wpstgIsPluginActiveInNetwork(WPSTG_PRO_VERSION_PLUGIN_FILE);
    }
}

if (!function_exists('wpstgIsFreeVersionRequiredForPro')) {



    function wpstgIsFreeVersionRequiredForPro(): bool
    {
        return apply_filters('wpstg.free_required_by_pro', true);
    }
}

if (!function_exists('wpstgIsProActiveInNetworkOrInCurrentSite')) {



    function wpstgIsProActiveInNetworkOrInCurrentSite(): bool
    {
        return wpstgIsProPluginActiveInNetwork() || wpstgIsProPluginActive();
    }
}

if (!function_exists('wpstgIsFreeVersionActive')) {



    function wpstgIsFreeVersionActive(): bool
    {
        return wpstgIsPluginActivated(WPSTG_FREE_VERSION_PLUGIN_FILE);
    }
}

if (!function_exists('wpstgIsFreeVersionActiveInNetwork')) {



    function wpstgIsFreeVersionActiveInNetwork(): bool
    {
        return wpstgIsPluginActiveInNetwork(WPSTG_FREE_VERSION_PLUGIN_FILE);
    }
}

if (!function_exists('wpstgIsFreeActiveInNetworkOrCurrentSite')) {



    function wpstgIsFreeActiveInNetworkOrCurrentSite(): bool
    {
        return wpstgIsFreeVersionActiveInNetwork() || wpstgIsFreeVersionActive();
    }
}

if (!function_exists('wpstgGetPluginSlug')) {





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



    function wpstgGetFreeVersionNumberIfInstalled(): string
    {
        $freeData                   = wpstgGetPluginData(WPSTG_FREE_VERSION_PLUGIN_FILE);
        $installedFreeVersionNumber = isset($freeData['Version']) ? $freeData['Version'] : '';

        return $installedFreeVersionNumber;
    }
}

if (!function_exists('wpstgGetProVersionNumberIfInstalled')) {



    function wpstgGetProVersionNumberIfInstalled(): string
    {
        $freeData                   = wpstgGetPluginData(WPSTG_PRO_VERSION_PLUGIN_FILE);
        $installedFreeVersionNumber = isset($freeData['Version']) ? $freeData['Version'] : '';

        return $installedFreeVersionNumber;
    }
}

if (!function_exists('wpstgIsFreeVersionCompatible')) {



    function wpstgIsFreeVersionCompatible(): bool
    {
        return defined('WPSTGPRO_MINIMUM_FREE_VERSION') && version_compare(wpstgGetFreeVersionNumberIfInstalled(), WPSTGPRO_MINIMUM_FREE_VERSION, '>=');
    }
}

if (!function_exists('wpstgIsFreeActiveButOutdated')) {



    function wpstgIsFreeActiveButOutdated(): bool
    {
        if (wpstgIsFreeActiveInNetworkOrCurrentSite() && !wpstgIsFreeVersionCompatible()) {
            return true;
        }

        return false;
    }
}

if (!function_exists('wpstgDeactivatePlugin')) {




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




if (strpos($pluginFilePath, 'wp-staging-pro.php') !== false && wpstgIsFreeActiveButOutdated()) {
 
    $pluginSlug = wpstgGetPluginSlug(WPSTG_FREE_VERSION_PLUGIN_FILE);
    wpstgDeactivatePlugin($pluginSlug);
}









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




if (
 
    class_exists('\WPStaging\WPStaging') ||
 
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
