<?php

namespace WPStaging\Backend\Pluginmeta;

/*
 *  Admin Plugins Meta Data
 */

// No Direct Access
if (!defined("WPINC")) {
    die;
}

use WPStaging\Core\WPStaging;


class Pluginmeta
{
    // Link to Upgrade WP Staging
    const UPGRADE_LINK = "https://wp-staging.com/premium-upgrade";

    public function __construct()
    {
        $this->defineHooks();
    }

    /**
     * Define Hooks
     */
    public function defineHooks()
    {
        add_filter('plugin_row_meta', [$this, 'rowMeta'], 10, 2);
        add_filter('plugin_action_links', [$this,'actionLinks'], 10, 2);
    }

    /**
     * Plugins row action links
     *
     * @param array $links already defined action links
     * @param string $file plugin file path and name being processed
     * @return array $links
     */
    public function actionLinks($links, $file)
    {
        $upgrade_link = '<a style="color: #27ae60;" target="_blank" href="' . self::UPGRADE_LINK . '">' . esc_html__('Upgrade to Premium', 'wp-staging') . '</a>';
        $freePlugins = [
            'wp-staging/wp-staging.php',
            'wp-staging-1/wp-staging.php'
        ];
        // show only for free version
        // using static plugin paths allow us to show upgrade link even if the plugin is not activated
        if (in_array($file, $freePlugins)) {
            array_unshift($links, $upgrade_link);
        }

        $settings_link = '<a href="' . admin_url('admin.php?page=wpstg-settings') . '">' . esc_html__('Settings', 'wp-staging') . '</a>';
        // show on both free and pro version
        // as WPSTG_PLUGIN_FILE is common for both free and pro version
        // defined during requirement bootstrapping
        // this will now work for wp-staging-dev/wp-staging-pro.php
        // since the settings link will only work if the plugins is activated, it is good to show it this way
        if (defined('WPSTG_PLUGIN_FILE') && $file == plugin_basename(WPSTG_PLUGIN_FILE)) {
            array_unshift($links, $settings_link);
        }

        return $links;
    }

    /**
     * Plugin row meta links
     *
     * @author Michael Cannon <mc@aihr.us>
     * @since 2.0
     * @param array $input already defined meta links
     * @param string $file plugin file path and name being processed
     * @return array $input
     */
    public function rowMeta($input, $file)
    {
        if ($file != 'wp-staging/wp-staging.php' && $file != 'wp-staging-pro/wp-staging-pro.php') {
            return $input;
        }

        $links = [
            '<a href="' . admin_url('admin.php?page=wpstg_clone') . '">' . esc_html__('Start Now', 'wp-staging') . '</a>',
        ];
        $input = array_merge($input, $links);
        return $input;
    }
}
