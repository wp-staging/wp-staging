<?php

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\BackupScheduler;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

abstract class BaseListing extends AbstractTemplateComponent
{



    protected $directory;




    protected $backupScheduler;






    public function __construct(BackupScheduler $backupScheduler, Directory $directory, TemplateEngine $templateEngine)
    {
        parent::__construct($templateEngine);
        $this->backupScheduler = $backupScheduler;
        $this->directory = $directory;
    }




    protected function getDirectories(): array
    {
        return [
            'uploads'   => $this->directory->getUploadsDirectory(),
            'themes'    => trailingslashit(get_theme_root()),
            'plugins'   => trailingslashit(WP_PLUGIN_DIR),
            'muPlugins' => trailingslashit(WPMU_PLUGIN_DIR),
            'wpContent' => trailingslashit(WP_CONTENT_DIR),
            'wpStaging' => $this->directory->getPluginUploadsDirectory(),
        ];
    }




    protected function getCommonRenderData(): array
    {
        return [
            'directory'   => $this->directory,
            'urlAssets'   => trailingslashit(WPSTG_PLUGIN_URL) . 'assets/',
            'hasSchedule' => count($this->backupScheduler->getSchedules()) > 0,
        ];
    }

    abstract protected function getTemplate();
}
