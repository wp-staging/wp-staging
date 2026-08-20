<?php

namespace WPStaging\Framework\Adapter;

use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\Filesystem;

class Maintenance
{
 
    const FILTER_ENABLE_MAINTENANCE = 'enable_maintenance_mode';

 
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
