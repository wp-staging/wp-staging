<?php

namespace WPStaging\Backup\Service;

use Exception;
use OutOfRangeException;
use RuntimeException;
use WPStaging\Backup\BackupFileIndex;
use WPStaging\Backup\BackupHeader;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Filesystem\MissingFileException;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Traits\RestoreFileExclusionTrait;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Backup\BackupValidator;
use WPStaging\Backup\Exceptions\EmptyChunkException;
use WPStaging\Framework\Job\Exception\FileValidationException;
use WPStaging\Backup\FileHeader;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\Permissions;

class Extractor extends AbstractExtractor
{
    use ResourceTrait;
    use RestoreFileExclusionTrait;

    /** @var LoggerInterface */
    protected $logger;

    /** @var DiskWriteCheck */
    protected $diskWriteCheck;

    /** @var BackupValidator */
    protected $backupValidator;

    /** @var ZlibCompressor */
    protected $zlibCompressor;

    /** @var bool */
    protected $isRepairMultipleHeadersIssue = false;

    public function __construct(
        PathIdentifier $pathIdentifier,
        Directory $directory,
        DiskWriteCheck $diskWriteCheck,
        ZlibCompressor $zlibCompressor,
        BackupValidator $backupValidator,
        BackupHeader $backupHeader,
        Permissions $permissions
    ) {
        parent::__construct($pathIdentifier, $directory, $backupHeader, $permissions);
        $this->zlibCompressor  = $zlibCompressor;
        $this->backupValidator = $backupValidator;
        $this->diskWriteCheck  = $diskWriteCheck;
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
     * @param bool $isRepairMultipleHeadersIssue
     * @return void
     */
    public function setIsRepairMultipleHeadersIssue(bool $isRepairMultipleHeadersIssue)
    {
        $this->isRepairMultipleHeadersIssue = $isRepairMultipleHeadersIssue;
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
     * @param bool $isValidateOnly
     * @return void
     */
    public function setIsValidateOnly(bool $isValidateOnly)
    {
        $this->isValidateOnly = $isValidateOnly;
        if ($isValidateOnly) {
            $this->throwExceptionOnValidationFailure = true;
        }
    }

    /**
     * @return void
     * @throws DiskNotWritableException
     */
    public function execute()
    {
        while (!$this->isThreshold()) {
            try {
                $this->findFileToExtract();
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
            } catch (Exception $e) {
                if ($e->getCode() === self::FILE_FILTERED_EXCEPTION_CODE) {
                    continue;
                }

                if ($e->getCode() === self::FINISHED_QUEUE_EXCEPTION_CODE) {
                    throw new FinishedQueueException();
                }

                if ($e->getCode() === self::ITEM_SKIP_EXCEPTION_CODE) {
                    continue;
                }

                throw $e;
            }

            try {
                $this->processCurrentFile();
            } catch (FileValidationException $e) {
                if ($this->isValidateOnly || $this->throwExceptionOnValidationFailure) {
                    throw $e;
                }

                $this->logger->warning('Unable to validate file. Error: ' .  $e->getMessage());
            }
        }
    }

    /**
     * @param Exception $ex
     * @param string $filePath
     * @return void
     */
    protected function throwMissingFileException(Exception $ex, string $filePath)
    {
        throw new MissingFileException(sprintf("Following backup part missing: %s", $filePath), 0, $ex);
    }

    protected function isBigFile(): bool
    {
        $sizeToConsiderAsBigFile = Hooks::applyFilters('wpstg.tests.restore.bigFileSize', 10 * MB_IN_BYTES);

        return $this->extractingFile->getTotalBytes() > $sizeToConsiderAsBigFile;
    }

    protected function cleanExistingFile(string $identifier)
    {
        if ($this->isValidateOnly) {
            return;
        }

        if ($identifier !== PathIdentifier::IDENTIFIER_UPLOADS || $this->extractingFile->getWrittenBytes() > 0) {
            return;
        }

        if (file_exists($this->extractingFile->getBackupPath())) {
            // Delete the original upload file
            if (!unlink($this->extractingFile->getBackupPath())) {
                throw new \RuntimeException(sprintf(__('Could not delete original media library file %s. Skipping restore of it...', 'wp-staging'), $this->extractingFile->getRelativePath()));
            }
        }
    }

    /**
     * Fixes issue https://github.com/wp-staging/wp-staging-pro/issues/2861
     * @return void
     */
    protected function maybeRemoveLastAccidentalCharFromLastExtractedFile()
    {
        if ($this->backupMetadata->getTotalFiles() !== $this->extractorDto->getTotalFilesExtracted()) {
            return;
        }

        if ($this->backupValidator->validateFileIndexFirstLine($this->wpstgFile, $this->backupMetadata)) {
            return;
        }

        $this->removeLastCharInExtractedFile();
    }

    protected function getExtractFolder(string $identifier): string
    {
        if ($this->isValidateOnly) {
            return trailingslashit($this->dirRestore . self::VALIDATE_DIRECTORY);
        }

        if ($identifier === PathIdentifier::IDENTIFIER_UPLOADS) {
            return $this->directory->getUploadsDirectory();
        }

        return $this->dirRestore . $identifier;
    }

    /**
     * @return void
     * @throws DiskNotWritableException
     */
    private function processCurrentFile()
    {
        $destinationFilePath = $this->extractingFile->getBackupPath();
        if ($this->currentIdentifier === PathIdentifier::IDENTIFIER_UPLOADS && $this->isExcludedFile($destinationFilePath)) {
            $this->extractorDto->incrementTotalFilesSkipped();
            $this->extractorDto->setCurrentIndexOffset($this->wpstgIndexOffsetForNextFile);
            return;
        }

        try {
            if ($this->isThreshold()) {
                // Prevent considering a file as big just because we start extracting at the threshold
                return;
            }

            $this->fileBatchWrite();

            $isFileExtracted = $this->isExtractingFileExtracted(function ($message) {
                $this->logger->info($message);
            });

            if (!$isFileExtracted) {
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

        $this->validateExtractedFileAndMoveNext();
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
            throw new Exception("Can not extract file $destinationFilePath");
        }

        $lastDebugMessage = '';
        while (!$this->extractingFile->isFinished() && !$this->isThreshold()) {
            $readBytesBefore = $this->wpstgFile->ftell();

            $chunk = null;
            try {
                $chunk = $this->zlibCompressor->getService()->readChunk($this->wpstgFile, $this->extractingFile, function ($currentChunkNumber) use (&$lastDebugMessage) {
                    $lastDebugMessage = sprintf('DEBUG: Extracting chunk %d/%d', $currentChunkNumber, $this->extractorDto->getTotalChunks());
                });
            } catch (EmptyChunkException $ex) {
                // If empty chunk, it is an empty file, so we can skip it
                continue;
            }

            if ($this->isRepairMultipleHeadersIssue) {
                $chunk = $this->maybeRepairMultipleHeadersIssue($chunk);
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

        if (!empty($lastDebugMessage)) {
            $this->logger->debug($lastDebugMessage);
        }

        fclose($destinationFileResource);
        $destinationFileResource = null;
    }
}
