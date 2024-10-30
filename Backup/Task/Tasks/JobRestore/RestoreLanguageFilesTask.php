<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Backup\Task\FileRestoreTask;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\PartIdentifier;

class RestoreLanguageFilesTask extends FileRestoreTask
{
    /** @var string */
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

    /**
     * @return array
     */
    protected function getParts(): array
    {
        return [];
    }

    /**
     * @return void
     */
    protected function buildQueue()
    {
        try {
            $languageFiles = $this->getLanguageFilesToRestore();
        } catch (\Exception $e) {
            // Folder does not exist. Likely there are no language files in wp-content to restore.
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

            /**
             * Scenario: Restoring a language file that already exists
             * If subsite restore and no filter is used to override the behaviour then preserve existing language file
             * Otherwise:
             * Replace the file
             */
            if (array_key_exists($relativeLangPath, $existingLanguages)) {
                if ($this->isRestoreOnSubsite() && Hooks::applyFilters(self::FILTER_REPLACE_EXISTING_LANGUAGES, false)) {
                    continue;
                }

                $this->enqueueMove($absoluteLangPath, $destinationDir . $relativeLangPath);
                continue;
            }

            /**
             * Scenario 2: Restoring a language file that does not yet exist
             */
            $this->enqueueMove($absoluteLangPath, $destinationDir . $relativeLangPath);
        }
    }

    /**
     * @return array An array of paths of language files found in a language subfolder of root of the temporary extracted wp-content backup folder,
     *               where the index is the relative path, and the value it's absolute path.
     * @example [
     *              'languages/edd.php' => '/var/www/single/wp-content/uploads/wp-staging/tmp/restore/655bb61a54f5/wpstg_l_/edd.php'
     *          ]
     *
     */
    private function getLanguageFilesToRestore(): array
    {
        $path = $this->jobDataDto->getTmpDirectory() . PathIdentifier::IDENTIFIER_LANG;
        $path = trailingslashit($path);

        return $this->filesystem->findFilesInDir($path);
    }

    /**
     * @param string $path
     * @return array An array of paths of existing languages.
     */
    private function getExistingLanguages(string $path): array
    {
        $path = trailingslashit($path);

        return $this->filesystem->findFilesInDir($path);
    }
}
