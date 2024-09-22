<?php

namespace WPStaging\Backup\Service;

use RuntimeException;
use SplFileInfo;
use Throwable;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\DirectoryListing;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Backup\BackupValidator;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Exceptions\BackupRuntimeException;

use function WPStaging\functions\debug_log;

/**
 * Class BackupsFinder
 *
 * Finds the .wpstg backups in the filesystem.
 *
 * @package WPStaging\Backup
 */
class BackupsFinder extends AbstractBackupsFinder
{
    /** @var Directory */
    private $directory;

    /** @var Filesystem */
    private $filesystem;

    /** @var DirectoryListing */
    private $directoryListing;

    public function __construct(Directory $directory, Filesystem $filesystem, DirectoryListing $directoryListing)
    {
        $this->directory = $directory;
        $this->filesystem = $filesystem;
        $this->directoryListing = $directoryListing;
    }

    /**
     * @param bool $refresh
     * @return string
     * @throws BackupRuntimeException
     */
    public function getBackupsDirectory(bool $refresh = false): string
    {
        if ($refresh || $this->backupsDirectory === null) {
            $defaultBackupUploadsDirectory = $this->directory->getPluginUploadsDirectory($refresh = true) . Archiver::BACKUP_DIR_NAME;

            /**
             * Allows filtering the path to the directory Backups will be written to and read from.
             *
             * Note: changing this directory while there are backups in the previous location will, in
             * fact, hide those Backups from the plugin. The task of moving the Backups left in the previous
             * location(s) is left to the user.
             *
             * By default it uses the folder ABSPATH/wp-content/uploads/wp-staging/backups
             * You can overwrite the path with the filter wpstg.backup.directory.
             * The filtered provided path needs to be an absolute path that is inside the WordPress root (ABSPATH)
             * E.g. If ABSPATH: '/var/www/example.com' then filtered path can be '/var/www/example.com/backups'. It can not be '/var/www/backups'

             *
             * @param string $defaultBackupUploadsDirectory The default path to the directory Backups will be read from and
             *                                              written to.
             */
            $directory = apply_filters('wpstg.backup.directory', $defaultBackupUploadsDirectory);

            $directory = trailingslashit(wp_normalize_path($directory));

            if (!$this->filesystem->mkdir($directory, true)) {
                throw BackupRuntimeException::cannotCreateBackupsDirectory($directory);
            }

            if (!is_readable($directory)) {
                throw BackupRuntimeException::backupsDirectoryNotReadable($directory);
            }

            if (!is_writeable($directory)) {
                throw BackupRuntimeException::backupsDirectoryNotWriteable($directory);
            }

            $this->directoryListing->maybeUpdateOldHtaccessWebConfig($directory);

            $this->backupsDirectory = $directory;
        }

        return $this->backupsDirectory;
    }

    /**
     * @param string $scheduleId
     *
     * @return SplFileInfo[]
     */
    public function findBackupByScheduleId(string $scheduleId): array
    {
        $backups = array_filter($this->findBackups(), function ($splFileInfo) use ($scheduleId) {
            $backupFile = $splFileInfo->getPathname();
            try {
                $metadata = (new BackupMetadata())->hydrateByFilePath($backupFile);

                return $metadata->getScheduleId() == $scheduleId;
            } catch (Throwable $ex) {
                debug_log("WP STAGING: Could not find backup by schedule ID {$scheduleId} - File: {$backupFile} - " . $ex->getMessage());

                return false;
            }
        });

        if (empty($backups)) {
            return [];
        }

        return $backups;
    }

    /**
     * @return bool
     * @throws \WPStaging\Framework\Job\Exception\DiskNotWritableException
     */
    public function hasInvalidFileIndex(): bool
    {
        $backupFiles = $this->findBackups();
        $hasInvalidFilesIndexBackup = false;

        /** @var BackupValidator */
        $backupValidator = WPStaging::make(BackupValidator::class);

        /** @var SplFileInfo $file */
        foreach ($backupFiles as $file) {
            if ($file->getExtension() !== 'wpstg') {
                continue;
            }

            $isValidFileIndex = false;
            try {
                $isValidFileIndex = $this->validateBackupFileIndex($backupValidator, $file->getRealPath());
            } catch (RuntimeException $ex) {
                debug_log('Backup Validation: ' . $ex->getMessage());
                continue;
            }

            if (!$isValidFileIndex) {
                $hasInvalidFilesIndexBackup = true;
                break;
            }
        }

        return $hasInvalidFilesIndexBackup;
    }

    /**
     * @param BackupValidator $backupValidator
     * @param string $backupPath
     * @return bool
     *
     * @throws RuntimeException
     */
    protected function validateBackupFileIndex(BackupValidator $backupValidator, string $backupPath): bool
    {
        $backupMetadata = new BackupMetadata();
        $backupMetadata = $backupMetadata->hydrateByFilePath($backupPath);
        $fileObject     = new FileObject($backupPath);

        return $backupValidator->validateFileIndex($fileObject, $backupMetadata);
    }
}
