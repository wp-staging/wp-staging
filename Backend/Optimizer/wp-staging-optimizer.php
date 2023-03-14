<?php

/*
 * Plugin Name: WP STAGING Optimizer
 * Plugin URI: https://wp-staging.com
 * Description: Prevents 3rd party plugins from being loaded during WP STAGING specific operations.
 *
 * This is a must-use standalone plugin -
 * Do not use any of these methods in WP STAGING code base as this mu-plugin can be missing!
 *
 * Author: René Hermenau
 * Version: 1.5.1
 * Author URI: https://wp-staging.com
 */

// Version number of this mu-plugin. Important for automatic updates
if (!defined('WPSTG_OPTIMIZER_VERSION')) {
    define('WPSTG_OPTIMIZER_VERSION', '1.5.1');
}
if (!function_exists('wpstgGetPluginsDir')) {
    /** @return string */
    function wpstgGetPluginsDir()
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
    /*
     * Check if optimizer is enabled
     * @return bool false if it's disabled
     *
     */
    function wpstgIsEnabledOptimizer()
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
     * Check if certain plugins are excluded and still runing during wp staging requests
     * @return boolean
     */
    function wpstgIsExcludedPlugin($plugin)
    {
        $excludedPlugins = get_option('wpstg_optimizer_excluded', []);

        if (empty($excludedPlugins)) {
            return false;
        }

        // Check for custom excluded plugins
        foreach ($excludedPlugins as $excludedPlugin) {
            if (strpos($plugin, $excludedPlugin) !== false) {
                return true;
            } else {
                continue;
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
            return $plugins;
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
            return $plugins;
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

    add_filter('site_option_active_sitewide_plugins', 'wpstgExcludeSitePlugins');
}


if (!function_exists('wpstgDisableTheme')) {
    /**
     *
     * Disables the theme during WP Staging AJAX requests
     *
     *
     * @param $dir
     *
     * @return string
     */
    function wpstgDisableTheme($dir)
    {
        $enableTheme = apply_filters('wpstg_optimizer_enable_theme', false);

        if (wpstgIsOptimizerRequest() && $enableTheme === false) {
            $wpstgRootPro = wpstgGetPluginsDir() . 'wp-staging-pro';
            $wpstgRoot = wpstgGetPluginsDir() . 'wp-staging';

            $file = DIRECTORY_SEPARATOR . 'Backend' . DIRECTORY_SEPARATOR . 'Optimizer' . DIRECTORY_SEPARATOR . 'blank-theme' . DIRECTORY_SEPARATOR . 'functions.php';
            $theme = DIRECTORY_SEPARATOR . 'Backend' . DIRECTORY_SEPARATOR . 'Optimizer' . DIRECTORY_SEPARATOR . 'blank-theme';


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
    function wpstgIsOptimizerRequest()
    {
        if (!wpstgIsEnabledOptimizer()) {
            return false;
        }

        if (
            defined('DOING_AJAX') &&
            DOING_AJAX &&
            isset($_REQUEST['action']) &&
            strpos(sanitize_text_field($_REQUEST['action']), 'wpstg_send_report') === false &&
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
        $remove_function = false;

        // run on wpstg page
        if (isset($_GET['page']) && $_GET['page'] == 'wpstg_clone') {
            $remove_function = true;
        }
        // run on wpstg ajax requests
        if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && strpos(sanitize_text_field($_POST['action']), 'wpstg') !== false) {
            $remove_function = true;
        }

        if ($remove_function) {
            global $wp_filter;
            $admin_init_functions = $wp_filter['admin_init'];
            foreach ($admin_init_functions as $priority => $functions) {
                foreach ($functions as $key => $function) {
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
     * @return bool True if is staging site. False otherwise.
     */
    function wpstgIsStaging()
    {
        if (file_exists(ABSPATH . '.wp-staging-cloneable')) {
            return false;
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
     * @param string $option
     * @return mixed
     */
    function wpstgGetCloneSettings($option = null)
    {
        $settings = get_option('wpstg_clone_settings', null);

        // Return settings if no options given
        if ($option === null) {
            return $settings;
        }

        // Early Bail: if settings is null or if settings isn't object
        if ($settings === null || !is_object($settings)) {
            return null;
        }

        // Early bail if given option not exists
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
if (wpstgIsStaging() && (((bool)get_option("wpstg_emails_disabled") === true) || ((bool)wpstgGetCloneSettings('wpstg_emails_disabled')))) {
    if (!function_exists('wp_mail')) {
        function wp_mail($to, $subject, $message, $headers = '', $attachments = [])
        {
            if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                // Safely cast everything to string
                $to = is_string($to) ? $to : wp_json_encode($to);
                $subject = is_string($subject) ? $subject : wp_json_encode($subject);
                $message = is_string($message) ? $message : wp_json_encode($message);
                $headers = is_string($headers) ? $headers : wp_json_encode($headers);
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
        }
    } else {
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            error_log("WP STAGING: Could not override the wp_mail() function to disable e-mails on staging site, as it was already defined before the optimizer mu-plugin was loaded.");
        }
    }
}
