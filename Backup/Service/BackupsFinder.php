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
use WPStaging\Framework\Network\RemoteDownloader;

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
    /** @var string */
    const FILTER_BACKUP_DIRECTORY = 'wpstg.backup.directory';

    /** @var Directory */
    private $directory;

    /** @var Filesystem */
    private $filesystem;

    /** @var DirectoryListing */
    private $directoryListing;

    /** @var BackupsDirectoryResolver */
    private $backupsDirectoryResolver;

    public function __construct(Directory $directory, Filesystem $filesystem, DirectoryListing $directoryListing, BackupsDirectoryResolver $backupsDirectoryResolver)
    {
        $this->directory                = $directory;
        $this->filesystem               = $filesystem;
        $this->directoryListing         = $directoryListing;
        $this->backupsDirectoryResolver = $backupsDirectoryResolver;
    }

    /**
     * @param bool $refresh
     * @return string
     * @throws BackupRuntimeException
     */
    public function getBackupsDirectory(bool $refresh = false): string
    {
        if ($refresh || $this->backupsDirectory === null) {
            $pluginUploadsDirectory = $this->directory->getPluginUploadsDirectory($refresh = true);
            $directory              = $this->backupsDirectoryResolver->resolveFromPluginUploadsDirectory($pluginUploadsDirectory);

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
     * Delete temp backup files associated with a job ID.
     * This includes the temp backup file, any in-progress upload file,
     * and the renamed .wpstg file from remote sync downloads.
     *
     * @param string $jobId
     * @return void
     */
    public function deleteTempBackupByJobId(string $jobId)
    {
        $backupsDir = $this->getBackupsDirectory();

        // Delete the temp backup file (e.g., {jobId}.wpstgtmp)
        $tempBackupPath = $backupsDir . $jobId . '.' . Archiver::TMP_BACKUP_EXTENSION;
        if (file_exists($tempBackupPath)) {
            $this->filesystem->delete($tempBackupPath);
        }

        // Delete the in-progress upload file (e.g., {jobId}.wpstgtmp.uploading)
        $uploadingPath = $tempBackupPath . '.' . RemoteDownloader::UPLOADING_EXTENSION;
        if (file_exists($uploadingPath)) {
            $this->filesystem->delete($uploadingPath);
        }

        // Delete the renamed backup file from remote sync (e.g., {jobId}.wpstg)
        // This happens when a download "completes" but may be corrupt/incomplete
        $renamedBackupPath = $backupsDir . $jobId . '.' . Archiver::BACKUP_EXTENSION;
        if (file_exists($renamedBackupPath)) {
            $this->filesystem->delete($renamedBackupPath);
        }
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
