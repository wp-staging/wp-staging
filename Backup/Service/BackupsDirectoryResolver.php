<?php

namespace WPStaging\Backup\Service;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Hooks;

/**
 * Resolves the backup directory after applying backup directory filters
 */
class BackupsDirectoryResolver
{
    /**
     * Resolve the filtered backup directory from the WordPress uploads directory.
     *
     * @param string $uploadsDirectory Absolute path to WordPress uploads.
     * @return string
     */
    public function resolveFromUploadsDirectory(string $uploadsDirectory): string
    {
        $uploadsDirectory = trim(trailingslashit(wp_normalize_path($uploadsDirectory)));
        $pluginUploadsDir = Hooks::applyFilters(Directory::FILTER_GET_UPLOAD_DIR, wp_normalize_path($uploadsDirectory . WPSTG_PLUGIN_DOMAIN));
        $pluginUploadsDir = Hooks::applyFilters(Directory::FILTER_PLUGIN_UPLOADS_DIRECTORY, $pluginUploadsDir);

        return $this->resolveFromPluginUploadsDirectory($pluginUploadsDir);
    }

    /**
     * Resolve the filtered backup directory from the plugin uploads directory.
     *
     * @param string $pluginUploadsDirectory Absolute path to WP STAGING uploads.
     * @return string
     */
    public function resolveFromPluginUploadsDirectory(string $pluginUploadsDirectory): string
    {
        return $this->resolve(trailingslashit($pluginUploadsDirectory) . Archiver::BACKUP_DIR_NAME);
    }

    /**
     * Resolve the filtered backup directory from the default backup directory.
     *
     * @param string $defaultBackupsDirectory The default path to the directory Backups will be read from and written to.
     * @return string
     */
    public function resolve(string $defaultBackupsDirectory): string
    {
        /**
         * Allows filtering the path to the directory Backups will be written to and read from.
         *
         * Note: changing this directory while there are backups in the previous location will, in
         * fact, hide those Backups from the plugin. The task of moving the Backups left in the previous
         * location(s) is left to the user.
         *
         * By default it uses the folder ABSPATH/wp-content/uploads/wp-staging/backups
         * You can overwrite the path with the filter wpstg.backup.directory.
         * The filtered provided path needs to be an absolute path that is inside the WordPress root (ABSPATH)
         * E.g. If ABSPATH: '/var/www/example.com' then filtered path can be '/var/www/example.com/backups'. It can not be '/var/www/backups'
         *
         * @param string $defaultBackupsDirectory The default path to the directory Backups will be read from and
         *                                        written to.
         */
        $directory = Hooks::applyFilters(BackupsFinder::FILTER_BACKUP_DIRECTORY, $defaultBackupsDirectory);

        return trailingslashit(wp_normalize_path($directory));
    }
}
