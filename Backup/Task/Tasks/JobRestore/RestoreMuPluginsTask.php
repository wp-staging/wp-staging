<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Backup\Task\FileRestoreTask;
use WPStaging\Framework\Facades\Hooks;

class RestoreMuPluginsTask extends FileRestoreTask
{
 
    const FILTER_REPLACE_EXISTING_MUPLUGINS = 'wpstg.backup.restore.replace_existing_mu_plugins';





    const FILTER_KEEP_EXISTING_MUPLUGINS = 'wpstg.backup.restore.keepExistingMuPlugins';

 
    const FILTER_IMPORT_MUPLUGINS_DEST_DIR = 'wpstg.import.muPlugins.destDir';

    public static function getTaskName(): string
    {
        return 'backup_restore_muplugins';
    }

    public static function getTaskTitle(): string
    {
        return 'Restoring Mu-Plugins';
    }

    protected function isSkipped(): bool
    {
        return $this->isBackupPartSkipped(PartIdentifier::MU_PLUGIN_PART_IDENTIFIER);
    }




    protected function getParts(): array
    {
        return $this->jobDataDto->getBackupMetadata()->getMultipartMetadata()->getMuPluginsParts();
    }




    protected function buildQueue()
    {
        try {
            $muPluginsToRestore = $this->getMuPluginsToRestore();
        } catch (\Exception $e) {
 
            $muPluginsToRestore = [];
        }

        $destDir = $this->directory->getMuPluginsDirectory();

        try {
            $existingMuPlugins = $this->getExistingMuPlugins();
        } catch (\Exception $e) {
            $this->logger->critical(
                sprintf(
                    esc_html('Destination mu-plugins folder could not be found nor created at "%s"'),
                    esc_html((string)Hooks::applyFilters(self::FILTER_IMPORT_MUPLUGINS_DEST_DIR, $destDir))
                )
            );

            return;
        }

        $defaultExcluded = [
            $destDir . 'wp-staging-optimizer.php'
        ];

        foreach ($muPluginsToRestore as $muPluginSlug => $muPluginPath) {



            if ($this->isSiteHostedOnWordPressCom && is_link("$destDir$muPluginSlug")) {
                continue;
            }

            if ($this->isExcludedFile("$destDir$muPluginSlug", $defaultExcluded)) {
                continue;
            }









            if (array_key_exists($muPluginSlug, $existingMuPlugins)) {
                if ($this->isRestoreOnSubsite() && Hooks::applyFilters(self::FILTER_REPLACE_EXISTING_MUPLUGINS, false)) {
                    continue;
                }

                $this->enqueueMove($existingMuPlugins[$muPluginSlug], "{$destDir}{$muPluginSlug}{$this->getOriginalSuffix()}");
                $this->enqueueMove($muPluginsToRestore[$muPluginSlug], "{$destDir}{$muPluginSlug}");
                $this->enqueueDelete("{$destDir}{$muPluginSlug}{$this->getOriginalSuffix()}");
                continue;
            }




            $this->enqueueMove($muPluginsToRestore[$muPluginSlug], "$destDir$muPluginSlug");
        }

 
        if ($this->isRestoreOnSubsite()) {
            return;
        }

 
        if (Hooks::applyFilters(self::FILTER_KEEP_EXISTING_MUPLUGINS, false)) {
            return;
        }

 
        foreach ($existingMuPlugins as $muPluginSlug => $muPluginPath) {
            if (!array_key_exists($muPluginSlug, $muPluginsToRestore)) {
                $this->enqueueDelete($muPluginPath);
            }
        }
    }




    private function getMuPluginsToRestore(): array
    {
        $tmpDir = $this->jobDataDto->getTmpDirectory() . PathIdentifier::IDENTIFIER_MUPLUGINS;

        return $this->findMuPluginsInDir($tmpDir);
    }




    private function getExistingMuPlugins(): array
    {
        $destDir = $this->directory->getMuPluginsDirectory();
        $destDir = (string)Hooks::applyFilters(self::FILTER_IMPORT_MUPLUGINS_DEST_DIR, $destDir);
        $this->filesystem->mkdir($destDir);

        return $this->findMuPluginsInDir($destDir);
    }












    private function findMuPluginsInDir(string $path): array
    {
        $it = @new \DirectoryIterator($path);

        $muPluginsDirs = [];
        $muPluginsFiles = [];

 
        foreach ($it as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            if ($fileInfo->isLink()) {
                continue;
            }

            if ($fileInfo->isDir()) {
 
                $muPluginsDirs[$fileInfo->getBasename()] = $fileInfo->getPathname();

                continue;
            }

            if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            if ($fileInfo->getBasename() === 'wp-staging-optimizer.php') {
                continue;
            }

 
            $muPluginsFiles[$fileInfo->getBasename()] = $fileInfo->getPathname();
        }









        return array_merge($muPluginsDirs, $muPluginsFiles);
    }
}
