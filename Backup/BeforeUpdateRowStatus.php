<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Service\BeforeUpdateBackupRequest;
use WPStaging\Backup\Service\UpdateProtectionSettings;
use WPStaging\Framework\Assets\I18n;













class BeforeUpdateRowStatus
{
 
    const ATTRIBUTE = 'data-wpstg-before-update-status';

 
    private $backupRequest;

 
    private $settings;

 
    private $i18n;






    public function __construct(BeforeUpdateBackupRequest $backupRequest, UpdateProtectionSettings $settings, I18n $i18n)
    {
        $this->backupRequest = $backupRequest;
        $this->settings      = $settings;
        $this->i18n          = $i18n;
    }





    public function registerRowMessages()
    {
        if (!$this->settings->isEnabled()) {
            return;
        }

        foreach ($this->getPluginsWithUpdates() as $pluginFile) {
            add_action("in_plugin_update_message-{$pluginFile}", [$this, 'renderAnchor'], 10, 2); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
        }
    }










    public function renderAnchor($pluginData = [], $response = null)
    {
        $pluginFile = '';
        if (is_object($response) && isset($response->plugin)) {
            $pluginFile = (string)$response->plugin;
        }

        printf(
            ' <span class="wpstg-before-update-status" %s="%s">%s</span>',
            esc_attr(self::ATTRIBUTE),
            esc_attr($pluginFile),
            $this->getStatusText($pluginFile)
        );
    }








    private function getStatusText(string $pluginFile): string
    {
        $position = array_search($pluginFile, $this->backupRequest->getPendingPluginFiles(), true);
        if ($pluginFile === '' || $position === false) {
            return '';
        }

        $sentences = $this->i18n->getTranslations()['backup_before_update'];

        return $position === 0 ? $sentences['row_backing_up'] : $sentences['row_queued'];
    }




    private function getPluginsWithUpdates(): array
    {
        $updates = get_site_transient('update_plugins');

        if (!is_object($updates) || !isset($updates->response) || !is_array($updates->response)) {
            return [];
        }

        return array_keys($updates->response);
    }
}
