<?php

namespace WPStaging\Backend\Pluginmeta;

/* Admin Plugins Meta Data */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\PluginInfo;

class Pluginmeta
{
    // Link to Upgrade WP Staging
    const UPGRADE_LINK = "https://wp-staging.com/premium-upgrade";

    /** @var PluginInfo */
    private $pluginInfo;

    public function __construct()
    {
        $this->pluginInfo = WPStaging::make(PluginInfo::class);
        $this->defineHooks();
    }

    /**
     * Define Hooks
     */
    public function defineHooks()
    {
        add_filter('plugin_row_meta', [$this, 'rowMeta'], 10, 2);
        add_filter('plugin_action_links', [$this, 'actionLinks'], 10, 2);
        add_filter('network_admin_plugin_action_links', [$this, 'editFreeActionRow'], 10, 2);
    }

    /**
     * Plugins row action links for the free version
     *
     * @param array $links already defined action links
     * @param string $file plugin file path and name being processed
     * @return array $links
     */
    public function actionLinks(array $links, string $file): array
    {
        $pluginBasename = plugin_basename(WPSTG_PLUGIN_FILE);

        if ($this->isFreePluginSlug($file) && stripos($pluginBasename, 'wp-staging-pro.php') === false) {
            $upgradeLink = '<a style="color: #27ae60;" target="_blank" href="' . self::UPGRADE_LINK . '">' . esc_html__('Upgrade to Premium', 'wp-staging') . '</a>';
            array_unshift($links, $upgradeLink);
        }

        // show on both free and pro version
        // as WPSTG_PLUGIN_FILE is common for both free and pro version
        // defined during requirement bootstrapping
        // this will now work for wp-staging-dev/wp-staging-pro.php
        // since the settings link will only work if the plugins is activated, it is good to show it this way
        if ($this->canShowSettingsLink($file)) {
            $settingsLink = '<a href="' . admin_url('admin.php?page=wpstg-settings') . '">' . esc_html__('Settings', 'wp-staging') . '</a>';
            array_unshift($links, $settingsLink);
        }

        if (stripos($file, 'wp-staging-pro.php')) {
            $updateLink = '<a href="' . esc_url('https://wp-staging.com/quick-start-guide/') . '" target="_blank">' . esc_html__('Quick Guide', 'wp-staging') . '</a>';
            array_push($links, $updateLink);

            $updateLink = '<a href="' . esc_url('https://wp-staging.com/contact-us-presale-and-premium-support/') . '" target="_blank">' . esc_html__('Contact Support', 'wp-staging') . '</a>';
            array_push($links, $updateLink);
        }

        return $this->editFreeActionRow($links, $file);
    }

    /**
     * Check if it's a free plugin slug
     * Checking against hardcoded plugin paths allow us to show an upgrade link even if the plugin is not activated
     *
     * @param $pluginSlug
     * @return bool
     */
    private function isFreePluginSlug($pluginSlug): bool
    {
        $freePluginSlugs = [
            'wp-staging/wp-staging.php',
            'wp-staging-1/wp-staging.php',
            'wp-staging-2/wp-staging.php'
        ];
        return in_array($pluginSlug, $freePluginSlugs);
    }

    /**
     * @param array $links
     * @param string $file
     * @return array
     */
    public function editFreeActionRow(array $links, string $file): array
    {
        if (stripos($file, 'wp-staging.php') === false) {
            return $links;
        }

        if ($this->canShowFreeRequiredNotice()) {
            unset($links['deactivate']);

            $settingsLink = '<a href="' . admin_url('admin.php?page=wpstg-settings') . '">' . esc_html__('Settings', 'wp-staging') . '</a>';
            array_unshift($links, $settingsLink);

            $freeRequireNotice = '<span style="color: #32373c;">' . esc_html__('Required by WP Staging Pro', 'wp-staging') . '</span>';
            array_unshift($links, $freeRequireNotice);
        }

        if (wpstgIsFreeVersionRequiredForPro() && wpstgIsProActiveInNetworkOrInCurrentSite() && version_compare(wpstgGetFreeVersionNumberIfInstalled(), WPSTGPRO_MINIMUM_FREE_VERSION, '<')) {
            unset($links['activate']);
        }

        return $links;
    }

    /**
     * @return bool
     */
    private function canShowFreeRequiredNotice(): bool
    {
        if (!wpstgIsFreeVersionRequiredForPro()) {
            return false;
        }

        $pluginBasename = plugin_basename(WPSTG_PLUGIN_FILE);
        if (stripos($pluginBasename, 'wp-staging-pro.php') === false) {
            return false;
        }

        if (defined('WPSTGPRO_MINIMUM_FREE_VERSION') && version_compare(wpstgGetFreeVersionNumberIfInstalled(), WPSTGPRO_MINIMUM_FREE_VERSION, '<')) {
            return false;
        }

        if (is_network_admin() && !wpstgIsFreeVersionActiveInNetwork()) {
            return false;
        }

        if (!is_network_admin() && !wpstgIsFreeVersionActive()) {
            return false;
        }

        return true;
    }

    /**
     * @param $file
     * @return bool
     */
    private function canShowSettingsLink($file): bool
    {
        if (!defined('WPSTG_PLUGIN_FILE')) {
            return false;
        }

        $pluginBasename = plugin_basename(WPSTG_PLUGIN_FILE);
        if ($file !== $pluginBasename) {
            return false;
        }

        if (!$this->pluginInfo->canShowAdminMenu()) {
            return false;
        }

        return true;
    }

    /**
     * Plugin row meta links
     *
     * @param array $input already defined meta links
     * @param string $file plugin file path and name being processed
     * @return array
     */
    public function rowMeta(array $input, string $file): array
    {
        if ($file != 'wp-staging/wp-staging.php' && $file != 'wp-staging-pro/wp-staging-pro.php') {
            return $input;
        }

        if (!$this->canShowSettingsLink($file)) {
            return $input;
        }

        $links = [
            '<a href="' . admin_url('admin.php?page=wpstg_clone') . '">' . esc_html__('Start Now', 'wp-staging') . '</a>',
        ];
        return array_merge($input, $links);
    }
}
