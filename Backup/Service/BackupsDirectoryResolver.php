<?php

namespace WPStaging\Backup\Service;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Hooks;




class BackupsDirectoryResolver
{






    public function resolveFromUploadsDirectory(string $uploadsDirectory): string
    {
        return $this->resolveFromPluginUploadsDirectory(WPStaging::make(Directory::class)->resolvePluginUploadsDirectory($uploadsDirectory));
    }







    public function resolveFromPluginUploadsDirectory(string $pluginUploadsDirectory): string
    {
        return $this->resolve(trailingslashit($pluginUploadsDirectory) . Archiver::BACKUP_DIR_NAME);
    }







    public function resolve(string $defaultBackupsDirectory): string
    {















        $directory = Hooks::applyFilters(BackupsFinder::FILTER_BACKUP_DIRECTORY, $defaultBackupsDirectory);

        return trailingslashit(wp_normalize_path($directory));
    }
}
