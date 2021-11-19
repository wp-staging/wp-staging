<?php

namespace WPStaging\Framework\Adapter;

use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\Filesystem;

class Maintenance
{
    const FILE_NAME = '.maintenance';

    public function isMaintenance()
    {
        return file_exists($this->findMaintenanceFilePath());
    }

    public function enableMaintenance($isMaintenance)
    {
        $maintenanceFile = $this->findMaintenanceFilePath();
        $fileExists = $this->isMaintenance();
        if ($isMaintenance && !$fileExists) {
            // Perhaps maintenance.php in WP_CONTENT?
            (new FileObject($maintenanceFile, FileObject::MODE_WRITE))->fwriteSafe('<?php $upgrading = time() ?>');
            return;
        }

        if (!$isMaintenance && $fileExists) {
            (new Filesystem())->delete($maintenanceFile);
        }
    }

    private function findMaintenanceFilePath()
    {
        return ABSPATH . self::FILE_NAME;
    }
}
