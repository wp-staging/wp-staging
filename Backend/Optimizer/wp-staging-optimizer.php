<?php

/*
 * Plugin Name: WP STAGING Optimizer
 * Plugin URI: https://wp-staging.com
 * Description: Prevents 3rd party plugins from being loaded during WP STAGING specific operations.
 *
 * This is a must-use standalone plugin -
 * Do not use any of these methods in WP STAGING code base as this mu-plugin can be missing!
 *
 * Do not implement PHP type declarations into this file!
 * This can lead to fatal errors due to mixed return results in the used wp core functions.
 * See https://github.com/wp-staging/wp-staging-pro/issues/2830
 *
 * Author: WP STAGING
 * Version: 1.6.2
 * Author URI: https://wp-staging.com
 * Text Domain: wp-staging
 */

 
 

if (!defined('WPSTG_OPTIMIZER_VERSION')) {
    define('WPSTG_OPTIMIZER_VERSION', '1.6.2');
}

if (!function_exists('wpstgGetPluginsDir')) {
 
    function wpstgGetPluginsDir(): string
    {
        $pluginsDir = '';
        if (defined('WP_PLUGIN_DIR')) {
            $pluginsDir = trailingslashit(WP_PLUGIN_DIR);
        } elseif (defined('WP_CONTENT_DIR')) {
            $pluginsDir = trailingslashit(WP_CONTENT_DIR) . 'plugins/';
        }

        return $pluginsDir;
    }
}

if (!function_exists('wpstgIsEnabledOptimizer')) {




    function wpstgIsEnabledOptimizer(): bool
    {
        $status = (object)get_option('wpstg_settings');

        if ($status && isset($status->optimizer) && $status->optimizer == 1) {
            return true;
        }

        return false;
    }
}

if (!function_exists('wpstgIsGoDaddyManagedSite')) {




    function wpstgIsGoDaddyManagedSite(): bool
    {
        $muPluginDir = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins';
        $isGoDaddy   = is_dir($muPluginDir . '/vendor/godaddy/') || is_dir($muPluginDir . '/gd-system-plugin');

        return $isGoDaddy;
    }
}

if (!function_exists('wpstgIsExcludedPlugin')) {





    function wpstgIsExcludedPlugin(string $plugin): bool
    {
 
 
        if (wpstgIsGoDaddyManagedSite() && in_array($plugin, ['woocommerce/woocommerce.php', 'action-scheduler/action-scheduler.php'], true)) {
            return true;
        }

        $excludedPlugins = get_option('wpstg_optimizer_excluded', []);

 
        foreach ($excludedPlugins as $excludedPlugin) {
            if (strpos($plugin, $excludedPlugin) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('wpstgExcludePlugins')) {







    function wpstgExcludePlugins($plugins)
    {
        if (!is_array($plugins) || empty($plugins)) {
            return [];
        }

        if (!wpstgIsOptimizerRequest()) {
            return $plugins;
        }

        foreach ($plugins as $key => $plugin) {
 
            if (strpos($plugin, 'wp-staging') !== false || wpstgIsExcludedPlugin($plugin)) {
                continue;
            }

            unset($plugins[$key]);
        }


        return $plugins;
    }

    add_filter('option_active_plugins', 'wpstgExcludePlugins');
}

if (!function_exists('wpstgExcludeSitePlugins')) {







    function wpstgExcludeSitePlugins($plugins)
    {
        if (!is_array($plugins) || empty($plugins)) {
            return [];
        }

        if (!wpstgIsOptimizerRequest()) {
            return $plugins;
        }

        foreach ($plugins as $plugin => $timestamp) {
 
            if (strpos($plugin, 'wp-staging') !== false || wpstgIsExcludedPlugin($plugin)) {
                continue;
            }

            unset($plugins[$plugin]);
        }

        return $plugins;
    }

    if (is_multisite()) {
        add_filter('site_option_active_sitewide_plugins', 'wpstgExcludeSitePlugins');
    }
}

if (!function_exists('wpstgDisableActivePluginsFilterAfterLoad')) {









    function wpstgDisableActivePluginsFilterAfterLoad()
    {
        remove_filter('option_active_plugins', 'wpstgExcludePlugins');
        remove_filter('site_option_active_sitewide_plugins', 'wpstgExcludeSitePlugins');
    }

    add_action('plugins_loaded', 'wpstgDisableActivePluginsFilterAfterLoad');
}

if (!function_exists('wpstgDisableTheme')) {









    function wpstgDisableTheme($dir)
    {
        $enableTheme = apply_filters('wpstg_optimizer_enable_theme', false);

        if (wpstgIsOptimizerRequest() && $enableTheme === false) {
            $wpstgRootPro = wpstgGetPluginsDir() . 'wp-staging-pro';
            $wpstgRoot    = wpstgGetPluginsDir() . 'wp-staging';

            $theme = '/resources/blank-theme';
            $file  = $theme . '/functions.php';

            if (file_exists($wpstgRoot . $file)) {
                return $wpstgRoot . $theme;
            } elseif (file_exists($wpstgRootPro . $file)) {
                return $wpstgRootPro . $theme;
            } else {
                return '';
            }
        }

        return $dir;
    }

    add_filter('stylesheet_directory', 'wpstgDisableTheme');
    add_filter('template_directory', 'wpstgDisableTheme');
}

if (!function_exists('wpstgIsOptimizerRequest')) {







    function wpstgIsOptimizerRequest(): bool
    {
        if (!wpstgIsEnabledOptimizer()) {
            return false;
        }

        if (isset($_POST['wpstg_action']) && sanitize_text_field($_POST['wpstg_action']) === 'bypass_optimizer') {
            return false;
        }

        if (
            defined('DOING_AJAX') &&
            DOING_AJAX &&
            isset($_REQUEST['action']) &&
            strpos(sanitize_text_field($_REQUEST['action']), 'wpstg_send_report') === false &&
            strpos(sanitize_text_field($_REQUEST['action']), 'wpstg--send--otp') === false &&
            strpos(sanitize_text_field($_REQUEST['action']), 'wpstg') === 0
        ) {
            return true;
        }

        return false;
    }
}

if (!function_exists('wpstgTgmpaCompatibility')) {





    function wpstgTgmpaCompatibility()
    {
        $isFunctionRemoved = false;

 
        if (isset($_GET['page']) && $_GET['page'] == 'wpstg_clone') {
            $isFunctionRemoved = true;
        }

 
        if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && strpos(sanitize_text_field($_POST['action']), 'wpstg') !== false) {
            $isFunctionRemoved = true;
        }

        if ($isFunctionRemoved) {
            global $wp_filter;

            $adminInitFunctions = $wp_filter['admin_init'];
            foreach ($adminInitFunctions as $priority => $functions) {
                if (!isset($wp_filter['admin_init'][$priority])) {
                    continue;
                }

                foreach ($functions as $key => $function) {
                    if (!isset($wp_filter['admin_init'][$priority][$key])) {
                        continue;
                    }

 
                    if (strpos($key, 'force_activation') !== false) {
                        unset($wp_filter['admin_init'][$priority][$key]);

                        return;
                    }
                }
            }
        }
    }

    add_action('admin_init', 'wpstgTgmpaCompatibility', 1);
}

if (!function_exists('wpstgIsStaging')) {



    function wpstgIsStaging(): bool
    {
        if (defined('WPSTAGING_DEV_SITE') && constant('WPSTAGING_DEV_SITE') === true) {
            return true;
        }

        if (get_option("wpstg_is_staging_site") === "true") {
            return true;
        }

        if (file_exists(ABSPATH . '.wp-staging')) {
            return true;
        }

        return false;
    }
}

if (!function_exists('wpstgGetCloneSettings')) {







    function wpstgGetCloneSettings($option = null)
    {
        $settings = get_option('wpstg_clone_settings', null);

        if (!is_string($option)) {
            return $settings;
        }

        if (!is_object($settings)) {
            return null;
        }

        if (!property_exists($settings, $option)) {
            return null;
        }

        return $settings->{$option};
    }
}





if (wpstgIsStaging() && (((bool)get_option("wpstg_emails_disabled") === true) || (wpstgGetCloneSettings('wpstg_emails_disabled')))) {
    if (!function_exists('wp_mail')) {









        function wp_mail($to, $subject, $message, $headers = '', $attachments = [])
        {
            if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
 
                $to          = is_string($to) ? $to : wp_json_encode($to);
                $subject     = is_string($subject) ? $subject : wp_json_encode($subject);
                $message     = is_string($message) ? $message : wp_json_encode($message);
                $headers     = is_string($headers) ? $headers : wp_json_encode($headers);
                $attachments = is_string($attachments) ? $attachments : wp_json_encode($attachments);

                $log_entry = <<<LOG_ENTRY
/***
* WPSTAGING - Mails are disabled for this Staging site. This e-mail was going to be sent, but was prevented:
***
* To: $to
***
* Subject: $subject
***
* Message: $message
***
* Headers: $headers
***
* Attachments: $attachments
***/
LOG_ENTRY;

                error_log($log_entry);
            }

            return false;
        }
    } elseif (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
        error_log("WP STAGING: Could not override the wp_mail() function to disable e-mails on staging site, as it was already defined before the optimizer mu-plugin was loaded.");
    }
}
