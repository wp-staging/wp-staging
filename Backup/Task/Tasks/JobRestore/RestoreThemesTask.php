<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Backup\Task\FileRestoreTask;

class RestoreThemesTask extends FileRestoreTask
{
    public static function getTaskName()
    {
        return 'backup_restore_themes';
    }

    public static function getTaskTitle()
    {
        return 'Restoring Themes';
    }

    /**
     * @inheritDoc
     */
    protected function getParts()
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
            $this->logger->critical(sprintf(__('Destination themes folder could not be found or created at "%s"', 'wp-staging'), (string)apply_filters('wpstg.import.themes.destDir', $destDir)));

            return;
        }

        foreach ($themesToRestore as $themeName => $themePath) {
            /**
             * Scenario: Skip restoring a theme whose destination is symlink and the site is hosted on WordPress.com
             */
            if ($this->isSiteHostedOnWordPressCom && is_link("$destDir$themeName")) {
                continue;
            }

            /*
             * Scenario: Restoring a theme that already exists
             * 1. Backup old theme
             * 2. Restore new theme
             * 3. Delete backup
             */
            if (array_key_exists($themeName, $existingThemes)) {
                $this->enqueueMove($existingThemes[$themeName], "{$destDir}{$themeName}{$this->getOriginalSuffix()}");
                $this->enqueueMove($themesToRestore[$themeName], "{$destDir}{$themeName}");
                $this->enqueueDelete("{$destDir}{$themeName}{$this->getOriginalSuffix()}");
                continue;
            }

            /*
             * Scenario 2: Restoring a theme that does not yet exist
             */
            $this->enqueueMove($themesToRestore[$themeName], "$destDir$themeName");
        }

        // Don't delete existing files if filter is set to true
        if (apply_filters('wpstg.backup.restore.keepExistingThemes', false)) {
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
