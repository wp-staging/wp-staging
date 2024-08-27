<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Exceptions\BackupRuntimeException;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreRequirementsCheckTask;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Utils\Strings;

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

    /** @var string[] */
    private $lineBreaks;

    /** @var Strings */
    private $strings;

    public function __construct(BackupsFinder $backupsFinder, Strings $strings)
    {
        $this->partSizeIssues = [];
        $this->missingPartIssues = [];
        $this->backupsFinder = $backupsFinder;
        $this->backupDir = '';
        $this->strings = $strings;

        $this->lineBreaks = [
            "\r",
            "\n",
            "\r\n",
            "\n\r",
            PHP_EOL
        ];
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
            $error = sprintf(esc_html('File Index of %s not found!'), $file->getFilename());
            debug_log($error);
            $this->error = $error;

            return false;
        }

        if (!$this->validateFileIndexFirstLine($file, $metadata)) {
            return false;
        }

        $file->fseek($start);
        $count = 0;
        while ($file->valid() && $file->ftell() < $end) {
            $line = $file->readAndMoveNext();
            if (empty($line) || in_array($line, $this->lineBreaks)) {
                continue;
            }

            $count++;
        }

        $totalFiles = $metadata->getTotalFiles();
        if ($count !== $totalFiles && !$metadata->getIsMultipartBackup()) {
            $error = sprintf(esc_html('File Index of %s is invalid! Actual number of files in the backup index: %s. Expected number of files: %s'), $file->getFilename(), $count, $totalFiles);
            $this->error = $error;
            debug_log($error);

            return false;
        }

        if (!$metadata->getIsMultipartBackup()) {
            return true;
        }

        $totalFiles = $metadata->getMultipartMetadata()->getTotalFiles();
        if ($count !== $totalFiles && $metadata->getIsMultipartBackup()) {
            $error = sprintf(esc_html('File Index of %s multipart backup is invalid! Actual number of files in the backup index: %s. Expected number of files: %s'), $file->getFilename(), $count, $totalFiles);
            $this->error = $error;
            debug_log($error);

            return false;
        }

        return true;
    }

    /**
     * @param  FileObject $file
     * @param  BackupMetadata $metadata
     * @return bool
     */
    public function validateFileIndexFirstLine(FileObject $file, BackupMetadata $metadata): bool
    {
        $version = $metadata->getBackupVersion();
        if (version_compare($version, BackupHeader::MIN_BACKUP_VERSION, '>=')) {
            return true;
        }

        $start = $metadata->getHeaderStart();
        $file->fseek($start - 1);

        if (!$file->valid()) {
            return true;
        }

        $line = $file->readAndMoveNext();
        if (in_array($line, $this->lineBreaks)) {
            $line = $file->readAndMoveNext(); // first line is break line, that's fine, move to next then!
        }

        if (!$this->strings->startsWith($line, 'wpstg_')) {
            $error = sprintf(esc_html('File Index of %s is invalid! The file index first line does not begin with `wpstg_`. The current first line is: %s'), $file->getFilename(), $line);
            $this->error = $error;
            debug_log($error);

            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @throws BackupRuntimeException
     */
    public function checkIfSplitBackupIsValid(BackupMetadata $metadata): bool
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

        foreach ($splitMetadata->getOthersParts() as $part) {
            $this->validatePart($part, 'otherWpRoot');
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
    public function isUnsupportedBackupVersion(BackupMetadata $metadata): bool
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
    private function validatePart(string $part, string $type)
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
