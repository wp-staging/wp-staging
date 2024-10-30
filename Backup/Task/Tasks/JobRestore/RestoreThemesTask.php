<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Backup\Task\FileRestoreTask;
use WPStaging\Framework\Facades\Hooks;

class RestoreThemesTask extends FileRestoreTask
{
    /** @var string */
    const FILTER_REPLACE_EXISTING_THEMES = 'wpstg.backup.restore.replace_existing_themes';

    /**
     * Old filter, cannot be renamed to new pattern
     * @var string
     */
    const FILTER_KEEP_EXISTING_THEMES = 'wpstg.backup.restore.keepExistingThemes';

    public static function getTaskName(): string
    {
        return 'backup_restore_themes';
    }

    public static function getTaskTitle(): string
    {
        return 'Restoring Themes';
    }

    protected function isSkipped(): bool
    {
        return $this->isBackupPartSkipped(PartIdentifier::THEME_PART_IDENTIFIER);
    }

    /**
     * @return array
     */
    protected function getParts(): array
    {
        return $this->jobDataDto->getBackupMetadata()->getMultipartMetadata()->getThemesParts();
    }

    protected function buildQueue()
    {
        try {
            $themesToRestore = $this->getThemesToRestore();
        } catch (\Exception $e) {
            // Folder does not exist. Likely there are no themes to restore.
            $themesToRestore = [];
        }

        $destDir = $this->directory->getActiveThemeParentDirectory();

        try {
            $existingThemes = $this->getExistingThemes();
        } catch (\Exception $e) {
            $this->logger->critical(sprintf('Destination themes folder could not be found or created at "%s"', (string)apply_filters('wpstg.import.themes.destDir', $destDir)));

            return;
        }

        foreach ($themesToRestore as $themeName => $themePath) {
            /**
             * Scenario: Skip restoring a theme whose destination is symlink and the site is hosted on WordPress.com
             */
            if ($this->isSiteHostedOnWordPressCom && is_link("$destDir$themeName")) {
                continue;
            }

            if ($this->isExcludedFile("$destDir$themeName")) {
                continue;
            }

            /**
             * Scenario: Restoring a theme that already exists
             * If subsite restore and no filter is used to override the behaviour then preserve existing theme
             * Otherwise:
             * 1. Backup old theme
             * 2. Restore new theme
             * 3. Delete backup
             */
            if (array_key_exists($themeName, $existingThemes)) {
                if ($this->isRestoreOnSubsite() && Hooks::applyFilters(self::FILTER_REPLACE_EXISTING_THEMES, false)) {
                    continue;
                }

                $this->enqueueMove($existingThemes[$themeName], "{$destDir}{$themeName}{$this->getOriginalSuffix()}");
                $this->enqueueMove($themesToRestore[$themeName], "{$destDir}{$themeName}");
                $this->enqueueDelete("{$destDir}{$themeName}{$this->getOriginalSuffix()}");
                continue;
            }

            /**
             * Scenario 2: Restoring a theme that does not yet exist
             */
            $this->enqueueMove($themesToRestore[$themeName], "$destDir$themeName");
        }

        // Don't delete existing files if restore on subsite
        if ($this->isRestoreOnSubsite()) {
            return;
        }

        // Don't delete existing files if filter is set to true
        if (Hooks::applyFilters(self::FILTER_KEEP_EXISTING_THEMES, false)) {
            return;
        }

        // Remove themes which are not in the backup
        foreach ($existingThemes as $themeName => $themePath) {
            if (!array_key_exists($themeName, $themesToRestore)) {
                $this->enqueueDelete($themePath);
            }
        }
    }

    /**
     * @return array An array of paths of themes to restore.
     */
    private function getThemesToRestore()
    {
        $tmpDir = $this->jobDataDto->getTmpDirectory() . PathIdentifier::IDENTIFIER_THEMES;

        return $this->findThemesInDir($tmpDir);
    }

    /**
     * @return array An array of paths of existing themes.
     */
    private function getExistingThemes()
    {
        $destDir = $this->directory->getActiveThemeParentDirectory();
        $destDir = (string)apply_filters('wpstg.import.themes.destDir', $destDir);
        $this->filesystem->mkdir($destDir);

        return $this->findThemesInDir($destDir);
    }

    /**
     * @param string $path Folder to look for themes, eg: '/var/www/wp-content/themes'
     *
     * @example [
     *              'twentynineteen' => '/var/www/wp-content/themes/twentynineteen',
     *              'twentytwenty' => '/var/www/wp-content/themes/twentytwenty',
     *          ]
     *
     * @return array An array of paths of themes found in the root of given directory,
     *               where the index is the name of the theme, and the value it's path.
     */
    private function findThemesInDir($path)
    {
        $it = @new \DirectoryIterator($path);

        $themes = [];

        /** @var \DirectoryIterator $item */
        foreach ($it as $item) {
            if ($item->isDot()) {
                continue;
            }

            if ($item->isDir()) {
                $themes[$item->getBasename()] = $item->getPathname();
            }
        }

        return $themes;
    }
}
