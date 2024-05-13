<?php

namespace WPStaging\Backup\Service;

use Exception;
use WPStaging\Backup\BackupFileIndex;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
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
use WPStaging\Backup\Exceptions\EmptyChunkException;

class Extractor
{
    use ResourceTrait;

    /** @var JobRestoreDataDto */
    private $jobRestoreDataDto;

    /**
     * File currently being extracted
     * @var FileBeingExtracted|null
     */
    private $extractingFile;

    /** @var FileObject */
    private $wpstgFile;

    /** @var string */
    private $dirRestore;

    /** @var int */
    private $wpstgIndexOffsetForCurrentFile;

    /** @var int */
    private $wpstgIndexOffsetForNextFile;

    /** @var LoggerInterface */
    private $logger;

    /** @var int How many bytes were written in this request. */
    protected $bytesWrittenThisRequest = 0;

    /** @var PathIdentifier */
    protected $pathIdentifier;

    /** @var Directory */
    protected $directory;

    /** @var diskWriteCheck */
    protected $diskWriteCheck;

    /** @var BackupFileIndex */
    protected $backupFileIndex;

    /** @var ZlibCompressor */
    protected $zlibCompressor;

    /** @var BackupValidator */
    private $backupValidator;

    public function __construct(
        PathIdentifier $pathIdentifier,
        Directory $directory,
        DiskWriteCheck $diskWriteCheck,
        BackupFileIndex $backupFileIndex,
        ZlibCompressor $zlibCompressor,
        BackupValidator $backupValidator
    ) {
        $this->pathIdentifier  = $pathIdentifier;
        $this->directory       = $directory;
        $this->diskWriteCheck  = $diskWriteCheck;
        $this->backupFileIndex = $backupFileIndex;
        $this->zlibCompressor  = $zlibCompressor;
        $this->backupValidator = $backupValidator;
    }

    /**
     * @param JobRestoreDataDto $jobRestoreDataDto
     * @param LoggerInterface $logger
     * @return void
     */
    public function inject(JobRestoreDataDto $jobRestoreDataDto, LoggerInterface $logger)
    {
        $this->jobRestoreDataDto = $jobRestoreDataDto;
        $this->wpstgFile         = new FileObject($this->jobRestoreDataDto->getFile());
        $this->dirRestore        = $this->jobRestoreDataDto->getTmpDirectory();
        $this->logger            = $logger;
    }

    /**
     * @return JobRestoreDataDto
     * @throws DiskNotWritableException
     */
    public function extract(): JobRestoreDataDto
    {
        while (!$this->isThreshold()) {
            try {
                $this->findFileToExtract();
            } catch (FinishedQueueException $e) {
                // Explicit re-throw for readability
                throw $e;
            } catch (\OutOfRangeException $e) {
                // Done processing, or failed
                $this->logger->warning('OutOfRangeException. Error: ' .  $e->getMessage());
                return $this->jobRestoreDataDto;
            } catch (\RuntimeException $e) {
                $this->logger->warning($e->getMessage());
                continue;
            } catch (MissingFileException $e) {
                $this->logger->warning('MissingFileException. Error: ' .  $e->getMessage());
                continue;
            }

            $this->extractCurrentFile();
        }

        return $this->jobRestoreDataDto;
    }

    /**
     * @return void
     */
    private function findFileToExtract()
    {
        if ($this->jobRestoreDataDto->getExtractorMetadataIndexPosition() === 0) {
            $this->jobRestoreDataDto->setExtractorMetadataIndexPosition($this->jobRestoreDataDto->getCurrentFileHeaderStart());
        }

        $this->wpstgFile->fseek($this->jobRestoreDataDto->getExtractorMetadataIndexPosition());

        // Store the index position when reading the current file
        $this->wpstgIndexOffsetForCurrentFile = $this->wpstgFile->ftell();

        $rawIndexFile = $this->wpstgFile->readAndMoveNext();

        // Store the index position of the next file to be processed
        $this->wpstgIndexOffsetForNextFile = $this->wpstgFile->ftell();

        if (!$this->backupFileIndex->isIndexLine($rawIndexFile)) {
            throw new FinishedQueueException();
        }

        $backupFileIndex = $this->backupFileIndex->readIndex($rawIndexFile);

        $identifier = $this->pathIdentifier->getIdentifierFromPath($backupFileIndex->identifiablePath);

        if ($identifier === PathIdentifier::IDENTIFIER_UPLOADS) {
            $extractFolder = $this->directory->getUploadsDirectory();
        } else {
            $extractFolder = $this->dirRestore . $identifier;
        }

        if (!wp_mkdir_p($extractFolder)) {
            throw new \RuntimeException("Could not create folder to extract backup file: $extractFolder");
        }

        $this->extractingFile = new FileBeingExtracted($backupFileIndex->identifiablePath, $extractFolder, $this->pathIdentifier, $backupFileIndex);
        $this->extractingFile->setWrittenBytes($this->jobRestoreDataDto->getExtractorFileWrittenBytes());

        if ($identifier === PathIdentifier::IDENTIFIER_UPLOADS && $this->extractingFile->getWrittenBytes() === 0) {
            if (file_exists($this->extractingFile->getBackupPath())) {
                // Delete the original upload file
                if (!unlink($this->extractingFile->getBackupPath())) {
                    throw new \RuntimeException(sprintf(__('Could not delete original media library file %s. Skipping backup of it...', 'wp-staging'), $this->extractingFile->getRelativePath()));
                }
            }
        }

        $this->wpstgFile->fseek($this->extractingFile->getStart() + $this->jobRestoreDataDto->getExtractorFileWrittenBytes());
    }

    /**
     * @return int
     */
    public function getBytesWrittenInThisRequest(): int
    {
        return $this->bytesWrittenThisRequest;
    }

    /**
     * @param string $filePath
     * @return void
     */
    public function setFileToExtract(string $filePath)
    {
        try {
            $this->wpstgFile = new FileObject($filePath);
            $metadata        = new BackupMetadata();
            $metadata        = $metadata->hydrateByFile($this->wpstgFile);
            $this->jobRestoreDataDto->setCurrentFileHeaderStart($metadata->getHeaderStart());
            $this->jobRestoreDataDto->setTotalChunks($metadata->getTotalChunks());
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
     * @return void
     * @throws DiskNotWritableException
     */
    private function extractCurrentFile()
    {
        try {
            if ($this->isThreshold()) {
                // Prevent considering a file as big just because we start extracting at the threshold
                return;
            }

            $this->fileBatchWrite();

            $this->bytesWrittenThisRequest += $this->extractingFile->getWrittenBytes();

            if (!$this->extractingFile->isFinished()) {
                if ($this->extractingFile->getWrittenBytes() > 0 && $this->extractingFile->getTotalBytes() > 10 * MB_IN_BYTES) {
                    $percentProcessed = ceil(($this->extractingFile->getWrittenBytes() / $this->extractingFile->getTotalBytes()) * 100);
                    $this->logger->info(sprintf('Extracting big file: %s - %s/%s (%s%%)', $this->extractingFile->getRelativePath(), size_format($this->extractingFile->getWrittenBytes(), 2), size_format($this->extractingFile->getTotalBytes(), 2), $percentProcessed));
                }

                $this->jobRestoreDataDto->setExtractorMetadataIndexPosition($this->wpstgIndexOffsetForCurrentFile);
                $this->jobRestoreDataDto->setExtractorFileWrittenBytes($this->extractingFile->getWrittenBytes());

                return;
            }
        } catch (DiskNotWritableException $e) {
            // Re-throw
            throw $e;
        } catch (\OutOfRangeException $e) {
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
        if (file_exists($destinationFilePath) && filesize($destinationFilePath) === 0 && $this->extractingFile->getTotalBytes() !== 0) {
            throw new \RuntimeException(sprintf('File %s is empty', $destinationFilePath));
        }

        // Jump to the next file of the index
        $this->jobRestoreDataDto->setExtractorMetadataIndexPosition($this->wpstgIndexOffsetForNextFile);

        $this->jobRestoreDataDto->incrementExtractorFilesExtracted();

        $this->maybeApplyPatch2861();

        // Reset offset pointer
        $this->jobRestoreDataDto->setExtractorFileWrittenBytes(0);
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
                    $this->logger->debug(sprintf('DEBUG: Extracting chunk %d/%d', $currentChunkNumber, $this->jobRestoreDataDto->getTotalChunks()));
                });
            } catch (EmptyChunkException $ex) {
                // If empty chunk, skip it
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
    protected function createEmptyFile(string $filePath): bool
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
    protected function filePutContents(string $filePath, string $content): bool
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
     * @return void
     */
    private function maybeApplyPatch2861()
    {
        $backupMetadata = $this->jobRestoreDataDto->getBackupMetadata();
        if ($backupMetadata->getTotalFiles() !== $this->jobRestoreDataDto->getExtractorFilesExtracted()) {
            return;
        }

        if ($this->backupValidator->validateFileIndexFirstLine($this->wpstgFile, $backupMetadata)) {
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
}
