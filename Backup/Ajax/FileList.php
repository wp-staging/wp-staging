<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\Ajax\FileList\ListableBackupsCollection;
use WPStaging\Backup\Entity\ListableBackup;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Sanitize;

class FileList extends AbstractTemplateComponent
{
    /** @var ListableBackupsCollection */
    private $listableBackupsCollection;

    /** @var Sanitize */
    private $sanitize;

    public function __construct(ListableBackupsCollection $listableBackupsCollection, TemplateEngine $templateEngine, Sanitize $sanitize)
    {
        parent::__construct($templateEngine);
        $this->listableBackupsCollection = $listableBackupsCollection;
        $this->sanitize = $sanitize;
    }

    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        // Discover the .wpstg backups in the filesystem
        try {
            $listableBackups = $this->listableBackupsCollection->getListableBackups();
        } catch (\Exception $e) {// TODO: remove the double catch and switch with Throwable when the support of php 5.6 is dropped!
            ob_end_clean();
            if (wp_doing_ajax()) {
                wp_send_json_error(
                    __(
                        "Failed to get the list of backups!",
                        'wp-staging'
                    )
                );
            }
        } catch (\Error $e) {
            ob_end_clean();
            if (wp_doing_ajax()) {
                wp_send_json_error(
                    __(
                        "Failed to get the list of backups!",
                        'wp-staging'
                    )
                );
            }
        }

        /**
         * Javascript expects an array with keys in natural order
         *
         * @var ListableBackup[] $listableBackups
         */
        $listableBackups = array_values($listableBackups);

        // Sort backups by the highest created/upload date, newest first.
        usort($listableBackups, function ($item, $nextItem) {
            /**
             * @var ListableBackup $item
             * @var ListableBackup $nextItem
             */
            return (max($nextItem->dateUploadedTimestamp, $nextItem->dateCreatedTimestamp)) - (max($item->dateUploadedTimestamp, $item->dateCreatedTimestamp));
        });

        // Returns a HTML template
        if (isset($_GET['withTemplate']) && $this->sanitize->sanitizeBool($_GET['withTemplate'])) {
            $output = '';

            $urlAssets         = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';
            $isProVersion      = WPStaging::isPro();
            $isValidLicenseKey = WPStaging::isValidLicense();

            if (empty($listableBackups) || ($isProVersion && !$isValidLicenseKey)) {
                $output .= $this->renderTemplate('Backend/views/backup/listing-backups-no-results.php', [
                    'urlAssets'         => $urlAssets,
                    'isProVersion'      => $isProVersion,
                    'isValidLicenseKey' => $isValidLicenseKey,
                ]);
            } else {
                $output .= sprintf('<h3>%s</h3>', __('Your Backups:', 'wp-staging'));

                /** @var ListableBackup $listable */
                foreach ($listableBackups as $listable) {
                    $viewData = [
                        'backup'    => $listable,
                        'urlAssets' => $urlAssets,
                    ];

                    $output .= $this->renderTemplate(
                        'Backend/views/backup/listing-single-backup.php',
                        $viewData
                    );
                }
            }

            wp_send_json($output);
        }

        // Returns a JSON response
        wp_send_json($listableBackups);
    }
}
