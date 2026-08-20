<?php

namespace WPStaging\Backend\Pluginmeta;

 

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Language\Language;
use WPStaging\Framework\Utils\PluginInfo;

class Pluginmeta
{
 
    private $pluginInfo;

    public function __construct()
    {
        $this->pluginInfo = WPStaging::make(PluginInfo::class);
        $this->defineHooks();
    }




    public function defineHooks()
    {
        add_filter('plugin_row_meta', [$this, 'rowMeta'], 10, 2);
        add_filter('plugin_action_links', [$this, 'actionLinks'], 10, 2);
        add_filter('network_admin_plugin_action_links', [$this, 'editFreeActionRow'], 10, 2);
    }








    public function actionLinks(array $links, string $file): array
    {
        $pluginBasename = plugin_basename(WPSTG_PLUGIN_FILE);

        if ($this->isFreePluginSlug($file) && stripos($pluginBasename, 'wp-staging-pro.php') === false) {
            $upgradeLink = '<a style="color: #27ae60;" target="_blank" href="' . esc_url(Language::getUpgradeUrl('plugin_row')) . '">' . esc_html__('Upgrade to Premium', 'wp-staging') . '</a>';
            array_unshift($links, $upgradeLink);
        }

 
 
 
 
 
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








    private function isFreePluginSlug($pluginSlug): bool
    {
        $freePluginSlugs = [
            'wp-staging/wp-staging.php',
            'wp-staging-1/wp-staging.php',
            'wp-staging-2/wp-staging.php',
        ];
        return in_array($pluginSlug, $freePluginSlugs);
    }






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
