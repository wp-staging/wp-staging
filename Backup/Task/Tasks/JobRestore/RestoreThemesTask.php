<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Backup\Task\FileRestoreTask;
use WPStaging\Framework\Facades\Hooks;

class RestoreThemesTask extends FileRestoreTask
{
 
    const FILTER_REPLACE_EXISTING_THEMES = 'wpstg.backup.restore.replace_existing_themes';





    const FILTER_KEEP_EXISTING_THEMES = 'wpstg.backup.restore.keepExistingThemes';

 
    const FILTER_IMPORT_THEMES_DEST_DIR = 'wpstg.import.themes.destDir';

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




    protected function getParts(): array
    {
        return $this->jobDataDto->getBackupMetadata()->getMultipartMetadata()->getThemesParts();
    }

    protected function buildQueue()
    {
        try {
            $themesToRestore = $this->getThemesToRestore();
        } catch (\Exception $e) {
 
            $themesToRestore = [];
        }

        $destDir = $this->directory->getActiveThemeParentDirectory();

        try {
            $existingThemes = $this->getExistingThemes();
        } catch (\Exception $e) {
            $this->logger->critical(sprintf('Destination themes folder could not be found or created at "%s"', (string)Hooks::applyFilters(self::FILTER_IMPORT_THEMES_DEST_DIR, $destDir)));

            return;
        }

        foreach ($themesToRestore as $themeName => $themePath) {



            if ($this->isSiteHostedOnWordPressCom && is_link("$destDir$themeName")) {
                continue;
            }

            if ($this->isExcludedFile("$destDir$themeName")) {
                continue;
            }









            if (array_key_exists($themeName, $existingThemes)) {
                if ($this->isRestoreOnSubsite() && Hooks::applyFilters(self::FILTER_REPLACE_EXISTING_THEMES, false)) {
                    continue;
                }

                $this->enqueueMove($existingThemes[$themeName], "{$destDir}{$themeName}{$this->getOriginalSuffix()}");
                $this->enqueueMove($themesToRestore[$themeName], "{$destDir}{$themeName}");
                $this->enqueueDelete("{$destDir}{$themeName}{$this->getOriginalSuffix()}");
                continue;
            }




            $this->enqueueMove($themesToRestore[$themeName], "$destDir$themeName");
        }

 
        if ($this->isRestoreOnSubsite()) {
            return;
        }

 
        if (Hooks::applyFilters(self::FILTER_KEEP_EXISTING_THEMES, false)) {
            return;
        }

 
        foreach ($existingThemes as $themeName => $themePath) {
            if (!array_key_exists($themeName, $themesToRestore)) {
                $this->enqueueDelete($themePath);
            }
        }
    }




    private function getThemesToRestore()
    {
        $tmpDir = $this->jobDataDto->getTmpDirectory() . PathIdentifier::IDENTIFIER_THEMES;

        return $this->findThemesInDir($tmpDir);
    }




    private function getExistingThemes()
    {
        $destDir = $this->directory->getActiveThemeParentDirectory();
        $destDir = (string)Hooks::applyFilters(self::FILTER_IMPORT_THEMES_DEST_DIR, $destDir);
        $this->filesystem->mkdir($destDir);

        return $this->findThemesInDir($destDir);
    }












    private function findThemesInDir($path)
    {
        $it = @new \DirectoryIterator($path);

        $themes = [];

 
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
