<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Backup\Ajax;

use WPStaging\Core\WPStaging;

class FileList extends BaseFileList
{
    /*
     * @return void
     */
    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $listableBackups = $this->getBackups();
        $listableBackups = $this->sortBackups($listableBackups);
        $withTemplate    = !empty($_GET['withTemplate']) && $this->sanitize->sanitizeBool($_GET['withTemplate']); //phpcs:ignore

        // Returns a JSON response
        if (!$withTemplate) {
            wp_send_json($listableBackups);
        }

        // Returns an HTML template
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
