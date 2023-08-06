<?php

namespace WPStaging\Backup\Service;

use Exception;
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

    public function __construct(PathIdentifier $pathIdentifier, Directory $directory, DiskWriteCheck $diskWriteCheck)
    {
        $this->pathIdentifier = $pathIdentifier;
        $this->directory = $directory;
        $this->diskWriteCheck = $diskWriteCheck;
    }

    public function inject(JobRestoreDataDto $jobRestoreDataDto, LoggerInterface $logger)
    {
        $this->jobRestoreDataDto = $jobRestoreDataDto;
        $this->wpstgFile = new FileObject($this->jobRestoreDataDto->getFile());
        $this->dirRestore = $this->jobRestoreDataDto->getTmpDirectory();
        $this->logger = $logger;
    }

    /**
     * @return JobRestoreDataDto
     * @throws DiskNotWritableException
     */
    public function extract()
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

        // e.g: wp-content/themes/twentytwentyone/readme.txt|9378469:4491
        $rawIndexFile = $this->wpstgFile->readAndMoveNext();

        // Store the index position of the next file to be processed
        $this->wpstgIndexOffsetForNextFile = $this->wpstgFile->ftell();

        if (strpos($rawIndexFile, '|') === false || strpos($rawIndexFile, ':') === false) {
            throw new FinishedQueueException();
        }

        // ['{T}twentytwentyone/readme.txt', '9378469:4491']
        list($identifiablePath, $indexPosition) = explode('|', trim($rawIndexFile));

        // ['9378469', '4491']
        list($offsetStart, $length) = explode(':', trim($indexPosition));

        $identifier = $this->pathIdentifier->getIdentifierFromPath($identifiablePath);

        if ($identifier === PathIdentifier::IDENTIFIER_UPLOADS) {
            $extractFolder = $this->directory->getUploadsDirectory();
        } else {
            $extractFolder = $this->dirRestore . $identifier;
        }

        if (!wp_mkdir_p($extractFolder)) {
            throw new \RuntimeException("Could not create folder to extract backup file: $extractFolder");
        }

        $this->extractingFile = new FileBeingExtracted($identifiablePath, $extractFolder, $offsetStart, $length, $this->pathIdentifier);
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

    public function getBytesWrittenInThisRequest()
    {
        return $this->bytesWrittenThisRequest;
    }

    public function setFileToExtract($filePath)
    {
        try {
            $this->wpstgFile = new FileObject($filePath);
            $metadata = new BackupMetadata();
            $metadata = $metadata->hydrateByFile($this->wpstgFile);
            $this->jobRestoreDataDto->setCurrentFileHeaderStart($metadata->getHeaderStart());
        } catch (Exception $ex) {
            throw new MissingFileException(sprintf("Following backup part missing: %s", $filePath));
        }
    }

    /**
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

        // Jump to the next file of the index
        $this->jobRestoreDataDto->setExtractorMetadataIndexPosition($this->wpstgIndexOffsetForNextFile);

        $this->jobRestoreDataDto->incrementExtractorFilesExtracted();

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

        // Ignore the binary header when restoring
        if (strpos($destinationFilePath, 'wpstgBackupHeader.txt') !== false) {
            throw new \OutOfRangeException();
        }

        if (strpos($destinationFilePath, '.sql') !== false) {
            $this->logger->debug(sprintf('DEBUG: Restoring SQL file %s', $destinationFilePath));
        }

        wp_mkdir_p(dirname($destinationFilePath));

        // On some servers, sometimes fopen() can not create files. Seems to be caused by big files
        // Issue: #2560, #2576
        if (!file_exists($destinationFilePath)) {
            touch($destinationFilePath);
        }

        $destinationFileResource = @fopen($destinationFilePath, FileObject::MODE_APPEND);

        if (!$destinationFileResource) {
            $this->diskWriteCheck->testDiskIsWriteable();
            throw new \Exception("Can not extract file $destinationFilePath");
        }

        while (!$this->extractingFile->isFinished() && !$this->isThreshold()) {
            $writtenBytes = fwrite($destinationFileResource, $this->wpstgFile->fread($this->extractingFile->findReadTo()), (int)$this->getScriptMemoryLimit());

            if ($writtenBytes === false || $writtenBytes <= 0) {
                throw DiskNotWritableException::diskNotWritable();
            }

            $this->extractingFile->addWrittenBytes($writtenBytes);
        }
    }
}
