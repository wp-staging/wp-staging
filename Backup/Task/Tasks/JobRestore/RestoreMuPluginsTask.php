<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Backup\Task\FileRestoreTask;

class RestoreMuPluginsTask extends FileRestoreTask
{
    public static function getTaskName()
    {
        return 'backup_restore_muplugins';
    }

    public static function getTaskTitle()
    {
        return 'Restoring Mu-Plugins';
    }

    /**
     * @inheritDoc
     */
    protected function getParts()
    {
        return $this->jobDataDto->getBackupMetadata()->getMultipartMetadata()->getMuPluginsParts();
    }

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
                    esc_html__('Destination mu-plugins folder could not be found nor created at "%s"', 'wp-stating'),
                    esc_html((string)apply_filters('wpstg.import.muPlugins.destDir', $destDir))
                )
            );

            return;
        }

        foreach ($muPluginsToRestore as $muPluginSlug => $muPluginPath) {
            /**
             * Scenario: Skip restoring a mu-plugin whose destination is symlink and the site is hosted on WordPress.com
             */
            if ($this->isSiteHostedOnWordPressCom && is_link("$destDir$muPluginSlug")) {
                continue;
            }

            /*
             * Scenario: Restoring a mu-plugin that already exists
             * 1. Backup old mu-plugin
             * 2. Restore new mu-plugin
             * 3. Delete backup
             */
            if (array_key_exists($muPluginSlug, $existingMuPlugins)) {
                $this->enqueueMove($existingMuPlugins[$muPluginSlug], "{$destDir}{$muPluginSlug}{$this->getOriginalSuffix()}");
                $this->enqueueMove($muPluginsToRestore[$muPluginSlug], "{$destDir}{$muPluginSlug}");
                $this->enqueueDelete("{$destDir}{$muPluginSlug}{$this->getOriginalSuffix()}");
                continue;
            }

            /*
             * Scenario 2: Restoring a plugin that does not yet exist
             */
            $this->enqueueMove($muPluginsToRestore[$muPluginSlug], "$destDir$muPluginSlug");
        }

        // Don't delete existing files if filter is set to true
        if (apply_filters('wpstg.backup.restore.keepExistingMuPlugins', false)) {
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
    private function getMuPluginsToRestore()
    {
        $tmpDir = $this->jobDataDto->getTmpDirectory() . PathIdentifier::IDENTIFIER_MUPLUGINS;

        return $this->findMuPluginsInDir($tmpDir);
    }

    /**
     * @return array An array of paths of existing mu-plugins.
     */
    private function getExistingMuPlugins()
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
    private function findMuPluginsInDir($path)
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
