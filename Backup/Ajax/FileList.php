<?php

 
 

namespace WPStaging\Backup\Ajax;

use WPStaging\Core\WPStaging;

class FileList extends BaseFileList
{



    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $listableBackups = $this->getBackups();
        $listableBackups = $this->sortBackups($listableBackups);
        $withTemplate    = !empty($_GET['withTemplate']) && $this->sanitize->sanitizeBool($_GET['withTemplate']); //phpcs:ignore

 
        if (!$withTemplate) {
            wp_send_json($listableBackups);
        }

 
        $output = '';
        if (empty($listableBackups)) {
            $output .= $this->renderTemplate('backup/listing-backups-no-results.php', [
                'urlAssets'         => $this->urlAssets,
                'isProVersion'      => false,
                'isValidLicenseKey' => false,
            ]);
        } else {
            $output .= $this->renderBackups($listableBackups);
        }

        wp_send_json($output);
    }
}
