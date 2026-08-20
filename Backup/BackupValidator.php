<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Exceptions\BackupRuntimeException;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreRequirementsCheckTask;
use WPStaging\Backup\Utils\BackupPathResolver;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Utils\Strings;

use function WPStaging\functions\debug_log;















class BackupValidator
{
 
    const LINE_BREAKS = [
        "\r",
        "\n",
        "\r\n",
        "\n\r",
        PHP_EOL,
    ];

 
    protected $missingPartIssues = [];

 
    protected $partSizeIssues = [];

 
    protected $backupFilename = '';

 
    protected $existingParts = [];

 
    protected $error = '';

 
    private $strings;

 
    private $backupPathResolver;

    public function __construct(Strings $strings, BackupPathResolver $backupPathResolver)
    {
        $this->partSizeIssues     = [];
        $this->missingPartIssues  = [];
        $this->strings            = $strings;
        $this->backupPathResolver = $backupPathResolver;
    }

 
    public function getMissingPartIssues()
    {
        return $this->missingPartIssues;
    }

 
    public function getPartSizeIssues()
    {
        return $this->partSizeIssues;
    }

 
    public function getErrorMessage()
    {
        return $this->error;
    }






    public function validateFileIndex(FileObject $file, BackupMetadata $metadata)
    {
 
        if ($file->getExtension() !== 'wpstg') {
            return true;
        }

        $start      = $metadata->getHeaderStart();
        $end        = $metadata->getHeaderEnd();
        $backupFile = $this->strings->maskBackupFilename($file->getFilename());
        if ($end - $start < 4) {
            $error = sprintf(esc_html('File Index of %s not found!'), $backupFile);
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
            if (empty($line) || in_array($line, self::LINE_BREAKS)) {
                continue;
            }

            $count++;
        }

        $totalFiles = $metadata->getTotalFiles();
        if ($count !== $totalFiles && !$metadata->getIsMultipartBackup()) {
            $error       = sprintf(esc_html('File Index of %s is invalid! Actual number of files in the backup index: %s. Expected number of files: %s.'), $backupFile, $count, $totalFiles);
            $this->error = $error;
            debug_log($error);

            return false;
        }

        if (!$metadata->getIsMultipartBackup()) {
            return true;
        }

        $totalFiles = $metadata->getMultipartMetadata()->getTotalFiles();
        if ($count !== $totalFiles && $metadata->getIsMultipartBackup()) {
            $error       = sprintf(esc_html('File Index of %s multipart backup is invalid! Actual number of files in the backup index: %s. Expected number of files: %s.'), $backupFile, $count, $totalFiles);
            $this->error = $error;
            debug_log($error);

            return false;
        }

        return true;
    }






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
        if (in_array($line, self::LINE_BREAKS)) {
            $line = $file->readAndMoveNext(); 
        }

        $backupFile = $this->strings->maskBackupFilename($file->getFilename());
        if (!$this->strings->startsWith($line, 'wpstg_')) {
            $error       = sprintf(esc_html('File Index of %s is invalid! The file index first line does not begin with `wpstg_`. The current first line is: %s.'), $backupFile, $line);
            $this->error = $error;
            debug_log($error);

            return false;
        }

        return true;
    }







    public function checkIfSplitBackupIsValid(BackupMetadata $metadata, string $backupFilename): bool
    {
        $this->partSizeIssues    = [];
        $this->missingPartIssues = [];

 
        if (!$metadata->getIsMultipartBackup()) {
            return true;
        }

        $this->backupFilename = $backupFilename;

        $splitMetadata = $metadata->getMultipartMetadata();

        $partsByType = [
            'plugins'     => $splitMetadata->getPluginsParts(),
            'themes'      => $splitMetadata->getThemesParts(),
            'uploads'     => $splitMetadata->getUploadsParts(),
            'muplugins'   => $splitMetadata->getMuPluginsParts(),
            'others'      => $splitMetadata->getOthersParts(),
            'otherWpRoot' => $splitMetadata->getOtherWpRootParts(),
            'database'    => $splitMetadata->getDatabaseParts(),
        ];

        foreach ($partsByType as $type => $parts) {
            foreach ($parts as $part) {
                $this->validatePart($part, $type);
            }
        }

        return empty($this->partSizeIssues) && empty($this->missingPartIssues);
    }





    public function isUnsupportedBackupVersion(BackupMetadata $metadata): bool
    {
        $isCreatedOnPro = $metadata->getCreatedOnPro();
        $version        = $metadata->getWpstgVersion();
        if (!$isCreatedOnPro) {
            return false;
        }

        return version_compare($version, RestoreRequirementsCheckTask::BETA_VERSION_LIMIT_PRO, '<');
    }






    private function validatePart(string $part, string $type)
    {
        $path = $this->backupPathResolver->resolveBackupPartPath($part, $this->backupFilename);
        if ($path === '' || !file_exists($path)) {
            $this->missingPartIssues[] = [
                'name' => $part,
                'type' => $type,
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
