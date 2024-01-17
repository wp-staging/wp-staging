<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Backup\Task\FileRestoreTask;

class RestorePluginsTask extends FileRestoreTask
{
    const FILTER_BACKUP_RESTORE_EXCLUDE_PLUGINS = 'wpstg.backup.restore.exclude_plugins';

    public static function getTaskName()
    {
        return 'backup_restore_plugins';
    }

    public static function getTaskTitle()
    {
        return 'Restoring Plugins';
    }

    /**
     * @inheritDoc
     */
    protected function getParts()
    {
        return $this->jobDataDto->getBackupMetadata()->getMultipartMetadata()->getPluginsParts();
    }

    protected function buildQueue()
    {
        try {
            $pluginsToRestore = $this->getPluginsToRestore();
        } catch (\Exception $e) {
            // Folder does not exist. Likely there are no plugins to restore.
            $pluginsToRestore = [];
        }

        $destDir = $this->directory->getPluginsDirectory();

        try {
            $existingPlugins  = $this->getExistingPlugins();
        } catch (\Exception $e) {
            $this->logger->critical(sprintf(__('Destination plugins folder could not be found not created at "%s"', 'wp-staging'), (string)apply_filters('wpstg.import.plugins.destDir', $destDir)));

            return;
        }

        foreach ($pluginsToRestore as $pluginSlug => $pluginPath) {
            /**
             * Scenario: Skip restoring a plugin whose destination is symlink and the site is hosted on WordPress.com
             */
            if ($this->isSiteHostedOnWordPressCom && is_link("{$destDir}{$pluginSlug}")) {
                continue;
            }

            /*
             * Scenario: Restoring a plugin that already exists
             * 1. Backup old plugin
             * 2. Restore new plugin
             * 3. Delete backup
             */
            if (array_key_exists($pluginSlug, $existingPlugins)) {
                $this->enqueueMove($existingPlugins[$pluginSlug], "{$destDir}{$pluginSlug}{$this->getOriginalSuffix()}");
                $this->enqueueMove($pluginsToRestore[$pluginSlug], "{$destDir}{$pluginSlug}");
                $this->enqueueDelete("{$destDir}{$pluginSlug}{$this->getOriginalSuffix()}");
                continue;
            }

            /*
             * Scenario 2: Restoring a plugin that does not yet exist
             */
            $this->enqueueMove($pluginsToRestore[$pluginSlug], "{$destDir}{$pluginSlug}");
        }

        // Don't delete existing files if filter is set to true
        if (apply_filters('wpstg.backup.restore.keepExistingPlugins', false)) {
            return;
        }

        // Remove plugins which are not in the backup
        foreach ($existingPlugins as $pluginSlug => $pluginPath) {
            if (!array_key_exists($pluginSlug, $pluginsToRestore)) {
                $this->enqueueDelete($pluginPath);
            }
        }
    }

    /**
     * @return array An array of paths of plugins to restore.
     */
    private function getPluginsToRestore()
    {
        $tmpDir = $this->jobDataDto->getTmpDirectory() . PathIdentifier::IDENTIFIER_PLUGINS;

        return $this->findPluginsInDir($tmpDir);
    }

    /**
     * @return array An array of paths of existing plugins.
     */
    private function getExistingPlugins()
    {
        $destDir = $this->directory->getPluginsDirectory();
        $destDir = (string)apply_filters('wpstg.import.plugins.destDir', $destDir);
        $this->filesystem->mkdir($destDir);

        return $this->findPluginsInDir($destDir);
    }

    /**
     * @param string $path Folder to look for plugins, eg: '/var/www/wp-content/plugins'
     *
     * @example [
     *              'foo' => '/var/www/wp-content/plugins/foo',
     *              'foo.php' => '/var/www/wp-content/plugins/foo.php',
     *          ]
     *
     * @return array An array of paths of plugins found in the root of given directory,
     *               where the index is the name of the plugin, and the value it's path.
     */
    private function findPluginsInDir($path)
    {
        $it = @new \DirectoryIterator($path);

        $plugins = [];

        /** @var \DirectoryIterator $fileInfo */
        foreach ($it as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            if ($fileInfo->isLink()) {
                continue;
            }

            $pluginsToExclude = apply_filters(self::FILTER_BACKUP_RESTORE_EXCLUDE_PLUGINS, [
                WPSTG_PLUGIN_SLUG, // Skip the current active wp staging plugin slug e.g wp-staging-pro, wp-staging-dev, wp-staging-pro_1, etc.
            ]);
            if ($fileInfo->isDir() && !in_array($fileInfo->getFilename(), $pluginsToExclude)) {
                $plugins[$fileInfo->getBasename()] = $fileInfo->getPathname();

                continue;
            }

            // wp-content/plugins/foo.php
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php' && $fileInfo->getBasename() !== 'index.php') {
                $plugins[$fileInfo->getBasename()] = $fileInfo->getPathname();

                continue;
            }
        }

        return $plugins;
    }
}
