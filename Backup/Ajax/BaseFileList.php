<?php

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\Ajax\FileList\ListableBackupsCollection;
use WPStaging\Backup\Entity\ListableBackup;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Sanitize;

abstract class BaseFileList extends AbstractTemplateComponent
{
 
    protected $listableBackupsCollection;

 
    protected $sanitize;




    protected $urlAssets;

    public function __construct(ListableBackupsCollection $listableBackupsCollection, TemplateEngine $templateEngine, Sanitize $sanitize)
    {
        parent::__construct($templateEngine);
        $this->listableBackupsCollection = $listableBackupsCollection;
        $this->sanitize                  = $sanitize;
        $this->urlAssets                 = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';
    }




    protected function getBackups(): array
    {
        try {
            $backups = $this->listableBackupsCollection->getListableBackups();
        } catch (\Throwable $e) {
            ob_end_clean();
            if (wp_doing_ajax()) {
                wp_send_json_error(__("Failed to get the list of backups!", 'wp-staging'));
            }

            return [];
        }

 
        return array_values($backups);
    }





    protected function sortBackups(array $listableBackups): array
    {
        usort($listableBackups, function ($item, $nextItem) {




            return (max($nextItem->dateUploadedTimestamp, $nextItem->dateCreatedTimestamp)) - (max($item->dateUploadedTimestamp, $item->dateCreatedTimestamp));
        });

        return $listableBackups;
    }





    protected function renderBackups(array $backups): string
    {
        $output = '';

        foreach ($backups as $backup) {
            $viewData = [
                'backup'    => $backup,
                'urlAssets' => $this->urlAssets,
            ];

            $output .= $this->renderTemplate('backup/listing-single-backup.php', $viewData);
        }

        return $output;
    }
}
