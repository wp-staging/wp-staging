<?php

namespace WPStaging\Backup\Service;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Hooks;




class BackupsDirectoryResolver
{






    public function resolveFromUploadsDirectory(string $uploadsDirectory): string
    {
        $uploadsDirectory = trim(trailingslashit(wp_normalize_path($uploadsDirectory)));
        $pluginUploadsDir = Hooks::applyFilters(Directory::FILTER_GET_UPLOAD_DIR, wp_normalize_path($uploadsDirectory . WPSTG_PLUGIN_DOMAIN));
        $pluginUploadsDir = Hooks::applyFilters(Directory::FILTER_PLUGIN_UPLOADS_DIRECTORY, $pluginUploadsDir);

        return $this->resolveFromPluginUploadsDirectory($pluginUploadsDir);
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
