<?php

namespace WPStaging\Framework\Analytics;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\WpAdapter;
use WPStaging\Framework\SiteInfo;

trait WithAnalyticsSiteInfo
{
    public function getAnalyticsSiteInfo()
    {
        /**
         * @var string $wp_version
         * @var int    $wp_db_version
         */
        include ABSPATH . WPINC . '/version.php';
        global $wpdb;

        // eg: 10.4.19-MariaDB-1:10.4.19+maria~focal
        $mysqlInfo = $wpdb->get_var('SELECT VERSION();');

        preg_match('/^[0-9.]+/', $mysqlInfo, $mySqlVersionNumber);

        if (!empty($mySqlVersionNumber)) {
            $mySqlVersionNumber = array_shift($mySqlVersionNumber);
        } else {
            $mySqlVersionNumber = 'UNDEFINED';
        }

        // Normalized engine name, allows us to query them regardless of the raw format.
        if (stripos($mysqlInfo, 'mysql')) {
            $engine = 'MYSQL';
        } elseif (stripos($mysqlInfo, 'mariadb')) {
            $engine = 'MARIADB';
        } elseif (stripos($mysqlInfo, 'azure')) {
            $engine = 'AZURE';
        } elseif (stripos($mysqlInfo, 'postgre')) {
            $engine = 'POSTGRE';
        } elseif (stripos(preg_replace('[^\w]', '', $mysqlInfo), 'microsoftsqlserver')) {
            $engine = 'MICROSOFTSQLSERVER';
        } elseif (stripos($mysqlInfo, 'percona')) {
            $engine = 'PERCONA';
        } elseif (stripos($mysqlInfo, 'oracle')) {
            $engine = 'ORACLE';
        } else {
            $engine = 'UNDEFINED';
        }

        $wpstgSettings = get_option('wpstg_settings', []);

        if (!is_array($wpstgSettings)) {
            $wpstgSettings = [];
        }

        $plugins = $this->getActivePlugins();

        $systemInfo = [
            'is_staging_site' => (int)WPStaging::make(SiteInfo::class)->isStagingSite(),

            'db_copy_query_limit' => !empty($wpstgSettings['queryLimit']) ? $wpstgSettings['queryLimit'] : null,
            'db_sr_limit' => !empty($wpstgSettings['querySRLimit']) ? $wpstgSettings['querySRLimit'] : null,
            'file_copy_limit' => !empty($wpstgSettings['fileLimit']) ? $wpstgSettings['fileLimit'] : null,
            'cpu_priority' => !empty($wpstgSettings['cpuLoad']) ? $wpstgSettings['cpuLoad'] : null,
            'file_copy_batch_size' => !empty($wpstgSettings['batchSize']) ? $wpstgSettings['batchSize'] : null,
            'max_file_size' => !empty($wpstgSettings['maxFileSize']) ? $wpstgSettings['maxFileSize'] : null,
            'optimizer' => !empty($wpstgSettings['optimizer']) ? $wpstgSettings['optimizer'] : null,

            // WP STAGING Settings that are null by default, if they are not present, they are evaluated as FALSY/EMPTY:
            'keep_permalinks' => !empty($wpstgSettings['keepPermalinks']) ? $wpstgSettings['keepPermalinks'] : false,
            'disable_admin_login' => !empty($wpstgSettings['disableAdminLogin']) ? $wpstgSettings['disableAdminLogin'] : false,
            //'delay_between_requests' => !empty($wpstgSettings['delayRequests']) ? $wpstgSettings['delayRequests'] : 0,
            'delay_between_requests' => 0,
            'debug_mode' => !empty($wpstgSettings['debugMode']) ? $wpstgSettings['debugMode'] : false,
            'remove_data_on_uninstall' => !empty($wpstgSettings['unInstallOnDelete']) ? $wpstgSettings['unInstallOnDelete'] : false,
            'check_directory_size' => !empty($wpstgSettings['checkDirectorySize']) ? $wpstgSettings['checkDirectorySize'] : false,
            'access_permission' => !empty($wpstgSettings['userRoles']) ? $wpstgSettings['userRoles'] : [],
            'users_with_staging_access' => !empty($wpstgSettings['usersWithStagingAccess']) ? $wpstgSettings['usersWithStagingAccess'] : '',

            'php_version' => phpversion(),
            'blog_id' => get_current_blog_id(),
            'network_id' => WPStaging::make(WpAdapter::class)->getCurrentNetworkId(),
            'single_or_multi' => is_multisite() ? 'multi' : 'single',
            'wpstaging_free_or_pro' => WPStaging::isPro() ? 'pro' : 'free',
            'wpstaging_version' => WPStaging::getVersion(),
            'operating_system_family' => stripos(PHP_OS, 'WIN') === 0 ? 'WINDOWS' : 'UNIX',
            'operating_system_family_raw' => PHP_OS,
            'active_theme' => get_option('stylesheet') ?: 'UNDEFINED',
            'wordpress_version' => $wp_version,
            'wpdb_version' => $wp_db_version,
            'db_collate' => $wpdb->collate,
            'db_charset' => $wpdb->charset,
            'sql_server_version_number' => $mySqlVersionNumber,
            'sql_server_version_engine' => $engine,
            'sql_server_version_engine_raw' => $mysqlInfo,
            'site_active_plugins' => isset($plugins['siteActive']) ? $plugins['siteActive'] : '',
            'mu_plugins' => isset($plugins['muPlugins']) ? $plugins['muPlugins'] : '',
            'network_active_plugins' => isset($plugins['networkActive']) ? $plugins['networkActive'] : '',
            'php_extensions' => $this->getPhpExtensions(),
        ];

        return $systemInfo;
    }

    protected function getActivePlugins()
    {
        if (!function_exists('get_plugin_data')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $plugins = [
            'siteActive' => [],
            'muPlugins' => [],
            'networkActive' => [],
        ];

        $callback = function () {
            return [];
        };

        $wpPluginDir = wp_normalize_path(WP_PLUGIN_DIR);
        $wpmuPluginDir = wp_normalize_path(WPMU_PLUGIN_DIR);

        // plugins
        add_filter('pre_site_option_active_sitewide_plugins', $callback);

        $plugins = $this->getPlugins();
        foreach ($plugins as $activePlugin) {
            $pluginData = get_plugin_data(WP_PLUGIN_DIR . "/" . $activePlugin);
            $version = array_key_exists('Version', $pluginData) ? $pluginData['Version'] : 'UNDEFINED';
            $name = str_replace($wpPluginDir, '', wp_normalize_path($activePlugin));
            $name = trim($name, '/\\');

            $plugins['siteActive'][$name] = $version;
        }
        remove_filter('pre_site_option_active_sitewide_plugins', $callback);

        // mu-plugins
        foreach (get_mu_plugins() ?: [] as $activeMuPlugin => $pluginData) {
            $version = array_key_exists('Version', $pluginData) ? $pluginData['Version'] : 'UNDEFINED';
            $name = str_replace($wpmuPluginDir, '', wp_normalize_path($activeMuPlugin));
            $name = trim($name, '/\\');

            $plugins['muPlugins'][$name] = $version;
        }

        // networkwide plugins
        if (function_exists('wp_get_active_network_plugins')) {
            foreach (wp_get_active_network_plugins() ?: [] as $activePlugin) {
                $pluginData = get_plugin_data($activePlugin);
                $version = array_key_exists('Version', $pluginData) ? $pluginData['Version'] : 'UNDEFINED';
                $name = str_replace($wpPluginDir, '', wp_normalize_path($activePlugin));
                $name = trim($name, '/\\');

                $plugins['networkActive'][$name] = $version;
            }
        }

        return $plugins;
    }

    protected function getPhpExtensions()
    {
        // Early bail: Not callable
        if (!is_callable('get_loaded_extensions')) {
            return [];
        }

        $phpExtensions = @get_loaded_extensions();

        // Early bail: Unexpected value
        if (!is_array($phpExtensions)) {
            return [];
        }

        return $phpExtensions;
    }

    /**
     * Use this special method to get the list of active plugins, instead of using a core method like wp_get_active_and_valid_plugins() because
     * wp_get_active_and_valid_plugins() does not deliver a result because our mu-plugin wp-staging-optimizer.php filters the active plugins.
     * @return array
     */
    protected function getPlugins()
    {
        global $wpdb;

        $sql = "SELECT option_value FROM " . esc_sql($wpdb->prefix) . "options WHERE option_name = 'active_plugins'";
        $result = $wpdb->get_results($sql, ARRAY_A);
        $result = isset($result[0]['option_value']) ? unserialize($result[0]['option_value']) : [];
        return (array)$result;
    }
}
