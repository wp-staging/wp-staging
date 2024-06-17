<?php

namespace WPStaging\Backup\Service;

use Exception;
use OutOfRangeException;
use RuntimeException;
use WPStaging\Backup\BackupFileIndex;
use WPStaging\Backup\BackupHeader;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Entity\FileBeingExtracted;
use WPStaging\Backup\Exceptions\DiskNotWritableException;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Filesystem\MissingFileException;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Backup\BackupValidator;
use WPStaging\Backup\Dto\File\ExtractorDto;
use WPStaging\Backup\Exceptions\EmptyChunkException;
use WPStaging\Backup\Exceptions\FileValidationException;
use WPStaging\Backup\FileHeader;
use WPStaging\Backup\Interfaces\IndexLineInterface;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;

class Extractor
{
    use ResourceTrait;

    const VALIDATE_DIRECTORY = 'validate';

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

    /** @var LoggerInterface */
    protected $logger;

    /** @var ExtractorDto */
    protected $extractorDto;

    /** @var int How many bytes were written in this request. */
    protected $bytesWrittenThisRequest = 0;

    /** @var bool */
    protected $isBackupFormatV1 = false;

    /** @var PathIdentifier */
    protected $pathIdentifier;

    /** @var Directory */
    protected $directory;

    /** @var DiskWriteCheck */
    protected $diskWriteCheck;

    /** @var BackupFileIndex */
    protected $backupFileIndex;

    /** @var ZlibCompressor */
    protected $zlibCompressor;

    /** @var BackupValidator */
    protected $backupValidator;

    /** @var BackupHeader */
    protected $backupHeader;

    /** @var IndexLineInterface */
    protected $indexLineDto;

    /** @var BackupMetadata */
    protected $backupMetadata;

    public function __construct(
        PathIdentifier $pathIdentifier,
        Directory $directory,
        DiskWriteCheck $diskWriteCheck,
        BackupFileIndex $backupFileIndex,
        ZlibCompressor $zlibCompressor,
        BackupValidator $backupValidator,
        BackupHeader $backupHeader
    ) {
        $this->pathIdentifier  = $pathIdentifier;
        $this->directory       = $directory;
        $this->diskWriteCheck  = $diskWriteCheck;
        $this->backupFileIndex = $backupFileIndex;
        $this->zlibCompressor  = $zlibCompressor;
        $this->backupValidator = $backupValidator;
        $this->backupHeader    = $backupHeader;
    }

    /**
     * @param bool $isBackupFormatV1
     * @return void
     */
    public function setIsBackupFormatV1(bool $isBackupFormatV1)
    {
        $this->isBackupFormatV1 = $isBackupFormatV1;
        if ($isBackupFormatV1) {
            $this->indexLineDto = new BackupFileIndex();
        } else {
            $this->indexLineDto = WPStaging::make(FileHeader::class);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
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

        $this->dirRestore = trailingslashit($this->dirRestore);
    }

    /**
     * @param bool $validateOnly
     * @return void
     * @throws DiskNotWritableException
     */
    public function execute(bool $validateOnly = false)
    {
        while (!$this->isThreshold()) {
            try {
                $this->findFileToExtract($validateOnly);
            } catch (FinishedQueueException $e) {
                // Explicit re-throw for readability
                throw $e;
            } catch (OutOfRangeException $e) {
                // Done processing, or failed
                $this->logger->warning('OutOfRangeException. Error: ' .  $e->getMessage());
                return;
            } catch (RuntimeException $e) {
                $this->logger->warning($e->getMessage());
                continue;
            } catch (MissingFileException $e) {
                $this->logger->warning('MissingFileException. Error: ' .  $e->getMessage());
                continue;
            }

            try {
                $this->processCurrentFile($validateOnly);
            } catch (FileValidationException $e) {
                if ($validateOnly) {
                    throw $e;
                } else {
                    $this->logger->warning('Unable to validate file. Error: ' .  $e->getMessage());
                    continue;
                }
            }
        }
    }

    /**
     * @param bool $validateOnly
     * @return void
     */
    private function findFileToExtract(bool $validateOnly = false)
    {
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
            throw new FinishedQueueException();
        }

        /** @var IndexLineInterface $backupFileIndex */
        $backupFileIndex = $this->indexLineDto->readIndexLine($rawIndexFile);

        $identifier = $this->pathIdentifier->getIdentifierFromPath($backupFileIndex->getIdentifiablePath());

        if ($validateOnly) {
            $extractFolder = trailingslashit($this->dirRestore . self::VALIDATE_DIRECTORY);
        } elseif ($identifier === PathIdentifier::IDENTIFIER_UPLOADS) {
            $extractFolder = $this->directory->getUploadsDirectory();
        } else {
            $extractFolder = $this->dirRestore . $identifier;
        }

        if (!$validateOnly && !wp_mkdir_p($extractFolder)) {
            throw new RuntimeException("Could not create folder to extract backup file: $extractFolder");
        }

        $this->extractingFile = new FileBeingExtracted($backupFileIndex->getIdentifiablePath(), $extractFolder, $this->pathIdentifier, $backupFileIndex);
        $this->extractingFile->setWrittenBytes($this->extractorDto->getExtractorFileWrittenBytes());

        if (!$validateOnly && $identifier === PathIdentifier::IDENTIFIER_UPLOADS && $this->extractingFile->getWrittenBytes() === 0) {
            if (file_exists($this->extractingFile->getBackupPath())) {
                // Delete the original upload file
                if (!unlink($this->extractingFile->getBackupPath())) {
                    throw new RuntimeException(sprintf(__('Could not delete original media library file %s. Skipping backup of it...', 'wp-staging'), $this->extractingFile->getRelativePath()));
                }
            }
        }

        $this->wpstgFile->fseek($this->extractingFile->getCurrentOffset());
        $this->indexLineDto = $backupFileIndex; // Required for BackupFileIndex
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
     * @param string $filePath
     * @return void
     */
    public function setFileToExtract(string $filePath)
    {
        try {
            $this->wpstgFile      = new FileObject($filePath);
            $this->backupMetadata = new BackupMetadata();
            $this->backupMetadata = $this->backupMetadata->hydrateByFile($this->wpstgFile);
            $this->extractorDto->setIndexStartOffset($this->backupMetadata->getHeaderStart());
            $this->extractorDto->setTotalChunks($this->backupMetadata->getTotalChunks());
        } catch (Exception $ex) {
            throw new MissingFileException(sprintf("Following backup part missing: %s", $filePath));
        }
    }

    /**
     * Convert {WPSTG_PIPE}, {WPSTG_COLON} to | and : respectively
     *
     * @param string $identifiablePath
     * @return string
     */
    protected function getRestorableIdentifiablePath(string $identifiablePath): string
    {
        return str_replace(['{WPSTG_PIPE}', '{WPSTG_COLON}'], ['|', ':'], $identifiablePath);
    }

    /**
     * @param bool $validateOnly
     * @return void
     * @throws DiskNotWritableException
     */
    private function processCurrentFile(bool $validateOnly = false)
    {
        try {
            if ($this->isThreshold()) {
                // Prevent considering a file as big just because we start extracting at the threshold
                return;
            }

            $this->fileBatchWrite();

            $this->bytesWrittenThisRequest += $this->extractingFile->getWrittenBytes();

            if (!$this->extractingFile->isFinished()) {
                if ($this->extractingFile->getWrittenBytes() > 0 && $this->isBigFile()) {
                    $percentProcessed = ceil(($this->extractingFile->getWrittenBytes() / $this->extractingFile->getTotalBytes()) * 100);
                    $this->logger->info(sprintf('Extracting big file: %s - %s/%s (%s%%)', $this->extractingFile->getRelativePath(), size_format($this->extractingFile->getWrittenBytes(), 2), size_format($this->extractingFile->getTotalBytes(), 2), $percentProcessed));
                }

                $this->extractorDto->setCurrentIndexOffset($this->wpstgIndexOffsetForCurrentFile);
                $this->extractorDto->setExtractorFileWrittenBytes($this->extractingFile->getWrittenBytes());

                return;
            }
        } catch (DiskNotWritableException $e) {
            // Re-throw
            throw $e;
        } catch (OutOfRangeException $e) {
            // Backup header, should be ignored silently
            $this->extractingFile->setWrittenBytes($this->extractingFile->getTotalBytes());
        } catch (Exception $e) {
            // Set this file as "written", so that we can skip to the next file.
            $this->extractingFile->setWrittenBytes($this->extractingFile->getTotalBytes());

            if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                $this->logger->warning(sprintf('Skipped file %s. Reason: %s', $this->extractingFile->getRelativePath(), $e->getMessage()));
            }
        }

        $destinationFilePath = $this->extractingFile->getBackupPath();
        $pathForErrorLogging = $this->pathIdentifier->transformIdentifiableToPath($this->indexLineDto->getIdentifiablePath());
        if (file_exists($destinationFilePath) && filesize($destinationFilePath) === 0 && $this->extractingFile->getTotalBytes() !== 0) {
            throw new RuntimeException(sprintf('File %s is empty', $pathForErrorLogging));
        }

        if (!$validateOnly && $this->isBackupFormatV1) {
            $this->maybeRemoveLastAccidentalCharFromLastExtractedFile();
        }

        clearstatcache();
        try {
            $this->indexLineDto->validateFile($destinationFilePath, $pathForErrorLogging);
        } catch (FileValidationException $e) {
            if ($validateOnly && file_exists($destinationFilePath)) {
                @unlink($destinationFilePath);
            }

            throw $e;
        }

        // Jump to the next file of the index
        $this->extractorDto->setCurrentIndexOffset($this->wpstgIndexOffsetForNextFile);

        $this->extractorDto->incrementTotalFilesExtracted();

        // Reset offset pointer
        $this->extractorDto->setExtractorFileWrittenBytes(0);

        if ($validateOnly && file_exists($destinationFilePath)) {
            @unlink($destinationFilePath);
        }
    }

    /**
     * @return void
     * @throws DiskNotWritableException
     * @throws \WPStaging\Framework\Filesystem\FilesystemExceptions
     */
    private function fileBatchWrite()
    {
        $destinationFilePath = $this->extractingFile->getBackupPath();

        if (strpos($destinationFilePath, '.sql') !== false) {
            $this->logger->debug(sprintf('DEBUG: Restoring SQL file %s', $destinationFilePath));
        }

        wp_mkdir_p(dirname($destinationFilePath));

        /**
         * On some servers, it is required to create empty file first, so we will create empty files.
         * On some servers, touch doesn't work consistently, so we will use fwrite, see the reason below.
         * On sites hosted on SiteGround, creating files using file_puts_contents uses a lot of memory,
         * so by default we will use fwrite to create the empty file.
         * If creating the empty file using fwrite fails, let try creating it using file_put_contents
         * @see https://github.com/wp-staging/wp-staging-pro/issues/3272 why it was needed.
         */
        if (!$this->createEmptyFile($destinationFilePath)) {
            file_put_contents($destinationFilePath, '');
        }

        $destinationFileResource = @fopen($destinationFilePath, FileObject::MODE_APPEND);

        if (!$destinationFileResource) {
            $this->diskWriteCheck->testDiskIsWriteable();
            throw new \Exception("Can not extract file $destinationFilePath");
        }

        while (!$this->extractingFile->isFinished() && !$this->isThreshold()) {
            $readBytesBefore = $this->wpstgFile->ftell();

            $chunk = null;
            try {
                $chunk = $this->zlibCompressor->getService()->readChunk($this->wpstgFile, $this->extractingFile, function ($currentChunkNumber) {
                    $this->logger->debug(sprintf('DEBUG: Extracting chunk %d/%d', $currentChunkNumber, $this->extractorDto->getTotalChunks()));
                });
            } catch (EmptyChunkException $ex) {
                // If empty chunk, it is an empty file, so we can skip it
                continue;
            }

            $writtenBytes = fwrite($destinationFileResource, $chunk, (int)$this->getScriptMemoryLimit());

            if ($writtenBytes === false || $writtenBytes <= 0) {
                fclose($destinationFileResource);
                $destinationFileResource = null;
                throw DiskNotWritableException::diskNotWritable();
            }

            $readBytesAfter = $this->wpstgFile->ftell() - $readBytesBefore;

            $this->extractingFile->addWrittenBytes($readBytesAfter);
        }

        fclose($destinationFileResource);
        $destinationFileResource = null;
    }

    /**
     * On some servers, sometimes fopen() can not create files. Seems to be caused by big files
     * Issue: #2560, #2576
     *
     * @param string $filePath
     * @return bool
     */
    private function createEmptyFile(string $filePath): bool
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

    /**
     * Fixes issue https://github.com/wp-staging/wp-staging-pro/issues/2861
     * @return void
     */
    private function maybeRemoveLastAccidentalCharFromLastExtractedFile()
    {
        if ($this->backupMetadata->getTotalFiles() !== $this->extractorDto->getTotalFilesExtracted()) {
            return;
        }

        if ($this->backupValidator->validateFileIndexFirstLine($this->wpstgFile, $this->backupMetadata)) {
            return;
        }

        $this->removeLastCharInExtractedFile();
    }

    /**
     * This function deletes the "w" character which is added at the end of the last restored file.
     * @see https://github.com/wp-staging/wp-staging-pro/issues/2861
     *
     * @return void
     */
    private function removeLastCharInExtractedFile()
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

    private function isBigFile(): bool
    {
        $sizeToConsiderAsBigFile = Hooks::applyFilters('wpstg.tests.restore.bigFileSize', 10 * MB_IN_BYTES);

        return $this->extractingFile->getTotalBytes() > $sizeToConsiderAsBigFile;
    }
}
