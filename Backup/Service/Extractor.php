<?php








namespace WPStaging\Backup\Service;

use Exception;
use OutOfRangeException;
use RuntimeException;
use WPStaging\Backup\BackupFileIndex;
use WPStaging\Backup\BackupHeader;
use WPStaging\Backup\BackupValidator;
use WPStaging\Backup\Exceptions\EmptyChunkException;
use WPStaging\Backup\FileHeader;
use WPStaging\Backup\Interfaces\ExtractorTaskInterface;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Job\Exception\FileValidationException;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Filesystem\MissingFileException;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Filesystem\Permissions;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Traits\RestoreFileExclusionTrait;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class Extractor extends AbstractExtractor
{
    use ResourceTrait;
    use RestoreFileExclusionTrait;

 
    protected $logger;

 
    protected $diskWriteCheck;

 
    protected $backupValidator;

 
    protected $zlibCompressor;

 
    protected $extractorTask;

 
    protected $isRepairMultipleHeadersIssue = false;

 
    protected $isFastPerformanceMode = true;

 
    protected $isLastRequestGracefulShutdown = true;

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





    public function setIsBackupFormatV1(bool $isBackupFormatV1)
    {
        $this->isBackupFormatV1 = $isBackupFormatV1;
        if ($isBackupFormatV1) {
            $this->indexLineDto = new BackupFileIndex();
        } else {
            $this->indexLineDto = WPStaging::make(FileHeader::class);
        }
    }





    public function setIsRepairMultipleHeadersIssue(bool $isRepairMultipleHeadersIssue)
    {
        $this->isRepairMultipleHeadersIssue = $isRepairMultipleHeadersIssue;
    }

    public function setIsFastPerformanceMode(bool $isFastPerformanceMode)
    {
        $this->isFastPerformanceMode = $isFastPerformanceMode;
    }

    public function setIsLastRequestGracefulShutdown(bool $isLastRequestGracefulShutdown)
    {
        $this->isLastRequestGracefulShutdown = $isLastRequestGracefulShutdown;
    }






    public function inject(ExtractorTaskInterface $extractorTask, LoggerInterface $logger)
    {
        $this->extractorTask = $extractorTask;
        $this->logger        = $logger;
    }





    public function setIsValidateOnly(bool $isValidateOnly)
    {
        $this->isValidateOnly = $isValidateOnly;
        if ($isValidateOnly) {
            $this->throwExceptionOnValidationFailure = true;
        }
    }





    public function execute()
    {
        while (!$this->isThreshold()) {
            try {
                $this->findFileToExtract();
            } catch (OutOfRangeException $e) {
 
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

 
 
        if ($this->indexLineDto instanceof FileHeader && $this->indexLineDto->getIsPreviousPartRequired()) {
            return;
        }

        if (file_exists($this->extractingFile->getBackupPath())) {
 
            if (!unlink($this->extractingFile->getBackupPath())) {
                throw new \RuntimeException(sprintf(__('Could not delete original media library file %s. Skipping restore of it...', 'wp-staging'), $this->extractingFile->getRelativePath()));
            }
        }
    }





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





    private function processCurrentFile()
    {
        $destinationFilePath = $this->extractingFile->getBackupPath();
        if ($this->currentIdentifier === PathIdentifier::IDENTIFIER_UPLOADS && $this->isExcludedFile($destinationFilePath)) {
            $this->extractorDto->incrementTotalFilesSkipped();
            $this->extractorDto->setCurrentIndexOffset($this->wpstgIndexOffsetForNextFile);
            $this->debugLog('Skipping excluded upload file: ' . rtrim($destinationFilePath, "\n"));
            return;
        }

        if ($this->extractingFile->getWrittenBytes() > 0) {
            $this->logger->debug(sprintf('Resuming extraction of file %s from byte %d. Total size: %d...', $this->extractingFile->getRelativePath(), $this->extractingFile->getWrittenBytes(), $this->extractingFile->getTotalBytes()));
        }

        $uncompressedSize      = $this->indexLineDto->getUncompressedSize();
        $shouldExtractToMemory = $this->isValidateOnly
            && !$this->isBackupFormatV1
            && !$this->isCurrentSegmentedFileHeader()
            && $this->extractingFile->getWrittenBytes() === 0
            && $this->extractingFile->getReadBytes() === 0
            && $this->isWithinMemoryExtractionLimit($uncompressedSize)
            && Hooks::applyFilters(JobDataDto::FILTER_BACKUP_USE_INMEMORY_EXTRACTION, true);
        try {
            if ($this->isThreshold()) {
 
                return;
            }

            if ($shouldExtractToMemory) {
                $this->extractAndValidateInMemory();
                return;
            }

            $this->extractFileToDisk();
        } catch (DiskNotWritableException $e) {
 
            throw $e;
        } catch (OutOfRangeException $e) {
 
            $this->extractingFile->setWrittenBytes($this->extractingFile->getTotalBytes());
        } catch (Exception $e) {
 
            $this->extractingFile->setWrittenBytes($this->extractingFile->getTotalBytes());

            if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                $this->logger->warning(sprintf('Skipped file %s. Reason: %s', $this->extractingFile->getRelativePath(), $e->getMessage()));
            }
        }

        if ($this->isFastPerformanceMode) {
            return;
        }

        $this->extractorTask->persistDto($this->extractorDto);
    }






    private function fileBatchWrite()
    {
        $destinationFilePath = $this->extractingFile->getBackupPath();

        if (strpos($destinationFilePath, '.sql') !== false) {
            $this->logger->debug(sprintf('DEBUG: Extracting SQL file %s', $destinationFilePath));
        }

        $this->maybeResetFilePointerAfterInMemoryFallback();
        wp_mkdir_p(dirname($destinationFilePath));









        if (!$this->createEmptyFile($destinationFilePath)) {
            file_put_contents($destinationFilePath, '');
        }

        $destinationFileResource = @fopen($destinationFilePath, FileObject::MODE_APPEND);
        if (!$destinationFileResource) {
            $this->diskWriteCheck->testDiskIsWriteable();
            throw new Exception("Can not extract file $destinationFilePath");
        }






        if (!$this->isLastRequestGracefulShutdown && !$this->isFastPerformanceMode && !$this->extractingFile->getIsCompressed()) {
            $fileSize = $this->getRecoverableCurrentSegmentBytes($destinationFilePath);
            $this->wpstgFile->fseek($this->extractingFile->getStart() + $fileSize);
            $this->extractingFile->setReadBytes($fileSize);
            $this->extractingFile->setWrittenBytes($fileSize);
            $this->logger->debug(sprintf('DEBUG: Seeking to byte %d in backup file to continue extraction of %s...', $this->extractingFile->getStart() + $fileSize, $this->extractingFile->getRelativePath()));
        }

        $lastDebugMessage = '';
        $processedChunks  = 0;
        while (!$this->extractingFile->isFinished() && !$this->isThreshold()) {
            $readBytesBefore = $this->wpstgFile->ftell();
            try {
                $chunk = $this->readAndPrepareChunk();
            } catch (DiskNotWritableException $ex) {
                $this->diskWriteCheck->testDiskIsWriteable();
                throw new Exception("Unable to extract file to $destinationFilePath. Please check if there is enough disk space available.");
            }

            if ($chunk === null) {
                continue;
            }

            $processedChunks++;
            $this->updateProgressTracking($processedChunks, $lastDebugMessage);
            $writtenBytes = $this->writeChunkToFile($destinationFileResource, $chunk);

            $this->trackChunkProgress($readBytesBefore, $writtenBytes);
            $this->persistDto();
        }

        if (!empty($lastDebugMessage)) {
            $this->logger->debug($lastDebugMessage);
        }

        fclose($destinationFileResource);
        $destinationFileResource = null;
    }

    protected function persistDto()
    {
        if ($this->isFastPerformanceMode) {
            return;
        }

        $this->updateExtractorDto();
        $this->extractorTask->persistDto($this->extractorDto);
    }





    private function extractFileToDisk()
    {
        $this->fileBatchWrite();
        $isFileExtracted = $this->isExtractingFileExtracted(function ($message) {
            $this->logger->info($message);
        });

        if (!$isFileExtracted) {
            return;
        }

        $this->validateExtractedFileAndMoveNext();
    }






    private function readAndPrepareChunk()
    {
        try {
            $chunk = $this->zlibCompressor->getService()->readChunk($this->wpstgFile, $this->extractingFile);
        } catch (EmptyChunkException $ex) {
            return null;
        }

        if ($this->isRepairMultipleHeadersIssue) {
            $chunk = $this->maybeRepairMultipleHeadersIssue($chunk);
        }

        return $chunk;
    }




    private function updateProgressTracking(int $processedChunks, string &$lastDebugMessage)
    {
        if ($processedChunks % 200 === 0 || $processedChunks === $this->extractorDto->getTotalChunks()) {
            $lastDebugMessage = sprintf('DEBUG: Extracting chunk %d/%d', $processedChunks, $this->extractorDto->getTotalChunks());
        }
    }







    private function writeChunkToFile($fileResource, string $chunk): int
    {
        $writtenBytes = fwrite($fileResource, $chunk, (int)$this->getScriptMemoryLimit());

        if ($writtenBytes === false || $writtenBytes <= 0) {
            fclose($fileResource);
            throw DiskNotWritableException::diskNotWritable();
        }

        return $writtenBytes;
    }

    private function getRecoverableCurrentSegmentBytes(string $destinationFilePath): int
    {
        clearstatcache();
        $diskSize = filesize($destinationFilePath);
        if ($diskSize === false) {
            return 0;
        }

        $writtenBytes = (int) $diskSize;
        if ($this->indexLineDto instanceof FileHeader && $this->indexLineDto->getIsPreviousPartRequired()) {
            $writtenBytes -= $this->extractorDto->getExtractorFileBaseBytes();
        }

        return max(0, min($this->extractingFile->getTotalBytes(), $writtenBytes));
    }




    private function trackChunkProgress(int $readBytesBefore, int $chunkSize)
    {
        $readBytesAfter = $this->wpstgFile->ftell() - $readBytesBefore;
        $this->extractingFile->addReadBytes($readBytesAfter);
        $this->extractingFile->addWrittenBytes($chunkSize);
    }





    private function validateFileContent(string $fileContent, string $pathForErrorLogging)
    {
        $actualSize   = strlen($fileContent);
        $expectedSize = $this->indexLineDto->getUncompressedSize();
        if ($expectedSize !== $actualSize) {
            throw new FileValidationException(
                sprintf(
                    'Filesize validation failed for file %s. Expected: %s. Actual: %s',
                    $pathForErrorLogging,
                    $this->formatSize($expectedSize, 2),
                    $this->formatSize($actualSize, 2)
                )
            );
        }

        if (!$this->extractingFile->areHeaderBytesRemoved()) {
            $crc32Checksum = hash(FileHeader::CRC32_CHECKSUM_ALGO, $fileContent);
 
            $fileHeader       = $this->indexLineDto;
            $expectedChecksum = $fileHeader->getCrc32Checksum();
            if ($expectedChecksum !== $crc32Checksum) {
                throw new FileValidationException(
                    sprintf(
                        'CRC32 Checksum validation failed for file %s. Expected: %s. Actual: %s',
                        $pathForErrorLogging,
                        $expectedChecksum,
                        $crc32Checksum
                    )
                );
            }
        } else {
            $this->debugLog('Skipping validation for file because duplicate file headers were removed: ' . $pathForErrorLogging);
        }
    }




    private function switchFromInMemoryToDiskExtraction(string $pathForErrorLogging)
    {
        $this->logger->debug(sprintf(
            'Threshold reached during in-memory extraction of %s. Switching to disk-based extraction on next request.',
            $pathForErrorLogging
        ));

        $this->extractingFile->setWrittenBytes(0);
    }






    private function extractAndValidateInMemory()
    {
        $pathForErrorLogging = $this->pathIdentifier->transformIdentifiableToPath($this->indexLineDto->getIdentifiablePath());
        $chunks              = [];
        while (!$this->extractingFile->isFinished() && !$this->isThreshold()) {
            $readBytesBefore = $this->wpstgFile->ftell();
            $chunk           = $this->readAndPrepareChunk();
            if ($chunk === null) {
                continue;
            }

            $chunks[] = $chunk;
            $this->trackChunkProgress($readBytesBefore, strlen($chunk));
        }

        if (!$this->extractingFile->isFinished()) {
            $this->switchFromInMemoryToDiskExtraction($pathForErrorLogging);
            $this->persistDto();
            return;
        }

        $fileContent = implode('', $chunks);
        $this->validateFileContent($fileContent, $pathForErrorLogging);
        $this->moveToNextFile();
    }





    private function maybeResetFilePointerAfterInMemoryFallback()
    {
        if ($this->extractingFile->getWrittenBytes() !== 0 || $this->extractingFile->getReadBytes() === 0) {
            return;
        }

        $this->logger->debug(sprintf(
            'Starting disk extraction for %s after in-memory fallback (resetting state)',
            $this->extractingFile->getRelativePath()
        ));

        $this->extractingFile->setReadBytes(0);
        $seekResult = $this->wpstgFile->fseek($this->extractingFile->getStart());
        if ($seekResult !== 0) {
            $message = sprintf(
                'Failed to seek backup file to start offset %d for %s during disk extraction fallback.',
                $this->extractingFile->getStart(),
                $this->extractingFile->getRelativePath()
            );

            $this->logger->warning($message);
            throw new RuntimeException($message);
        }
    }
}
