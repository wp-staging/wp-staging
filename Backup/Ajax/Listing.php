<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\BackupScheduler;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

class Listing extends AbstractTemplateComponent
{
    /** @var Directory */
    private $directory;

    /** @var BackupScheduler */
    private $backupScheduler;

    public function __construct(BackupScheduler $backupScheduler, Directory $directory, TemplateEngine $templateEngine)
    {
        parent::__construct($templateEngine);
        $this->backupScheduler = $backupScheduler;
        $this->directory       = $directory;
    }

    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        if (!WPStaging::isPro() && is_multisite()) {
            $result = $this->templateEngine->render('Backend/views/backup/free-version.php');
        } else {
            $directories = [
                'uploads' => $this->directory->getUploadsDirectory(),
                'themes'  => trailingslashit(get_theme_root()),
                'plugins' => trailingslashit(WP_PLUGIN_DIR),
                'muPlugins' => trailingslashit(WPMU_PLUGIN_DIR),
                'wpContent' => trailingslashit(WP_CONTENT_DIR),
                'wpStaging' => $this->directory->getPluginUploadsDirectory(),
            ];

            $result = $this->templateEngine->render(
                'Backend/views/backup/listing.php',
                [
                    'directory' => $this->directory,
                    'urlAssets' => trailingslashit(WPSTG_PLUGIN_URL) . 'assets/',
                    'directories' => $directories,
                    'hasSchedule' => count($this->backupScheduler->getSchedules()) > 0,
                    'isProVersion'   => WPStaging::isPro(),
                    'isValidLicense' => WPStaging::isValidLicense(),
                ]
            );
        }

        wp_send_json($result);
    }
}
