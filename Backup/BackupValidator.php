<?php

namespace WPStaging\Backup;

use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Exceptions\BackupRuntimeException;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreRequirementsCheckTask;

use function WPStaging\functions\debug_log;

class BackupValidator
{
    /** @var BackupsFinder */
    private $backupsFinder;

    /** @var array */
    protected $missingPartIssues = [];

    /** @var array */
    protected $partSizeIssues = [];

    /** @var string */
    protected $backupDir;

    /** @var array  */
    protected $existingParts = [];

    /** @var string */
    protected $error = '';

    public function __construct(BackupsFinder $backupsFinder)
    {
        $this->partSizeIssues = [];
        $this->missingPartIssues = [];
        $this->backupsFinder = $backupsFinder;
        $this->backupDir = '';
    }

    /** @return array */
    public function getMissingPartIssues()
    {
        return $this->missingPartIssues;
    }

    /** @return array */
    public function getPartSizeIssues()
    {
        return $this->partSizeIssues;
    }

    /** @return string */
    public function getErrorMessage()
    {
        return $this->error;
    }

    /**
     * @param FileObject $file
     * @param BackupMetadata $metadata
     * @return bool
     */
    public function validateFileIndex(FileObject $file, BackupMetadata $metadata)
    {
        // Early bail if not wpstg file
        if ($file->getExtension() !== 'wpstg') {
            return true;
        }

        $start = $metadata->getHeaderStart();
        $end = $metadata->getHeaderEnd();
        if ($end - $start < 4) {
            $error = esc_html__('File Index of ' . $file->getFilename() . ' not found!', 'wp-staging');
            debug_log($error);
            $this->error = $error;

            return false;
        }

        $file->fseek($start);
        $lineBreaks = [
            "\r",
            "\n",
            "\r\n",
            "\n\r",
            PHP_EOL
        ];

        $count = 0;
        while ($file->valid() && $file->ftell() < $end) {
            $line = $file->readAndMoveNext();
            if (empty($line) || in_array($line, $lineBreaks)) {
                continue;
            }

            $count++;
        }

        $totalFiles = $metadata->getTotalFiles();
        if ($count !== $totalFiles && !$metadata->getIsMultipartBackup()) {
            $error = sprintf(esc_html__('File Index of ' . $file->getFilename() . ' is invalid! Actual number of files in the backup index: %s. Expected number of files: %s', 'wp-staging'), $count, $totalFiles);
            $this->error = $error;
            debug_log($error);

            return false;
        }

        if (!$metadata->getIsMultipartBackup()) {
            return true;
        }

        $totalFiles = $metadata->getMultipartMetadata()->getTotalFiles();
        if ($count !== $totalFiles && $metadata->getIsMultipartBackup()) {
            $error = sprintf(esc_html__('File Index of ' . $file->getFilename() . ' multipart backup is invalid! Actual number of files in the backup index: %s. Expected number of files: %s', 'wp-staging'), $count, $totalFiles);
            $this->error = $error;
            debug_log($error);

            return false;
        }

        return true;
    }

    /** @return bool
     * @throws BackupRuntimeException
     */
    public function checkIfSplitBackupIsValid(BackupMetadata $metadata)
    {
        $this->partSizeIssues = [];
        $this->missingPartIssues = [];

        // Early bail if not split backup
        if (!$metadata->getIsMultipartBackup()) {
            return true;
        }

        $this->backupDir = wp_normalize_path($this->backupsFinder->getBackupsDirectory());

        $splitMetadata = $metadata->getMultipartMetadata();

        foreach ($splitMetadata->getPluginsParts() as $part) {
            $this->validatePart($part, 'plugins');
        }

        foreach ($splitMetadata->getThemesParts() as $part) {
            $this->validatePart($part, 'themes');
        }

        foreach ($splitMetadata->getUploadsParts() as $part) {
            $this->validatePart($part, 'uploads');
        }

        foreach ($splitMetadata->getMuPluginsParts() as $part) {
            $this->validatePart($part, 'muplugins');
        }

        foreach ($splitMetadata->getOthersParts() as $part) {
            $this->validatePart($part, 'others');
        }

        foreach ($splitMetadata->getDatabaseParts() as $part) {
            $this->validatePart($part, 'database');
        }

        return empty($this->partSizeIssues) && empty($this->missingPartIssues);
    }

    /**
     * @param BackupMetadata $metadata
     * @return bool
     */
    public function isUnsupportedBackupVersion(BackupMetadata $metadata)
    {
        $isCreatedOnPro = $metadata->getCreatedOnPro();
        $version = $metadata->getWpstgVersion();
        if (!$isCreatedOnPro) {
            return false;
        }

        return version_compare($version, RestoreRequirementsCheckTask::BETA_VERSION_LIMIT_PRO, '<');
    }

    /**
     * @param string $part contains part name
     * @param string $type (plugins|themes|uploads|muplugins|others|database)
     *
     * @return void
     */
    private function validatePart($part, $type)
    {
        $path = $this->backupDir . str_replace($this->backupDir, '', wp_normalize_path(untrailingslashit($part)));
        if (!file_exists($path)) {
            $this->missingPartIssues[] = [
                'name' => $part,
                'type' => $type
            ];

            return;
        }

        $metadata = new BackupMetadata();
        $metadata = $metadata->hydrateByFilePath($path);

        if (filesize($path) !== $metadata->getMultipartMetadata()->getPartSize()) {
            $this->partSizeIssues[] = $part;
            return;
        }

        $this->existingParts[] = $part;
    }
}
