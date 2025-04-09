<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\BackupHeader;
use WPStaging\Backup\Dto\File\ExtractorDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Entity\FileBeingExtracted;
use WPStaging\Backup\FileHeader;
use WPStaging\Backup\Interfaces\IndexLineInterface;
use WPStaging\Framework\Adapter\DirectoryInterface;
use WPStaging\Framework\Job\Exception\FileValidationException;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\Permissions;
use WPStaging\Framework\Traits\DebugLogTrait;
use WPStaging\Framework\Traits\FormatTrait;

/**
 * Don't use any wp core functions or classes in this class
 * This class is to be used in standalone restore tool.
 */
abstract class AbstractExtractor
{
    use FormatTrait;
    use DebugLogTrait;

    /** @var string */
    const VALIDATE_DIRECTORY = 'validate';

    /** @var int */
    const ITEM_SKIP_EXCEPTION_CODE = 4001;

    /** @var int */
    const FINISHED_QUEUE_EXCEPTION_CODE = 4002;

    /** @var int */
    const FILE_FILTERED_EXCEPTION_CODE = 4003;

    /**
     * File currently being extracted
     * @var FileBeingExtracted|null
     */
    protected $extractingFile;

    /** @var FileObject */
    protected $wpstgFile;

    /** @var string */
    protected $dirRestore;

    /** @var int */
    protected $wpstgIndexOffsetForCurrentFile;

    /** @var int */
    protected $wpstgIndexOffsetForNextFile;

    /** @var ExtractorDto */
    protected $extractorDto;

    /** @var int How many bytes were written in this request. */
    protected $bytesWrittenThisRequest = 0;

    /** @var bool */
    protected $isBackupFormatV1 = false;

    /** @var PathIdentifier */
    protected $pathIdentifier;

    /** @var DirectoryInterface */
    protected $directory;

    /** @var BackupHeader */
    protected $backupHeader;

    /** @var IndexLineInterface */
    protected $indexLineDto;

    /** @var BackupMetadata */
    protected $backupMetadata;

    /** @var string|null */
    protected $extractIdentifier = '';

    /** @var bool */
    protected $isValidateOnly = false;

    /** @var string[] */
    protected $excludedIdentifier = [];

    /** @var string */
    protected $databaseBackupFile;

    /** @var int */
    protected $defaultDirectoryOctal = 0755;

    /** @var string */
    protected $currentIdentifier;

    /** @var bool */
    protected $throwExceptionOnValidationFailure = false;

    /** @var string */
    protected $lastIdentifiablePath;

    public function __construct(
        PathIdentifier $pathIdentifier,
        DirectoryInterface $directory,
        BackupHeader $backupHeader,
        Permissions $permissions
    ) {
        $this->pathIdentifier = $pathIdentifier;
        $this->directory      = $directory;
        $this->backupHeader   = $backupHeader;

        $this->defaultDirectoryOctal = $permissions->getDirectoryOctal();
        $this->excludedIdentifier    = [];
    }

    /**
     * @param string[] $excludedIdentifier
     * @return void
     */
    public function setExcludedIdentifiers(array $excludedIdentifier)
    {
        $this->excludedIdentifier = $excludedIdentifier;
    }

    public function setExtractOnlyPart(string $partToExtract)
    {
        // Reset the excluded identifier
        $this->excludedIdentifier = [];
        // early bail if part to extract is empty
        if (empty($partToExtract)) {
            return;
        }

        $parts = [
            PartIdentifier::DROPIN_PART_IDENTIFIER,
            PartIdentifier::DATABASE_PART_IDENTIFIER,
            PartIdentifier::MU_PLUGIN_PART_IDENTIFIER,
            PartIdentifier::PLUGIN_PART_IDENTIFIER,
            PartIdentifier::THEME_PART_IDENTIFIER,
            PartIdentifier::UPLOAD_PART_IDENTIFIER,
            PartIdentifier::LANGUAGE_PART_IDENTIFIER,
            PartIdentifier::WP_CONTENT_PART_IDENTIFIER,
            PartIdentifier::WP_ROOT_PART_IDENTIFIER,
        ];

        foreach ($parts as $part) {
            if ($part === $partToExtract) {
                continue;
            }

            if ($part === PartIdentifier::DROPIN_PART_IDENTIFIER) {
                $this->excludedIdentifier[] = PartIdentifier::DROPIN_PART_IDENTIFIER;
                continue;
            }

            // we need to handle the database part separately, as it's not a part of the PathIdentifier
            if ($part === PartIdentifier::DATABASE_PART_IDENTIFIER) {
                $this->excludedIdentifier[] = PartIdentifier::DATABASE_PART_IDENTIFIER;
                continue;
            }

            $this->excludedIdentifier[] = $this->pathIdentifier->getIdentifierByPartName($part);
        }
    }

    /**
     * @param IndexLineInterface $indexLineDto
     * @return void
     */
    public function setIndexLineDto(IndexLineInterface $indexLineDto)
    {
        $this->indexLineDto = $indexLineDto;
    }

    /**
     * @param bool $isBackupFormatV1
     * @return void
     */
    public function setIsBackupFormatV1(bool $isBackupFormatV1)
    {
        $this->isBackupFormatV1 = $isBackupFormatV1;
    }

    public function setThrowExceptionOnValidationFailure(bool $throwExceptionOnValidationFailure)
    {
        $this->throwExceptionOnValidationFailure = $throwExceptionOnValidationFailure;
    }

    /**
     * @return int
     */
    public function getBytesWrittenInThisRequest(): int
    {
        return $this->bytesWrittenThisRequest;
    }

    public function getExtractorDto(): ExtractorDto
    {
        return $this->extractorDto;
    }

    /**
     * @param ExtractorDto $extractorDto
     * @param string $backupFilePath
     * @param string $tmpPath
     * @return void
     */
    public function setup(ExtractorDto $extractorDto, string $backupFilePath, string $tmpPath = '')
    {
        $this->dirRestore   = $tmpPath;
        $this->extractorDto = $extractorDto;
        $this->setFileToExtract($backupFilePath);

        if (empty($this->dirRestore)) {
            $this->dirRestore = $this->directory->getTmpDirectory();
        }

        $this->dirRestore = rtrim($this->dirRestore, '/') . '/';
    }

    /**
     * @param string $filePath
     * @return void
     */
    public function setFileToExtract(string $filePath)
    {
        try {
            $this->wpstgFile          = new FileObject($filePath);
            $this->backupMetadata     = new BackupMetadata();
            $this->backupMetadata     = $this->backupMetadata->hydrateByFile($this->wpstgFile);
            $this->databaseBackupFile = $this->backupMetadata->getDatabaseFile();
            $this->extractorDto->setIndexStartOffset($this->backupMetadata->getHeaderStart());
            $this->extractorDto->setTotalChunks($this->backupMetadata->getTotalChunks());
        } catch (\Exception $ex) {
            $this->throwMissingFileException($ex, $filePath);
        }
    }

    /**
     * @param int $fileToExtractOffset
     * @return void
     */
    public function findFileToExtract(int $fileToExtractOffset = 0)
    {
        if ($fileToExtractOffset > 0) {
            $this->extractorDto->setCurrentIndexOffset($fileToExtractOffset);
        }

        if ($this->extractorDto->getCurrentIndexOffset() === 0) {
            $this->extractorDto->setCurrentIndexOffset($this->extractorDto->getIndexStartOffset());
        }

        $this->wpstgFile->fseek($this->extractorDto->getCurrentIndexOffset());

        // Store the index position when reading the current file
        $this->wpstgIndexOffsetForCurrentFile = $this->wpstgFile->ftell();

        $rawIndexFile = $this->wpstgFile->readAndMoveNext();

        // Store the index position of the next file to be processed
        $this->wpstgIndexOffsetForNextFile = $this->wpstgFile->ftell();

        if (!$this->indexLineDto->isIndexLine($rawIndexFile)) {
            throw new \Exception("", self::FINISHED_QUEUE_EXCEPTION_CODE);
        }

        /** @var IndexLineInterface $backupFileIndex */
        $backupFileIndex         = $this->indexLineDto->readIndexLine($rawIndexFile);
        $identifiablePath        = $backupFileIndex->getIdentifiablePath();
        if (empty($identifiablePath)) {
            $this->extractorDto->incrementTotalFilesSkipped();
            $this->extractorDto->setCurrentIndexOffset($this->wpstgIndexOffsetForNextFile);
            $this->debugLog('Identifier not found during extraction. Raw Index is logged: ' . rtrim($rawIndexFile, "\n"));
            throw new \Exception('Skipping file: Identifier not found. Raw Index is logged', self::ITEM_SKIP_EXCEPTION_CODE);
        }

        if ($identifiablePath === $this->lastIdentifiablePath) {
            $this->extractorDto->incrementTotalFilesSkipped();
            $this->extractorDto->setCurrentIndexOffset($this->wpstgIndexOffsetForNextFile);
            $this->debugLog('File already extracted: ' . rtrim($identifiablePath, "\n"));
            throw new \Exception('Skipping file: ' . $identifiablePath, self::ITEM_SKIP_EXCEPTION_CODE);
        }

        $identifier              = $this->pathIdentifier->getIdentifierFromPath($identifiablePath);
        $this->currentIdentifier = $identifier;
        if ($this->isFileSkipped($identifiablePath, $identifier)) {
            $this->extractorDto->incrementTotalFilesSkipped();
            $this->extractorDto->setCurrentIndexOffset($this->wpstgIndexOffsetForNextFile);
            $this->debugLog('Skipping File By Rule: ' . rtrim($identifiablePath, "\n"));
            throw new \Exception('Skipping file: ' . $identifiablePath, self::ITEM_SKIP_EXCEPTION_CODE);
        }

        $extractFolder = $this->getExtractFolder($identifier);

        if (!$this->createDirectory($extractFolder)) {
            throw new \RuntimeException("Could not create folder to extract backup file: $extractFolder");
        }

        $this->extractingFile = new FileBeingExtracted($backupFileIndex->getIdentifiablePath(), $extractFolder, $this->pathIdentifier, $backupFileIndex);
        $this->extractingFile->setWrittenBytes($this->extractorDto->getExtractorFileWrittenBytes());
        $this->extractingFile->setHeaderBytesRemoved($this->extractorDto->getHeaderBytesRemoved());

        if ($this->isFileExtracted($backupFileIndex, $this->extractingFile->getBackupPath())) {
            $this->extractorDto->incrementTotalFilesSkipped();
            $this->extractorDto->setCurrentIndexOffset($this->wpstgIndexOffsetForNextFile);
            throw new \Exception('File already extracted: ' . $identifiablePath, self::ITEM_SKIP_EXCEPTION_CODE);
        }

        $this->cleanExistingFile($identifier);

        $this->wpstgFile->fseek($this->extractingFile->getCurrentOffset());
        $this->indexLineDto = $backupFileIndex; // Required for BackupFileIndex
    }

    /**
     * On some servers, sometimes fopen() can not create files. Seems to be caused by big files
     * Issue: #2560, #2576
     *
     * @param string $filePath
     * @return bool
     */
    public function createEmptyFile(string $filePath): bool
    {
        // Early bail: file already exists
        if (file_exists($filePath)) {
            return true;
        }

        // touch() didn't work consistently on a client server, but file_put_contents() worked
        // also file_put_contents performs better than touch()
        // @see https://github.com/wp-staging/wp-staging-pro/issues/2807
        return $this->filePutContents($filePath, '') !== false;
    }

    public function isExtractingFileExtracted(callable $logInfo): bool
    {
        $this->bytesWrittenThisRequest += $this->extractingFile->getWrittenBytes();

        if ($this->extractingFile->isFinished()) {
            return true;
        }

        if ($this->extractingFile->getWrittenBytes() > 0 && $this->isBigFile()) {
            $percentProcessed = ceil(($this->extractingFile->getWrittenBytes() / $this->extractingFile->getTotalBytes()) * 100);
            $logInfo(sprintf('Extracting big file: %s - %s/%s (%s%%)', $this->extractingFile->getRelativePath(), $this->formatSize($this->extractingFile->getWrittenBytes(), 2), $this->formatSize($this->extractingFile->getTotalBytes(), 2), $percentProcessed));
        }

        $this->extractorDto->setCurrentIndexOffset($this->wpstgIndexOffsetForCurrentFile);
        $this->extractorDto->setExtractorFileWrittenBytes($this->extractingFile->getWrittenBytes());

        return false;
    }

    public function validateExtractedFileAndMoveNext()
    {
        $destinationFilePath = $this->extractingFile->getBackupPath();
        $pathForErrorLogging = $this->pathIdentifier->transformIdentifiableToPath($this->indexLineDto->getIdentifiablePath());
        if (file_exists($destinationFilePath) && filesize($destinationFilePath) === 0 && $this->extractingFile->getTotalBytes() !== 0) {
            throw new \RuntimeException(sprintf('File %s is empty', $pathForErrorLogging));
        }

        if ($this->isBackupFormatV1) {
            $this->maybeRemoveLastAccidentalCharFromLastExtractedFile();
        }

        $isValidated = true;
        $exception   = null;
        clearstatcache();

        // Lets only validate if the file headers are not removed
        if ($this->extractingFile->areHeaderBytesRemoved()) {
            $this->debugLog('Skipping validation for file because duplicate file headers were removed: ' . $pathForErrorLogging);
        } else {
            try {
                $this->indexLineDto->validateFile($destinationFilePath, $pathForErrorLogging);
            } catch (FileValidationException $e) {
                $isValidated = false;
                $exception   = $e;
            }
        }

        $this->lastIdentifiablePath = $this->indexLineDto->getIdentifiablePath();
        // Jump to the next file of the index
        $this->extractorDto->setCurrentIndexOffset($this->wpstgIndexOffsetForNextFile);

        $this->extractorDto->incrementTotalFilesExtracted();

        // Reset offset pointer
        $this->extractorDto->setHeaderBytesRemoved(0);
        $this->extractorDto->setExtractorFileWrittenBytes(0);
        $this->deleteValidationFile($destinationFilePath);

        if (!$isValidated) {
            throw $exception;
        }
    }

    public function finishExtractingFile()
    {
        $this->extractingFile->setWrittenBytes($this->extractingFile->getTotalBytes());
    }

    public function getExtractingFile(): FileBeingExtracted
    {
        return $this->extractingFile;
    }

    public function getBackupFileOffset(): int
    {
        return $this->wpstgFile->ftell();
    }

    public function readBackup(int $dataLengthToRead): string
    {
        return $this->wpstgFile->fread($dataLengthToRead);
    }

    protected function isBigFile(): bool
    {
        return $this->extractingFile->getTotalBytes() > 10 * MB_IN_BYTES;
    }

    /**
     * Fixes issue https://github.com/wp-staging/wp-staging-pro/issues/2861
     * @return void
     */
    protected function maybeRemoveLastAccidentalCharFromLastExtractedFile()
    {
        if ($this->isValidateOnly) {
            return;
        }

        if ($this->backupMetadata->getTotalFiles() !== $this->extractorDto->getTotalFilesExtracted()) {
            return;
        }

        $this->removeLastCharInExtractedFile();
    }

    /**
     * @param \Exception $ex
     * @param string $filePath
     * @return void
     */
    protected function throwMissingFileException(\Exception $ex, string $filePath)
    {
        throw new \Exception(sprintf("Following backup part missing: %s", $filePath), 0, $ex);
    }

    /**
     * This function deletes the "w" character which is added at the end of the last restored file.
     * @see https://github.com/wp-staging/wp-staging-pro/issues/2861
     *
     * @return void
     */
    protected function removeLastCharInExtractedFile()
    {
        $destinationFilePath = $this->extractingFile->getBackupPath();
        $fileContent         = file_get_contents($destinationFilePath);

        if (empty($fileContent)) {
            return;
        }

        if (substr($fileContent, -1) !== 'w') {
            return;
        }

        $fileContent = substr($fileContent, 0, -1); // Remove the last character
        file_put_contents($destinationFilePath, $fileContent);
    }

    protected function getExtractFolder(string $identifier): string
    {
        return $this->dirRestore . $this->pathIdentifier->getRelativePath($identifier);
    }

    protected function cleanExistingFile(string $identifier)
    {
        if ($this->isValidateOnly) {
            return;
        }

        if ($this->extractingFile->getWrittenBytes() > 0) {
            return;
        }

        if (file_exists($this->extractingFile->getBackupPath())) {
            // Delete the original upload file
            if (!unlink($this->extractingFile->getBackupPath())) {
                throw new \RuntimeException(sprintf(__('Could not delete original file %s. Skipping restore of it...', 'wp-staging'), $this->extractingFile->getRelativePath()));
            }
        }
    }

    protected function deleteValidationFile(string $filePath)
    {
        if (!$this->isValidateOnly) {
            return;
        }

        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    protected function isFileSkipped(string $identifiablePath, string $identifier): bool
    {
        if ($identifiablePath === $this->databaseBackupFile) {
            return in_array(PartIdentifier::DATABASE_PART_IDENTIFIER, $this->excludedIdentifier);
        }

        if ($identifier === PathIdentifier::IDENTIFIER_WP_CONTENT && !in_array(PartIdentifier::DROPIN_PART_IDENTIFIER, $this->excludedIdentifier) && $this->pathIdentifier->hasDropinsFile($identifiablePath)) {
            return false;
        }

        return in_array($identifier, $this->excludedIdentifier);
    }

    protected function isFileExtracted(IndexLineInterface $backupFileIndex, string $extractPath): bool
    {
        if (!file_exists($extractPath)) {
            return false;
        }

        return $backupFileIndex->getUncompressedSize() === filesize($extractPath);
    }

    /**
     * @see https://github.com/wp-staging/wp-staging-pro/issues/4150
     * @see https://github.com/wp-staging/wp-staging-pro/issues/4152
     * @param string $dataToFix
     * @return string
     */
    public function maybeRepairMultipleHeadersIssue(string $dataToFix): string
    {
        /**
         * @var FileHeader $fileHeader
         */
        $fileHeader = $this->indexLineDto;
        // If the file header is found in the data, remove it (this will only remove header from uncompressed file)
        if (strpos($dataToFix, $fileHeader->getFileHeader()) !== false) {
            $count = substr_count($dataToFix, $fileHeader->getFileHeader());
            $this->extractingFile->addHeaderBytesRemoved($count * ($fileHeader->getDynamicHeaderLength() + 1));

            return str_replace($fileHeader->getFileHeader() . "\n", '', $dataToFix);
        }

        // Return early if the file is not compressed because the fix for uncompressed file is already applied above
        if (!$fileHeader->getIsCompressed()) {
            return $dataToFix;
        }

        /**
         * If the uncompressed file header is found in the data of compressed file, remove it
         * Required for : https://github.com/wp-staging/wp-staging-pro/issues/4241
         */
        if (strpos($dataToFix, $fileHeader->getUncompressedFileHeader()) !== false) {
            $count = substr_count($dataToFix, $fileHeader->getUncompressedFileHeader());
            $this->extractingFile->addHeaderBytesRemoved($count * ($fileHeader->getDynamicHeaderLength() + 1));

            return str_replace($fileHeader->getUncompressedFileHeader() . "\n", '', $dataToFix);
        }

        return $dataToFix;
    }

    /**
     * file_put_contents doesn't release the resource properly. This is a workaround to release the resource properly
     * @see https://github.com/wp-staging/wp-staging-pro/issues/2868
     * @param string $filePath
     * @param string $content
     * @return bool
     */
    private function filePutContents(string $filePath, string $content): bool
    {
        if ($fp = fopen($filePath, 'wb')) {
            $bytes = fwrite($fp, $content);
            fclose($fp);
            $fp = null; // This is important to release the resource properly
            return $bytes;
        }

        return false;
    }

    private function createDirectory(string $directory): bool
    {
        if (file_exists($directory)) {
            return @is_dir($directory);
        }

        if (!is_dir($directory) && !mkdir($directory, $this->defaultDirectoryOctal, true)) {
            return false;
        }

        return true;
    }
}
