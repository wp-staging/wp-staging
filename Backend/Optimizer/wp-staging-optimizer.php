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
 * Version: 1.6.0
 * Author URI: https://wp-staging.com
 * Text Domain: wp-staging
 */

// Version number of this mu-plugin. Important for automatic updates
// Important: Update WPSTG_OPTIMIZER_MUVERSION in /bootstrap.php to the same version!

if (!defined('WPSTG_OPTIMIZER_VERSION')) {
    define('WPSTG_OPTIMIZER_VERSION', '1.6.0');
}

if (!function_exists('wpstgGetPluginsDir')) {
    /** @return string */
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
    /**
     * Check if optimizer is enabled
     * @return bool
     */
    function wpstgIsEnabledOptimizer(): bool
    {
        $status = (object)get_option('wpstg_settings');

        if ($status && isset($status->optimizer) && $status->optimizer == 1) {
            return true;
        }

        return false;
    }
}

if (!function_exists('wpstgIsExcludedPlugin')) {
    /**
     * Check if a plugin should be excluded from the optimizer and still running during wp staging requests
     * @param string $plugin
     * @return bool
     */
    function wpstgIsExcludedPlugin(string $plugin): bool
    {
        $excludedPlugins = get_option('wpstg_optimizer_excluded', []);

        // Check for custom excluded plugins
        foreach ($excludedPlugins as $excludedPlugin) {
            if (strpos($plugin, $excludedPlugin) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('wpstgExcludePlugins')) {
    /**
     * Remove all plugins except wp-staging and wp-staging-pro from blog-active plugins
     *
     * @param array $plugins numerically keyed array of plugin names (index=>name)
     *
     * @return array
     */
    function wpstgExcludePlugins($plugins)
    {
        if (!is_array($plugins) || empty($plugins)) {
            return [];
        }

        if (!wpstgIsOptimizerRequest()) {
            return $plugins;
        }

        foreach ($plugins as $key => $plugin) {
            // Default filter. Must be at the beginning or wp staging plugin will be filtered and killed
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
    /**
     * Remove all plugins except wp-staging and wp-staging-pro from network-active plugins
     *
     * @param array $plugins array of plugins keyed by name (name=>timestamp pairs)
     *
     * @return array
     */
    function wpstgExcludeSitePlugins($plugins)
    {
        if (!is_array($plugins) || empty($plugins)) {
            return [];
        }

        if (!wpstgIsOptimizerRequest()) {
            return $plugins;
        }

        foreach ($plugins as $plugin => $timestamp) {
            // Default filter. Must be at the beginning or wp staging plugin will be filtered and killed
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

if (!function_exists('wpstgDisableTheme')) {
    /**
     *
     * Disables the active theme during WP Staging AJAX requests
     *
     *
     * @param string $dir
     *
     * @return string
     */
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
    /**
     * Should the current request be processed by optimizer?
     *
     * For WP STAGING requests that require other plugins to be active, use raw_wpstg_{actionName}
     *
     * @return bool
     */
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
    /**
     * Remove TGM Plugin Activation 'force_activation' admin_init action hook if present.
     *
     * This is to stop excluded plugins being deactivated after a migration, when a theme uses TGMPA to require a plugin to be always active.
     */
    function wpstgTgmpaCompatibility()
    {
        $isFunctionRemoved = false;

        // run on wpstg page
        if (isset($_GET['page']) && $_GET['page'] == 'wpstg_clone') {
            $isFunctionRemoved = true;
        }

        // run on wpstg ajax requests
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

                    // searching for function this way as can't rely on the calling class being named TGM_Plugin_Activation
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
    /**
     * @return bool True if it is staging site. False otherwise.
     */
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
    /**
     * Get the value of the given option in clone settings,
     * If no option given return all clone settings
     *
     * @param string|null $option
     * @return mixed
     */
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

/**
 * Disable all outgoing e-mails on Staging site
 * Will check against both the old and new logic of storing emails disabled option
 */
if (wpstgIsStaging() && (((bool)get_option("wpstg_emails_disabled") === true) || (wpstgGetCloneSettings('wpstg_emails_disabled')))) {
    if (!function_exists('wp_mail')) {
        /**
         * @param array|string $to
         * @param string       $subject
         * @param string       $message
         * @param array|string $headers
         * @param array|string $attachments
         *
         * @return bool
         */
        function wp_mail($to, $subject, $message, $headers = '', $attachments = [])
        {
            if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                // Safely cast everything to string
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
