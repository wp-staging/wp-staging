<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Backup\Task\FileRestoreTask;
use WPStaging\Framework\Facades\Hooks;

class RestorePluginsTask extends FileRestoreTask
{
    /** @var string */
    const FILTER_BACKUP_RESTORE_EXCLUDE_PLUGINS = 'wpstg.backup.restore.exclude_plugins';

    /**
     * Old filter, cannot be renamed to new pattern
     * @var string
     */
    const FILTER_REPLACE_EXISTING_PLUGINS = 'wpstg.backup.restore.replace_existing_plugins';

    /**
     * Old filter, cannot be renamed to new pattern
     * @var string
     */
    const FILTER_KEEP_EXISTING_PLUGINS = 'wpstg.backup.restore.keepExistingPlugins';

    /**
     * @var string
     */
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

    /**
     * @return array
     */
    protected function getParts(): array
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
            $existingPlugins = $this->getExistingPlugins();
        } catch (\Exception $e) {
            $this->logger->critical(sprintf('Destination plugins folder could not be found not created at "%s"', (string)apply_filters('wpstg.import.plugins.destDir', $destDir)));

            return;
        }

        $defaultExcluded = [
            $destDir . 'wp-staging' // Skip wp staging plugin, e.g wp-staging-pro, wp-staging-dev, wp-staging-pro_1.
        ];

        foreach ($pluginsToRestore as $pluginSlug => $pluginPath) {
            /**
             * Scenario: Skip restoring a plugin whose destination is symlink and the site is hosted on WordPress.com
             */
            if ($this->isSiteHostedOnWordPressCom && is_link("{$destDir}{$pluginSlug}")) {
                continue;
            }

            if ($this->isExcludedFile("$destDir$pluginSlug", $defaultExcluded)) {
                continue;
            }

            /**
             * Scenario: Restoring a plugin that already exists
             * If subsite restore and no filter is used to override the behavior then preserve existing plugin
             * Otherwise:
             * 1. Backup old plugin
             * 2. Restore new plugin
             * 3. Delete backup
             */
            if (array_key_exists($pluginSlug, $existingPlugins)) {
                if ($this->isRestoreOnSubsite() && Hooks::applyFilters(self::FILTER_REPLACE_EXISTING_PLUGINS, false)) {
                    continue;
                }

                $this->enqueueMove($existingPlugins[$pluginSlug], "{$destDir}{$pluginSlug}{$this->getOriginalSuffix()}");
                $this->enqueueMove($pluginsToRestore[$pluginSlug], "{$destDir}{$pluginSlug}");
                $this->enqueueDelete("{$destDir}{$pluginSlug}{$this->getOriginalSuffix()}");
                continue;
            }

            /**
             * Scenario 2: Restoring a plugin that does not yet exist
             */
            $this->enqueueMove($pluginsToRestore[$pluginSlug], "{$destDir}{$pluginSlug}");
        }

        // Don't delete existing files if restore on subsite
        if ($this->isRestoreOnSubsite()) {
            return;
        }

        // Don't delete existing files if filter is set to true
        if (Hooks::applyFilters(self::FILTER_KEEP_EXISTING_PLUGINS, false)) {
            return;
        }

        // Remove plugins which are not in the backup
        foreach ($existingPlugins as $pluginSlug => $pluginPath) {
            if ($pluginSlug === self::SLUG_W3_TOTAL_CACHE && !array_key_exists(self::SLUG_W3_TOTAL_CACHE, $pluginsToRestore)) {
                $this->mayBeDeleteDropInFiles();
            }

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

        $pluginsToExclude = apply_filters(self::FILTER_BACKUP_RESTORE_EXCLUDE_PLUGINS, [
            WPSTG_PLUGIN_SLUG, // Skip the current active wp staging plugin slug e.g wp-staging-pro, wp-staging-dev, wp-staging-pro_1, etc.
            'wp-staging',
            'wp-staging-pro',
        ]);

        /** @var \DirectoryIterator $fileInfo */
        foreach ($it as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            if ($fileInfo->isLink()) {
                continue;
            }

            $pluginsToExclude = apply_filters_deprecated(
                self::FILTER_BACKUP_RESTORE_EXCLUDE_PLUGINS, // filter name
                [
                    [
                        WPSTG_PLUGIN_SLUG, // Skip the current active wp staging plugin slug e.g wp-staging-pro, wp-staging-dev, wp-staging-pro_1, etc.
                    ],
                ],  // old args that used to be passed to apply_filters().
                '5.9.1', // version from which it is deprecated.
                self::FILTER_EXCLUDE_FILES_DURING_RESTORE, // new filter to use
                sprintf('This filter will be removed in the upcoming version, use %s filter instead.', self::FILTER_EXCLUDE_FILES_DURING_RESTORE)
            );

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

    /**
     * @param array $dropInFiles
     * @return void
     */
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
