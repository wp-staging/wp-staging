<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Backup\Task\FileRestoreTask;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\PartIdentifier;

class RestoreLanguageFilesTask extends FileRestoreTask
{
 
    const FILTER_REPLACE_EXISTING_LANGUAGES = 'wpstg.backup.restore.replace_existing_languages';

    public static function getTaskName(): string
    {
        return 'backup_restore_language_files';
    }

    public static function getTaskTitle(): string
    {
        return 'Restoring Language Files';
    }

    protected function isSkipped(): bool
    {
        return $this->isBackupPartSkipped(PartIdentifier::LANGUAGE_PART_IDENTIFIER);
    }




    protected function getParts(): array
    {
        return [];
    }




    protected function buildQueue()
    {
        try {
            $languageFiles = $this->getLanguageFilesToRestore();
        } catch (\Exception $e) {
 
            $languageFiles = [];
        }

        $destinationDir = $this->directory->getLangsDirectory();

        try {
            $existingLanguages = $this->getExistingLanguages($destinationDir);
        } catch (\Exception $e) {
            $existingLanguages = [];
        }

        foreach ($languageFiles as $relativeLangPath => $absoluteLangPath) {
            if ($this->isExcludedFile("$destinationDir$relativeLangPath")) {
                continue;
            }







            if (array_key_exists($relativeLangPath, $existingLanguages)) {
                if ($this->isRestoreOnSubsite() && Hooks::applyFilters(self::FILTER_REPLACE_EXISTING_LANGUAGES, false)) {
                    continue;
                }

                $this->enqueueMove($absoluteLangPath, $destinationDir . $relativeLangPath);
                continue;
            }




            $this->enqueueMove($absoluteLangPath, $destinationDir . $relativeLangPath);
        }
    }









    private function getLanguageFilesToRestore(): array
    {
        $path = $this->jobDataDto->getTmpDirectory() . PathIdentifier::IDENTIFIER_LANG;
        $path = trailingslashit($path);

        return $this->filesystem->findFilesInDir($path);
    }





    private function getExistingLanguages(string $path): array
    {
        $path = trailingslashit($path);

        return $this->filesystem->findFilesInDir($path);
    }
}
