<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Backup\Task\FileRestoreTask;
use WPStaging\Framework\Facades\Hooks;

class RestorePluginsTask extends FileRestoreTask
{




    const FILTER_REPLACE_EXISTING_PLUGINS = 'wpstg.backup.restore.replace_existing_plugins';





    const FILTER_KEEP_EXISTING_PLUGINS = 'wpstg.backup.restore.keepExistingPlugins';

 
    const FILTER_IMPORT_PLUGINS_DEST_DIR = 'wpstg.import.plugins.destDir';




    const SLUG_W3_TOTAL_CACHE = 'w3-total-cache';

    public static function getTaskName()
    {
        return 'backup_restore_plugins';
    }

    public static function getTaskTitle()
    {
        return 'Restoring Plugins';
    }

    protected function isSkipped(): bool
    {
        return $this->isBackupPartSkipped(PartIdentifier::PLUGIN_PART_IDENTIFIER);
    }




    protected function getParts(): array
    {
        return $this->jobDataDto->getBackupMetadata()->getMultipartMetadata()->getPluginsParts();
    }

    protected function buildQueue()
    {
        try {
            $pluginsToRestore = $this->getPluginsToRestore();
        } catch (\Exception $e) {
 
            $pluginsToRestore = [];
        }

        $destDir = $this->directory->getPluginsDirectory();

        try {
            $existingPlugins = $this->getExistingPlugins();
        } catch (\Exception $e) {
            $this->logger->critical(sprintf('Destination plugins folder could not be found not created at "%s"', (string)Hooks::applyFilters(self::FILTER_IMPORT_PLUGINS_DEST_DIR, $destDir)));

            return;
        }

        $defaultExcluded = [
            $destDir . 'wp-staging' 
        ];

        foreach ($pluginsToRestore as $pluginSlug => $pluginPath) {



            if ($this->isSiteHostedOnWordPressCom && is_link("{$destDir}{$pluginSlug}")) {
                continue;
            }

            if ($this->isExcludedFile("$destDir$pluginSlug", $defaultExcluded)) {
                continue;
            }









            if (array_key_exists($pluginSlug, $existingPlugins)) {
                if ($this->isRestoreOnSubsite() && Hooks::applyFilters(self::FILTER_REPLACE_EXISTING_PLUGINS, false)) {
                    continue;
                }

                $this->enqueueMove($existingPlugins[$pluginSlug], "{$destDir}{$pluginSlug}{$this->getOriginalSuffix()}");
                $this->enqueueMove($pluginsToRestore[$pluginSlug], "{$destDir}{$pluginSlug}");
                $this->enqueueDelete("{$destDir}{$pluginSlug}{$this->getOriginalSuffix()}");
                continue;
            }




            $this->enqueueMove($pluginsToRestore[$pluginSlug], "{$destDir}{$pluginSlug}");
        }

 
        if ($this->isRestoreOnSubsite()) {
            return;
        }

 
        if (Hooks::applyFilters(self::FILTER_KEEP_EXISTING_PLUGINS, false)) {
            return;
        }

 
        foreach ($existingPlugins as $pluginSlug => $pluginPath) {
            if ($this->isExcludedFile($pluginPath, $defaultExcluded)) {
                continue;
            }

            if ($pluginSlug === self::SLUG_W3_TOTAL_CACHE && !array_key_exists(self::SLUG_W3_TOTAL_CACHE, $pluginsToRestore)) {
                $this->mayBeDeleteDropInFiles();
            }

            if (!array_key_exists($pluginSlug, $pluginsToRestore)) {
                $this->enqueueDelete($pluginPath);
            }
        }
    }




    private function getPluginsToRestore()
    {
        $tmpDir = $this->jobDataDto->getTmpDirectory() . PathIdentifier::IDENTIFIER_PLUGINS;

        return $this->findPluginsInDir($tmpDir);
    }




    private function getExistingPlugins()
    {
        $destDir = $this->directory->getPluginsDirectory();
        $destDir = (string)Hooks::applyFilters(self::FILTER_IMPORT_PLUGINS_DEST_DIR, $destDir);
        $this->filesystem->mkdir($destDir);

        return $this->findPluginsInDir($destDir);
    }












    private function findPluginsInDir($path)
    {
        $it = @new \DirectoryIterator($path);

        $plugins = [];

 
        foreach ($it as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            if ($fileInfo->isLink()) {
                continue;
            }

            if ($fileInfo->isDir() && strpos($fileInfo->getFilename(), WPSTG_PLUGIN_DOMAIN) !== 0) {
                $plugins[$fileInfo->getBasename()] = $fileInfo->getPathname();

                continue;
            }

 
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php' && $fileInfo->getBasename() !== 'index.php') {
                $plugins[$fileInfo->getBasename()] = $fileInfo->getPathname();

                continue;
            }
        }

        return $plugins;
    }





    private function mayBeDeleteDropInFiles(array $dropInFiles = PartIdentifier::DROP_IN_FILES)
    {
        $destinationDir = $this->directory->getWpContentDirectory();

        foreach ($dropInFiles as $file) {
            if (!file_exists($destinationDir . $file)) {
                continue;
            }

            $this->enqueueDelete($destinationDir . $file);
        }
    }
}
