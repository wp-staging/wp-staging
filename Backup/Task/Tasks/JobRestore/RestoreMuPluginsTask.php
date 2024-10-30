<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Backup\Task\FileRestoreTask;
use WPStaging\Framework\Facades\Hooks;

class RestoreMuPluginsTask extends FileRestoreTask
{
    /** @var string */
    const FILTER_REPLACE_EXISTING_MUPLUGINS = 'wpstg.backup.restore.replace_existing_mu_plugins';

    /**
     * Old filter, cannot be renamed to new pattern
     * @var string
     */
    const FILTER_KEEP_EXISTING_MUPLUGINS = 'wpstg.backup.restore.keepExistingMuPlugins';

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

    /**
     * @return array
     */
    protected function getParts(): array
    {
        return $this->jobDataDto->getBackupMetadata()->getMultipartMetadata()->getMuPluginsParts();
    }

    /**
     * @return void
     */
    protected function buildQueue()
    {
        try {
            $muPluginsToRestore = $this->getMuPluginsToRestore();
        } catch (\Exception $e) {
            // Folder does not exist. Likely there are no mu-plugins to restore.
            $muPluginsToRestore = [];
        }

        $destDir = $this->directory->getMuPluginsDirectory();

        try {
            $existingMuPlugins = $this->getExistingMuPlugins();
        } catch (\Exception $e) {
            $this->logger->critical(
                sprintf(
                    esc_html('Destination mu-plugins folder could not be found nor created at "%s"'),
                    esc_html((string)apply_filters('wpstg.import.muPlugins.destDir', $destDir))
                )
            );

            return;
        }

        $defaultExcluded = [
            $destDir . 'wp-staging-optimizer.php'
        ];

        foreach ($muPluginsToRestore as $muPluginSlug => $muPluginPath) {
            /**
             * Scenario: Skip restoring a mu-plugin whose destination is symlink and the site is hosted on WordPress.com
             */
            if ($this->isSiteHostedOnWordPressCom && is_link("$destDir$muPluginSlug")) {
                continue;
            }

            if ($this->isExcludedFile("$destDir$muPluginSlug", $defaultExcluded)) {
                continue;
            }

            /**
             * Scenario: Restoring a mu-plugin that already exists
             * If subsite restore and no filter is used to override the behavior then preserve existing mu-plugin
             * Otherwise:
             * 1. Backup old mu-plugin
             * 2. Restore new mu-plugin
             * 3. Delete backup
             */
            if (array_key_exists($muPluginSlug, $existingMuPlugins)) {
                if ($this->isRestoreOnSubsite() && Hooks::applyFilters(self::FILTER_REPLACE_EXISTING_MUPLUGINS, false)) {
                    continue;
                }

                $this->enqueueMove($existingMuPlugins[$muPluginSlug], "{$destDir}{$muPluginSlug}{$this->getOriginalSuffix()}");
                $this->enqueueMove($muPluginsToRestore[$muPluginSlug], "{$destDir}{$muPluginSlug}");
                $this->enqueueDelete("{$destDir}{$muPluginSlug}{$this->getOriginalSuffix()}");
                continue;
            }

            /**
             * Scenario 2: Restoring a plugin that does not yet exist
             */
            $this->enqueueMove($muPluginsToRestore[$muPluginSlug], "$destDir$muPluginSlug");
        }

        // Don't delete existing files if restore on subsite
        if ($this->isRestoreOnSubsite()) {
            return;
        }

        // Don't delete existing files if filter is set to true
        if (Hooks::applyFilters(self::FILTER_KEEP_EXISTING_MUPLUGINS, false)) {
            return;
        }

        // Remove mu plugins which are not in the backup
        foreach ($existingMuPlugins as $muPluginSlug => $muPluginPath) {
            if (!array_key_exists($muPluginSlug, $muPluginsToRestore)) {
                $this->enqueueDelete($muPluginPath);
            }
        }
    }

    /**
     * @return array An array of paths of mu-plugins to restore.
     */
    private function getMuPluginsToRestore(): array
    {
        $tmpDir = $this->jobDataDto->getTmpDirectory() . PathIdentifier::IDENTIFIER_MUPLUGINS;

        return $this->findMuPluginsInDir($tmpDir);
    }

    /**
     * @return array An array of paths of existing mu-plugins.
     */
    private function getExistingMuPlugins(): array
    {
        $destDir = $this->directory->getMuPluginsDirectory();
        $destDir = (string)apply_filters('wpstg.import.muPlugins.destDir', $destDir);
        $this->filesystem->mkdir($destDir);

        return $this->findMuPluginsInDir($destDir);
    }

    /**
     * @param string $path Folder to look for mu-plugins, eg: '/var/www/wp-content/mu-plugins'
     *
     * @example [
     *              'foo' => '/var/www/wp-content/mu-plugins/foo',
     *              'foo.php' => '/var/www/wp-content/mu-plugins/foo.php',
     *          ]
     *
     * @return array An array of paths of mu-plugins found in the root of given directory,
     *               where the index is the name of the mu-plugin, and the value it's path.
     */
    private function findMuPluginsInDir(string $path): array
    {
        $it = @new \DirectoryIterator($path);

        $muPluginsDirs = [];
        $muPluginsFiles = [];

        /** @var \DirectoryIterator $fileInfo */
        foreach ($it as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            if ($fileInfo->isLink()) {
                continue;
            }

            if ($fileInfo->isDir()) {
                // wp-content/plugins/foo
                $muPluginsDirs[$fileInfo->getBasename()] = $fileInfo->getPathname();

                continue;
            }

            if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            if ($fileInfo->getBasename() === 'wp-staging-optimizer.php') {
                continue;
            }

            // wp-content/plugins/foo.php
            $muPluginsFiles[$fileInfo->getBasename()] = $fileInfo->getPathname();
        }

        /*
         * We need to handle the order of mu-plugins restoring explicitly,
         * starting by the folders, and only then by the files.
         *
         * This will avoid a mu-plugin requiring a file in a folder that
         * has not been restored yet.
         */

        return array_merge($muPluginsDirs, $muPluginsFiles);
    }
}
