<?php

// TODO PHP7.1; constant visibility

namespace WPStaging\Backup\Service;

use Exception;
use LogicException;
use RuntimeException;
use WPStaging\Backup\BackupFileIndex;
use WPStaging\Backup\BackupHeader;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Backup\Dto\Service\ArchiverDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Exceptions\BackupSkipItemException;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Job\Exception\NotFinishedException;
use WPStaging\Backup\FileHeader;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Adapter\PhpAdapter;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Utils\Cache\BufferedCache;
use WPStaging\Framework\Traits\EndOfLinePlaceholderTrait;
use WPStaging\Vendor\lucatume\DI52\NotFoundException;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Job\Exception\ThresholdException;

use function WPStaging\functions\debug_log;

/**
 * This class is responsible for archiving files and creating backups.
 */
class Archiver
{
    use EndOfLinePlaceholderTrait;

    /**
     * After this number of failed requests, we will increase the execution by 5s for file append.
     * @var int
     */
    const MAX_RETRIES = 1;

    /**
     * This is max time limit we can allow even when php time limit is set higher.
     * @var int
     */
    const MAX_ALLOWED_PHP_TIME_LIMIT = 60;

    /**
     * In some cases when, server may have defined max_execution_time as 0 or -1, so we forcefully set it to 10s in those cases.
     * @var int
     */
    const MIN_ALLOWED_PHP_TIME_LIMIT = 10;

    /**
     * This is the fraction of php time limit, so we still have some room left to finish the execution when threshold reaches
     * @var float
     */
    const PHP_TIME_LIMIT_IN_FRACTION = 0.8;

    /** @var string */
    const BACKUP_DIR_NAME = 'backups';

    /** @var bool */
    const CREATE_BINARY_HEADER = true;

    /** @var BufferedCache */
    protected $tempBackupIndex;

    /** @var BufferedCache */
    protected $tempBackup;

    /** @var ArchiverDto */
    protected $archiverDto;

    /** @var PathIdentifier */
    protected $pathIdentifier;

    /** @var int */
    protected $archivedFileSize = 0;

    /** @var JobDataDto */
    protected $jobDataDto;

    /** @var PhpAdapter */
    protected $phpAdapter;

    /** @var bool */
    protected $isLocalBackup = false;

    /** @var int */
    protected $bytesWrittenInThisRequest = 0;

    /** @var FileHeader */
    protected $fileHeader;

    /** @var BackupHeader */
    protected $backupHeader;

    /** @var BackupFileIndex */
    protected $backupFileIndex;

    /** @var Filesystem */
    protected $filesystem;

    public function __construct(
        BufferedCache $cacheIndex,
        BufferedCache $tempBackup,
        PathIdentifier $pathIdentifier,
        JobDataDto $jobDataDto,
        ArchiverDto $archiverDto,
        PhpAdapter $phpAdapter,
        BackupFileIndex $backupFileIndex,
        FileHeader $fileHeader,
        BackupHeader $backupHeader,
        Filesystem $filesystem
    ) {
        $this->jobDataDto      = $jobDataDto;
        $this->archiverDto     = $archiverDto;
        $this->tempBackupIndex = $cacheIndex;
        $this->tempBackup      = $tempBackup;
        $this->pathIdentifier  = $pathIdentifier;
        $this->phpAdapter      = $phpAdapter;
        $this->backupFileIndex = $backupFileIndex;
        $this->fileHeader      = $fileHeader;
        $this->backupHeader    = $backupHeader;
        $this->filesystem      = $filesystem;
    }

    /**
     * @param int $fileAppendTimeLimit
     * @return void
     */
    public function setFileAppendTimeLimit(int $fileAppendTimeLimit)
    {
        $this->tempBackup->setFileAppendTimeLimit($fileAppendTimeLimit);
        $this->tempBackupIndex->setFileAppendTimeLimit($fileAppendTimeLimit);
    }

    /**
     * @param bool $isCreateBinaryHeader
     * @return void
     */
    public function createArchiveFile(bool $isCreateBinaryHeader = false)
    {
        $this->setupTmpBackupFile();

        if ($isCreateBinaryHeader && !$this->tempBackup->isValid()) {
            // Create temp file with binary header
            $this->tempBackup->save($this->isBackupFormatV1() ? $this->backupHeader->getV1FormatHeader() : $this->backupHeader->getHeader() . "\n");
        }
    }

    /**
     * Setup temp backup file and temp files index file for the given job id,
     * @return void
     */
    public function setupTmpBackupFile()
    {
        $this->tempBackup->setFilename('temp_wpstg_backup_' . $this->jobDataDto->getId());
        $this->tempBackup->setLifetime(DAY_IN_SECONDS);

        $tempBackupIndexFilePrefix = 'temp_backup_index_';
        $this->tempBackupIndex->setFilename($tempBackupIndexFilePrefix . $this->jobDataDto->getId());
        $this->tempBackupIndex->setLifetime(DAY_IN_SECONDS);
    }

    /**
     * @var bool $isLocalBackup
     */
    public function setIsLocalBackup(bool $isLocalBackup)
    {
        $this->isLocalBackup = $isLocalBackup;
    }

    /**
     * @return ArchiverDto
     */
    public function getDto(): ArchiverDto
    {
        return $this->archiverDto;
    }

    /**
     * @return int
     */
    public function getBytesWrittenInThisRequest(): int
    {
        return $this->bytesWrittenInThisRequest;
    }

    /**
     * @return BufferedCache
     */
    public function getTempBackupIndex(): BufferedCache
    {
        return $this->tempBackupIndex;
    }

    /**
     * @return BufferedCache
     */
    public function getTempBackup(): BufferedCache
    {
        return $this->tempBackup;
    }

    /**
     * @param string $fullFilePath
     * @param string $indexPath
     *
     * `true` -> finished
     * `false` -> not finished
     *
     * @throws DiskNotWritableException
     * @throws RuntimeException
     * @throws BackupSkipItemException Skip this file don't do anything
     * @throws ThresholdException
     *
     * @return bool
     */
    public function appendFileToBackup(string $fullFilePath, string $indexPath = ''): bool
    {
        // We can use evil '@' as we don't check is_file || file_exists to speed things up.
        // Since in this case speed > anything else
        // However if @ is not used, depending on if file exists or not this can throw E_WARNING.
        $resource = @fopen($fullFilePath, 'rb');
        if (!$resource) {
            debug_log("appendFileToBackup(): Can't open file {$fullFilePath} for reading");
            throw new BackupSkipItemException();
        }

        if (empty($indexPath)) {
            $indexPath = $fullFilePath;
        }

        $indexPath = $this->replaceEOLsWithPlaceholders($indexPath);
        $fileStats = fstat($resource);
        $this->initiateDtoByFilePath($fullFilePath, $fileStats);
        $this->archiverDto->setIndexPath($indexPath);
        $fileHeaderSizeInBytes = 0;
        if (!$this->isBackupFormatV1() && !$this->archiverDto->isFileHeaderWritten()) {
            $fileHeaderSizeInBytes = $this->writeFileHeader($fullFilePath, $indexPath);
            $this->archiverDto->setFileHeaderSizeInBytes($fileHeaderSizeInBytes);
        }

        $writtenBytesBefore = $this->archiverDto->getWrittenBytesTotal();
        try {
            $writtenBytesTotal = $this->appendToArchiveFile($resource, $fullFilePath);
        } catch (ThresholdException $ex) {
            // Let close the file resource before re-throwing the exception
            fclose($resource);
            $resource = null;
            $this->maybeIncrementFileAppendTimeLimit();

            throw $ex;
        }

        $newBytesWritten                 = $writtenBytesTotal + $fileHeaderSizeInBytes - $writtenBytesBefore;
        $writtenBytesIncludingFileHeader = $writtenBytesTotal + $this->archiverDto->getFileHeaderSizeInBytes();

        $retries = 0;

        do {
            if ($retries > 0) {
                usleep($this->getDelayForRetry($retries));
            }

            $bytesAddedForIndex = $this->addIndex($writtenBytesIncludingFileHeader, $newBytesWritten);
            $retries++;
        } while ($bytesAddedForIndex === 0 && $retries < 3);

        $this->archiverDto->setWrittenBytesTotal($writtenBytesTotal);

        $this->bytesWrittenInThisRequest += $newBytesWritten;

        $isFinished = $this->archiverDto->isFinished();
        if ($isFinished) {
            $this->resetFileAppendTimeLimitAndRetries();
        }

        $this->archiverDto->resetIfFinished();

        return $isFinished;
    }

    /**
     * @param string $filePath
     * @param array $fileStats
     * @param bool
     */
    public function initiateDtoByFilePath(string $filePath, array $fileStats = []): bool
    {
        if (empty($filePath) || ($filePath === $this->archiverDto->getFilePath() && $fileStats['size'] === $this->archiverDto->getFileSize())) {
            return false;
        }

        $this->archiverDto->setFilePath($filePath);
        $this->archiverDto->setFileSize($fileStats['size']);
        return true;
    }

    /**
     * Combines index and archive file, renames / moves it to destination
     *
     * This function is called only once, so performance improvements has no impact here.
     *
     * @param int $backupSizeBeforeAddingIndex
     * @param string $finalFileNameOnRename
     *
     * @return string
     */
    public function generateBackupMetadata(int $backupSizeBeforeAddingIndex = 0, string $finalFileNameOnRename = ''): string
    {
        clearstatcache();
        $backupSizeAfterAddingIndex = filesize($this->tempBackup->getFilePath());

        $backupMetadata = $this->archiverDto->getBackupMetadata();
        $backupMetadata->setHeaderStart($backupSizeBeforeAddingIndex);
        $backupMetadata->setHeaderEnd($backupSizeAfterAddingIndex);

        if ($this->jobDataDto instanceof JobBackupDataDto) {
            /** @var JobBackupDataDto */
            $jobDataDto = $this->jobDataDto;
            $this->setBackupMetadataCategoryInfo($backupMetadata, $jobDataDto);
        }

        $this->tempBackup->append(json_encode($backupMetadata));
        if (!$this->isBackupFormatV1()) {
            $this->backupHeader->readFromPath($this->tempBackup->getFilePath());
            $this->backupHeader->setMetadataStartOffset($backupSizeAfterAddingIndex);
            $this->backupHeader->setMetadataEndOffset($backupSizeAfterAddingIndex);
            $this->backupHeader->updateHeader($this->tempBackup->getFilePath());
        }

        return $this->renameBackup($finalFileNameOnRename);
    }

    /** @return int */
    public function addFileIndex(): int
    {
        clearstatcache();
        $indexResource = fopen($this->tempBackupIndex->getFilePath(), 'rb');

        if (!$indexResource) {
            debug_log('[Add File Index] Nothing to backup, no index resource! File Index: ' . $this->tempBackupIndex->getFilePath());
            throw new NotFoundException('Nothing to backup, no index resource found!');
        }

        static $isFirstInsert = false;
        $insertSeparator      = '';
        if ($isFirstInsert === false) {
            $lastLine = $this->tempBackup->readLastLine();
            if (!empty($lastLine) && preg_match('@^INSERT\sINTO\s@', $lastLine)) {
                $isFirstInsert   = true;
                $insertSeparator = "\n--\n-- SQL DATA END\n--\n";
                $this->tempBackup->append($insertSeparator);
                $this->tempBackup->deleteBottomBytes(strlen(PHP_EOL));
            }
        }

        $indexStats = fstat($indexResource);
        $this->initiateDtoByFilePath($this->tempBackupIndex->getFilePath(), $indexStats);

        $lastLine     = $this->tempBackup->readLastLine();
        $writtenBytes = $this->archiverDto->getWrittenBytesTotal();
        if ($lastLine !== PHP_EOL && $writtenBytes === 0) {
            $this->tempBackup->append(''); // ensure that file index start from new line. See https://github.com/wp-staging/wp-staging-pro/issues/2861
        }

        clearstatcache();
        $backupSizeBeforeAddingIndex = filesize($this->tempBackup->getFilePath());
        $backupIndexFileSize         = filesize($this->tempBackupIndex->getFilePath());

        // Write the index to the backup file, regardless of resource limits threshold
        // @throws Exception
        $writtenBytes = $this->appendToArchiveFile($indexResource, $this->tempBackupIndex->getFilePath());
        $this->archiverDto->setWrittenBytesTotal($writtenBytes);

        if ($writtenBytes === 0) {
            $this->jobDataDto->setRetries($this->jobDataDto->getRetries() + 1);
        } else {
            $this->jobDataDto->setRetries(0);
        }

        // close the index file handle to make it deletable for Windows where PHP < 7.3
        fclose($indexResource);

        if ($this->jobDataDto->getRetries() > 3) {
            $indexSize = $backupIndexFileSize === false ? 0 : size_format($backupIndexFileSize, 3);
            debug_log(sprintf('[Add File Index] Failed to write files-index to backup file! Tmp Size: %s. Index Size: %s', size_format($backupSizeBeforeAddingIndex, 3), $indexSize));
            throw new Exception(sprintf('Failed to write files-index to backup file! Tmp Size: %s. Index Size: %s', size_format($backupSizeBeforeAddingIndex, 3), $indexSize));
        } elseif ($writtenBytes === 0) {
            debug_log('[Add File Index] Failed to write any byte to files-index! Retrying...');
        }

        if (!$this->archiverDto->isFinished()) {
            throw new NotFinishedException('File backup is not finished yet!');
        }

        $this->tempBackupIndex->delete();
        $this->archiverDto->reset();

        $backupSizeAfterAddingIndex = filesize($this->tempBackup->getFilePath());
        if (!$this->isBackupFormatV1()) {
            $this->backupHeader->setFilesIndexStartOffset($backupSizeBeforeAddingIndex);
            $this->backupHeader->setFilesIndexEndOffset($backupSizeAfterAddingIndex);
            $this->backupHeader->updateHeader($this->tempBackup->getFilePath());
        }

        $this->tempBackup->append(PHP_EOL);

        return $backupSizeBeforeAddingIndex;
    }

    /**
     * @return string
     */
    public function getDestinationPath(): string
    {
        $extension = "wpstg";

        return sprintf(
            '%s_%s_%s.%s',
            parse_url(get_home_url())['host'],
            current_time('Ymd-His'),
            $this->jobDataDto->getId(),
            $extension
        );
    }

    /**
     * @param string $renameFileTo
     * @param bool $isLocalBackup
     * @return string
     */
    public function getFinalPath(string $renameFileTo = '', bool $isLocalBackup = true): string
    {
        $backupsDirectory = $this->getFinalBackupParentDirectory($isLocalBackup);
        if ($renameFileTo === '') {
            $renameFileTo = $this->getDestinationPath();
        }

        return $backupsDirectory . $renameFileTo;
    }

    public function getFinalBackupParentDirectory(bool $isLocalBackup = true): string
    {
        if ($isLocalBackup) {
            return WPStaging::make(BackupsFinder::class)->getBackupsDirectory();
        }

        return WPStaging::make(Directory::class)->getCacheDirectory();
    }

    /**
     * @param string $filePath
     * @param string $indexPath
     * @return int
     */
    protected function writeFileHeader(string $filePath, string $indexPath): int
    {
        $identifiablePath = $this->pathIdentifier->transformPathToIdentifiable($this->filesystem->maybeNormalizePath($indexPath));
        $this->fileHeader->readFile($filePath, $identifiablePath);

        return $this->tempBackup->append($this->fileHeader->getFileHeader());
    }

    /**
     * Get delay in milliseconds for retry according to retry number
     *
     * @param int $retry
     * @return float
     */
    protected function getDelayForRetry(int $retry): float
    {
        $delay = 0.1;
        for ($i = 0; $i < $retry; $i++) {
            $delay *= 2;
        }

        return $delay * 1000;
    }

    /**
     * @param BackupMetadata $backupMetadata
     * @param JobBackupDataDto $jobBackupDataDto
     * @return void
     */
    protected function setBackupMetadataCategoryInfo(BackupMetadata $backupMetadata, JobBackupDataDto $jobBackupDataDto)
    {
        $backupMetadata->setIndexPartSize($jobBackupDataDto->getCategorySizes());
    }

    /**
     * @param JobBackupDataDto $jobBackupDataDto
     * @return void
     */
    protected function incrementFilesCount(JobBackupDataDto $jobBackupDataDto)
    {
        $jobBackupDataDto->setTotalFiles($jobBackupDataDto->getTotalFiles() + 1);
    }

    /**
     * @return void
     */
    protected function setIndexPositionCreated()
    {
        $this->archiverDto->setIndexPositionCreated(true);
    }

    /**
     * @return bool
     */
    protected function isIndexPositionCreated(): bool
    {
        return $this->archiverDto->isIndexPositionCreated();
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    protected function maybeIncrementFileAppendTimeLimit()
    {
        $this->jobDataDto->incrementNumberOfRetries();
        if ($this->jobDataDto->getNumberOfRetries() > self::MAX_RETRIES) {
            return;
        }

        /** @var JobBackupDataDto */
        $jobDataDto = $this->jobDataDto;
        $jobDataDto->incrementFileAppendTimeLimit();
        if ($jobDataDto->getFileAppendTimeLimit() > $this->getMaxPhpTimeLimitAllowed()) {
            throw new RuntimeException('Maximum file append time limit exceeded. Please increase your PHP max execution time to proceed.');
        }
    }

    protected function getMaxPhpTimeLimitAllowed(): int
    {
        $maxAllowedPhpTimeLimit = (int)ini_get('max_execution_time');
        if ($maxAllowedPhpTimeLimit === 0 || $maxAllowedPhpTimeLimit === -1) {
            $maxAllowedPhpTimeLimit = self::MAX_ALLOWED_PHP_TIME_LIMIT * self::PHP_TIME_LIMIT_IN_FRACTION;
            return (int)Hooks::applyFilters('wpstg.resources.executionTimeLimit', $maxAllowedPhpTimeLimit);
        }

        $maxAllowedPhpTimeLimit = max(self::MIN_ALLOWED_PHP_TIME_LIMIT, $maxAllowedPhpTimeLimit);
        $maxAllowedPhpTimeLimit = min(self::MAX_ALLOWED_PHP_TIME_LIMIT, $maxAllowedPhpTimeLimit);
        $maxAllowedPhpTimeLimit = $maxAllowedPhpTimeLimit * self::PHP_TIME_LIMIT_IN_FRACTION;

        return (int)Hooks::applyFilters('wpstg.resources.executionTimeLimit', $maxAllowedPhpTimeLimit);
    }

    /**
     * @return void
     */
    protected function resetFileAppendTimeLimitAndRetries()
    {
        /** @var JobBackupDataDto */
        $jobDataDto = $this->jobDataDto;

        $jobDataDto->resetFileAppendTimeLimit();
        $jobDataDto->resetNumberOfRetries();
    }

    /**
     * @throws RuntimeException
     *
     * @param string $renameFileTo
     * @return string
     */
    private function renameBackup(string $renameFileTo = ''): string
    {
        if ($renameFileTo === '') {
            $renameFileTo = $this->getDestinationPath();
        }

        $destination = trailingslashit(dirname($this->tempBackup->getFilePath())) . $renameFileTo;
        if ($this->isLocalBackup) {
            $destination = $this->getFinalPath($renameFileTo);
        }

        if (!rename($this->tempBackup->getFilePath(), $destination)) {
            throw new RuntimeException('Failed to generate destination');
        }

        return $destination;
    }

    /**
     * @param int $writtenBytesTotal
     * @param int $newBytesAdded
     * @return int
     * @throws \WPStaging\Framework\Exceptions\IOException
     * @throws LogicException
     * @throws RuntimeException
     */
    private function addIndex(int $writtenBytesTotal, int $newBytesAdded = 0): int
    {
        clearstatcache();
        if (file_exists($this->tempBackup->getFilePath())) {
            $this->archivedFileSize = filesize($this->tempBackup->getFilePath());
        }

        $start = max($this->archivedFileSize - $writtenBytesTotal, 0);

        $identifiablePath = $this->pathIdentifier->transformPathToIdentifiable($this->archiverDto->getIndexPath());
        // New Backup format
        if ($this->isIndexPositionCreated() && !$this->isBackupFormatV1()) {
            $this->addIndexPartSize($identifiablePath, $newBytesAdded);
            return $newBytesAdded;
        }

        // Old backup format
        if ($this->isIndexPositionCreated()) {
            return $this->updateIndexInformationForAlreadyAddedIndex($writtenBytesTotal);
        }

        if ($this->isBackupFormatV1()) {
            $identifiablePath = $this->pathIdentifier->transformPathToIdentifiable($this->archiverDto->getIndexPath());
            $backupFileIndex  = $this->backupFileIndex->createIndex($identifiablePath, $start, $writtenBytesTotal, false);
            $bytesWritten     = $this->tempBackupIndex->append($backupFileIndex->getIndex());
        } else {
            $this->fileHeader->setStartOffset($start);
            $bytesWritten = $this->tempBackupIndex->append($this->fileHeader->getIndexHeader());
        }

        $this->archiverDto->setIndexPositionCreated(true);

        $this->addIndexPartSize($identifiablePath, $writtenBytesTotal);

        /**
         * We require JobDataDto in the constructor because it is wired in the DI container
         * to the current job DTO instance. However, here we need to make sure this DTO
         * is the jobBackupDataDto.
         */
        if (!$this->phpAdapter->isCallable([$this->jobDataDto, 'setTotalFiles']) || !$this->phpAdapter->isCallable([$this->jobDataDto, 'getTotalFiles'])) {
            debug_log('This method can only be called from the context of Backup');
            throw new LogicException('This method can only be called from the context of Backup');
        }

        /** @var JobBackupDataDto $jobBackupDataDto */
        $jobBackupDataDto = $this->jobDataDto;
        if ($this->archiverDto->getFileSize() >= 2 * GB_IN_BYTES) {
            $jobBackupDataDto->setIsContaining2GBFile(true);
        }

        $this->incrementFilesCount($jobBackupDataDto);

        return $bytesWritten;
    }

    /**
     * @param resource $resource
     * @param string $filePath
     *
     * @return int Bytes written
     * @throws DiskNotWritableException
     * @throws RuntimeException
     * @throws ThresholdException
     */
    private function appendToArchiveFile($resource, string $filePath): int
    {
        try {
            return $this->tempBackup->appendFile(
                $resource,
                $this->archiverDto->getWrittenBytesTotal()
            );
        } catch (DiskNotWritableException $e) {
            debug_log('Failed to write to file: ' . $filePath);
            // Re-throw for readability
            throw $e;
        }
    }

    /**
     * @param string $identifiablePath
     * @param int    $newBytesWritten
     *
     * @return void
     */
    private function addIndexPartSize(string $identifiablePath, int $newBytesWritten)
    {
        // Early bail if jobDataDto is not instance of jobBackupDataDto
        if (!$this->jobDataDto instanceof JobBackupDataDto) {
            return;
        }

        /** @var JobBackupDataDto $jobDataDto */
        $jobDataDto = $this->jobDataDto;

        $collectPartsize = $jobDataDto->getCategorySizes();

        $partName = '';
        switch ($identifiablePath) {
            case ($this->pathIdentifier::IDENTIFIER_WP_CONTENT === substr($identifiablePath, 0, strlen($this->pathIdentifier::IDENTIFIER_WP_CONTENT))):
                $partName = PartIdentifier::WP_CONTENT_PART_SIZE_IDENTIFIER;

                if ($this->pathIdentifier->hasDropinsFile($identifiablePath)) {
                    $dropinsPartName = PartIdentifier::DROPIN_PART_SIZE_IDENTIFIER;
                    if (!isset($collectPartsize[$dropinsPartName])) {
                        $collectPartsize[$dropinsPartName] = 0;
                    }

                    $collectPartsize[$dropinsPartName] += $newBytesWritten;
                }

                break;
            case ($this->pathIdentifier::IDENTIFIER_PLUGINS === substr($identifiablePath, 0, strlen($this->pathIdentifier::IDENTIFIER_PLUGINS))):
                $partName = PartIdentifier::PLUGIN_PART_SIZE_IDENTIFIER;
                break;
            case ($this->pathIdentifier::IDENTIFIER_THEMES === substr($identifiablePath, 0, strlen($this->pathIdentifier::IDENTIFIER_THEMES))):
                $partName = PartIdentifier::THEME_PART_SIZE_IDENTIFIER;
                break;
            case ($this->pathIdentifier::IDENTIFIER_MUPLUGINS === substr($identifiablePath, 0, strlen($this->pathIdentifier::IDENTIFIER_MUPLUGINS))):
                $partName = PartIdentifier::MU_PLUGIN_PART_SIZE_IDENTIFIER;
                break;
            case ($this->pathIdentifier::IDENTIFIER_UPLOADS === substr($identifiablePath, 0, strlen($this->pathIdentifier::IDENTIFIER_UPLOADS))):
                $partName = PartIdentifier::UPLOAD_PART_SIZE_IDENTIFIER;
                if (substr($identifiablePath, -4) === '.sql') {
                    $partName = PartIdentifier::DATABASE_PART_SIZE_IDENTIFIER;
                }

                break;
            case ($this->pathIdentifier::IDENTIFIER_LANG === substr($identifiablePath, 0, strlen($this->pathIdentifier::IDENTIFIER_LANG))):
                $partName = PartIdentifier::LANGUAGE_PART_SIZE_IDENTIFIER;
                break;
            case ($this->pathIdentifier::IDENTIFIER_ABSPATH === substr($identifiablePath, 0, strlen($this->pathIdentifier::IDENTIFIER_ABSPATH))):
                $partName = PartIdentifier::WP_ROOT_PART_SIZE_IDENTIFIER;
                break;
        }

        if (empty($partName)) {
            return;
        }

        // If identifier not in array yet
        if (!isset($collectPartsize[$partName])) {
            $collectPartsize[$partName] = 0;
        }

        $collectPartsize[$partName] += $newBytesWritten;
        $jobDataDto->setCategorySizes($collectPartsize);
    }

    /**
     * Used in v1 Backup Format
     * At the moment this is used when processing adding of big file which is not done in a single request
     * @param int $writtenBytesTotal
     * @return int
     * @throws RuntimeException
     */
    private function updateIndexInformationForAlreadyAddedIndex(int $writtenBytesTotal): int
    {
        $lastLine = $this->tempBackupIndex->readLines(1, null, BufferedCache::POSITION_BOTTOM);
        if (!is_array($lastLine)) {
            debug_log('Failed to read backup metadata file index information. Error: The last line is no array. Last line: ' . $lastLine);
            throw new RuntimeException('Failed to read backup metadata file index information. Error: The last line is no array.');
        }

        $lastLine = array_filter($lastLine, [$this->backupFileIndex, 'isIndexLine']);

        if (count($lastLine) !== 1) {
            debug_log('Failed to read backup metadata file index information. Error: The last line is not an array or element with countable interface. Last line: ' . print_r($lastLine, 1));
            throw new RuntimeException('Failed to read backup metadata file index information. Error: The last line is not an array or element with countable interface.');
        }

        $lastLine = array_shift($lastLine);

        $backupFileIndex   = $this->backupFileIndex->readIndex($lastLine);
        $writtenPreviously = $backupFileIndex->bytesEnd;

        $this->tempBackupIndex->deleteBottomBytes(strlen($lastLine));

        $identifiablePath = $this->pathIdentifier->transformPathToIdentifiable($this->archiverDto->getIndexPath());
        $backupFileIndex  = $this->backupFileIndex->createIndex($identifiablePath, $backupFileIndex->bytesStart, $writtenBytesTotal, false);
        $bytesWritten     = $this->tempBackupIndex->append($backupFileIndex->getIndex());

        $this->setIndexPositionCreated();

        // We only need to increment newly added bytes
        $this->addIndexPartSize($identifiablePath, $writtenBytesTotal - (int)$writtenPreviously);

        return $bytesWritten;
    }

    private function isBackupFormatV1(): bool
    {
        /** @var JobBackupDataDto */
        $jobDataDto = $this->jobDataDto;
        return $jobDataDto->getIsBackupFormatV1();
    }
}
