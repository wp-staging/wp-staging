<?php

 

namespace WPStaging\Backup\Service;

use Exception;
use LogicException;
use RuntimeException;
use WPStaging\Backup\BackupFileIndex;
use WPStaging\Backup\BackupHeader;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Dto\Service\ArchiverDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Exceptions\BackupSkipItemException;
use WPStaging\Backup\FileHeader;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Adapter\PhpAdapter;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Job\Exception\NotFinishedException;
use WPStaging\Framework\Job\Exception\ThresholdException;
use WPStaging\Framework\Traits\EndOfLinePlaceholderTrait;
use WPStaging\Framework\Utils\Cache\BufferedCache;
use WPStaging\Vendor\lucatume\DI52\NotFoundException;

use function WPStaging\functions\debug_log;




class Archiver
{
    use EndOfLinePlaceholderTrait;




    const BACKUP_EXTENSION = 'wpstg';





    const TMP_BACKUP_EXTENSION = 'wpstgtmp';





    const MAX_RETRIES_BEFORE_EXTENDING_TIME_LIMIT = 1;





    const MAX_ALLOWED_PHP_TIME_LIMIT = 60;





    const MIN_ALLOWED_PHP_TIME_LIMIT = 10;






    const PHP_TIME_LIMIT_IN_FRACTION = 0.8;

 
    const BACKUP_DIR_NAME = 'backups';

 
    const CREATE_BINARY_HEADER = true;

 
    protected $tempBackupIndex;

 
    protected $tempBackup;

 
    protected $archiverDto;

 
    protected $pathIdentifier;

 
    protected $archivedFileSize = 0;

 
    protected $jobDataDto;

 
    protected $phpAdapter;

 
    protected $isLocalBackup = false;

 
    protected $bytesWrittenInThisRequest = 0;

 
    protected $fileHeader;

 
    protected $backupHeader;

 
    protected $backupFileIndex;

 
    protected $filesystem;

 
    protected $isTempBackup = false;

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





    public function setFileAppendTimeLimit(int $fileAppendTimeLimit)
    {
        $this->tempBackup->setFileAppendTimeLimit($fileAppendTimeLimit);
        $this->tempBackupIndex->setFileAppendTimeLimit($fileAppendTimeLimit);
    }





    public function setIsTempBackup(bool $isTempBackup)
    {
        $this->isTempBackup = $isTempBackup;
    }





    public function createArchiveFile(bool $isCreateBinaryHeader = false)
    {
        $this->setupTmpBackupFile();

        if ($isCreateBinaryHeader && !$this->tempBackup->isValid()) {
 
            $this->tempBackup->save($this->isBackupFormatV1() ? $this->backupHeader->getV1FormatHeader() : $this->backupHeader->getHeader() . "\n");
        }
    }





    public function setupTmpBackupFile()
    {
        $this->tempBackup->setFilename('temp_wpstg_backup_' . $this->jobDataDto->getId());
        $this->tempBackup->setLifetime(DAY_IN_SECONDS);

        $tempBackupIndexFilePrefix = 'temp_backup_index_';
        $this->tempBackupIndex->setFilename($tempBackupIndexFilePrefix . $this->jobDataDto->getId());
        $this->tempBackupIndex->setLifetime(DAY_IN_SECONDS);
    }




    public function setIsLocalBackup(bool $isLocalBackup)
    {
        $this->isLocalBackup = $isLocalBackup;
    }




    public function getDto(): ArchiverDto
    {
        return $this->archiverDto;
    }




    public function getBytesWrittenInThisRequest(): int
    {
        return $this->bytesWrittenInThisRequest;
    }




    public function getTempBackupIndex(): BufferedCache
    {
        return $this->tempBackupIndex;
    }




    public function getTempBackup(): BufferedCache
    {
        return $this->tempBackup;
    }















    public function appendFileToBackup(string $fullFilePath, string $indexPath = ''): bool
    {
 
 
 
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
        } elseif (!$this->isBackupFormatV1()) {
            $identifiablePath = $this->pathIdentifier->transformPathToIdentifiable($this->filesystem->maybeNormalizePath($indexPath));
            $this->fileHeader->readFile($fullFilePath, $identifiablePath);
        }

        $writtenBytesBefore = $this->archiverDto->getWrittenBytesTotal();
        try {
            $writtenBytesTotal = $this->appendToArchiveFile($resource, $fullFilePath);
        } catch (ThresholdException $ex) {
 
            fclose($resource);
            $resource = null;
            $this->maybeIncrementFileAppendTimeLimit();

            throw $ex;
        }

        $newBytesWritten                 = $writtenBytesTotal + $fileHeaderSizeInBytes - $writtenBytesBefore;
        $writtenBytesIncludingFileHeader = $writtenBytesTotal + $this->archiverDto->getFileHeaderSizeInBytes();

        if (!$this->isBackupFormatV1() && empty($this->fileHeader->getFilePath())) {
            $identifiablePath = $this->pathIdentifier->transformPathToIdentifiable($this->filesystem->maybeNormalizePath($indexPath));
            $this->fileHeader->readFile($fullFilePath, $identifiablePath);
        }

        $retries = 0;

        if (!$this->isBackupFormatV1() && empty($this->fileHeader->getFilePath())) {
            $identifiablePath = $this->pathIdentifier->transformPathToIdentifiable($this->filesystem->maybeNormalizePath($indexPath));
            $this->fileHeader->readFile($fullFilePath, $identifiablePath);
        }

        do {
            if ($retries > 0) {
                usleep((int)$this->getDelayForRetry($retries));
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






    public function initiateDtoByFilePath(string $filePath, array $fileStats = []): bool
    {
        if (empty($filePath) || ($filePath === $this->archiverDto->getFilePath() && $fileStats['size'] === $this->archiverDto->getFileSize())) {
            return false;
        }

        $this->archiverDto->setFilePath($filePath);
        $this->archiverDto->setFileSize($fileStats['size']);
        return true;
    }











    public function generateBackupMetadata(int $backupSizeBeforeAddingIndex = 0, string $finalFileNameOnRename = ''): string
    {
        clearstatcache();
        $backupSizeAfterAddingIndex = filesize($this->tempBackup->getFilePath());

        $backupMetadata = $this->archiverDto->getBackupMetadata();
        $backupMetadata->setHeaderStart($backupSizeBeforeAddingIndex);
        $backupMetadata->setHeaderEnd($backupSizeAfterAddingIndex);

        if ($this->jobDataDto instanceof JobBackupDataDto) {
 
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
            $this->tempBackup->append(''); 
        }

        clearstatcache();
        $backupSizeBeforeAddingIndex = filesize($this->tempBackup->getFilePath());
        $backupIndexFileSize         = filesize($this->tempBackupIndex->getFilePath());

 
 
        $writtenBytes = $this->appendToArchiveFile($indexResource, $this->tempBackupIndex->getFilePath());
        $this->archiverDto->setWrittenBytesTotal($writtenBytes);

        if ($writtenBytes === 0) {
            $this->jobDataDto->setRetries($this->jobDataDto->getRetries() + 1);
        } else {
            $this->jobDataDto->setRetries(0);
        }

 
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




    public function getDestinationPath(): string
    {
        return sprintf(
            '%s_%s_%s.%s',
            parse_url(get_home_url())['host'],
            current_time('Ymd-His'),
            $this->jobDataDto->getId(),
            self::BACKUP_EXTENSION
        );
    }






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






    protected function writeFileHeader(string $filePath, string $indexPath): int
    {
        $identifiablePath = $this->pathIdentifier->transformPathToIdentifiable($this->filesystem->maybeNormalizePath($indexPath));
        $this->fileHeader->readFile($filePath, $identifiablePath);

        return $this->tempBackup->append($this->fileHeader->getFileHeader());
    }







    protected function getDelayForRetry(int $retry): float
    {
        $delay = 0.1;
        for ($i = 0; $i < $retry; $i++) {
            $delay *= 2;
        }

        return $delay * 1000;
    }






    protected function setBackupMetadataCategoryInfo(BackupMetadata $backupMetadata, JobBackupDataDto $jobBackupDataDto)
    {
        $backupMetadata->setIndexPartSize($jobBackupDataDto->getCategorySizes());
    }





    protected function incrementFilesCount(JobBackupDataDto $jobBackupDataDto)
    {
        $jobBackupDataDto->setTotalFiles($jobBackupDataDto->getTotalFiles() + 1);
    }




    protected function setIndexPositionCreated()
    {
        $this->archiverDto->setIndexPositionCreated(true);
    }




    protected function isIndexPositionCreated(): bool
    {
        return $this->archiverDto->isIndexPositionCreated();
    }





    protected function maybeIncrementFileAppendTimeLimit()
    {
        $this->jobDataDto->incrementNumberOfRetries();
        if ($this->jobDataDto->getNumberOfRetries() > self::MAX_RETRIES_BEFORE_EXTENDING_TIME_LIMIT) {
            return;
        }

 
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
            return (int)Hooks::applyFilters(JobDataDto::FILTER_RESOURCES_EXECUTION_TIME_LIMIT, $maxAllowedPhpTimeLimit);
        }

        $maxAllowedPhpTimeLimit = max(self::MIN_ALLOWED_PHP_TIME_LIMIT, $maxAllowedPhpTimeLimit);
        $maxAllowedPhpTimeLimit = min(self::MAX_ALLOWED_PHP_TIME_LIMIT, $maxAllowedPhpTimeLimit);
        $maxAllowedPhpTimeLimit = $maxAllowedPhpTimeLimit * self::PHP_TIME_LIMIT_IN_FRACTION;

        return (int)Hooks::applyFilters(JobDataDto::FILTER_RESOURCES_EXECUTION_TIME_LIMIT, $maxAllowedPhpTimeLimit);
    }




    protected function resetFileAppendTimeLimitAndRetries()
    {
 
        $jobDataDto = $this->jobDataDto;

        $jobDataDto->resetFileAppendTimeLimit();
        $jobDataDto->resetNumberOfRetries();
    }

    protected function addNewFileHeaderToIndex(int $writtenBytes, int $startOffset): int
    {
        if ($this->isIndexPositionCreated()) {
            return 0;
        }

        $this->fileHeader->setStartOffset($startOffset);
        return $this->tempBackupIndex->append($this->fileHeader->getIndexHeader());
    }










    protected function appendToArchiveFile($resource, string $filePath): int
    {
        try {
            return $this->tempBackup->appendFile(
                $resource,
                $this->archiverDto->getWrittenBytesTotal()
            );
        } catch (DiskNotWritableException $e) {
            debug_log('Failed to write to file: ' . $filePath);
            throw $this->reclassifyDiskFailure($e);
        }
    }








    protected function reclassifyDiskFailure(DiskNotWritableException $original): DiskNotWritableException
    {
        try {
            $remainingBytes = max(
                0,
                $this->archiverDto->getFileSize() - $this->archiverDto->getWrittenBytesTotal()
            );
            WPStaging::make(DiskWriteCheck::class)->checkPathCanStoreEnoughBytes(
                dirname($this->tempBackup->getFilePath()),
                $remainingBytes
            );
        } catch (DiskNotWritableException $diskFull) {
            return $diskFull;
        } catch (RuntimeException $cannotDetermine) {
 
        }

        return $original;
    }







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









    private function addIndex(int $writtenBytesTotal, int $newBytesAdded = 0): int
    {
        clearstatcache();
        if (file_exists($this->tempBackup->getFilePath())) {
            $this->archivedFileSize = filesize($this->tempBackup->getFilePath());
        }

        $start = max($this->archivedFileSize - $writtenBytesTotal, 0);

        $identifiablePath = $this->pathIdentifier->transformPathToIdentifiable($this->archiverDto->getIndexPath());

 
        if ($this->isBackupFormatV1() && $this->isIndexPositionCreated()) {
            return $this->updateIndexInformationForAlreadyAddedIndex($writtenBytesTotal);
        }

        if ($this->isBackupFormatV1()) {
            $identifiablePath = $this->pathIdentifier->transformPathToIdentifiable($this->archiverDto->getIndexPath());
            $backupFileIndex  = $this->backupFileIndex->createIndex($identifiablePath, $start, $writtenBytesTotal, false);
            $bytesWritten     = $this->tempBackupIndex->append($backupFileIndex->getIndex());
        }

        if (!$this->isBackupFormatV1()) {
            $bytesWritten = $this->addNewFileHeaderToIndex($newBytesAdded, $start);
            if ($this->isIndexPositionCreated()) {
                $this->addIndexPartSize($identifiablePath, $newBytesAdded);
                return $newBytesAdded;
            }
        }

        $this->archiverDto->setIndexPositionCreated(true);

        $this->addIndexPartSize($identifiablePath, $writtenBytesTotal);






        if (!$this->phpAdapter->isCallable([$this->jobDataDto, 'setTotalFiles']) || !$this->phpAdapter->isCallable([$this->jobDataDto, 'getTotalFiles'])) {
            debug_log('This method can only be called from the context of Backup');
            throw new LogicException('This method can only be called from the context of Backup');
        }

 
        $jobBackupDataDto = $this->jobDataDto;
        if ($this->archiverDto->getFileSize() >= 2 * GB_IN_BYTES) {
            $jobBackupDataDto->setIsContaining2GBFile(true);
        }

        $this->incrementFilesCount($jobBackupDataDto);

        return $bytesWritten;
    }







    protected function addIndexPartSize(string $identifiablePath, int $newBytesWritten)
    {
 
        if (!$this->jobDataDto instanceof JobBackupDataDto) {
            return;
        }

 
        $jobDataDto = $this->jobDataDto;

        $collectPartSize = $jobDataDto->getCategorySizes();

        $partName = '';
        switch ($identifiablePath) {
            case ($this->pathIdentifier::IDENTIFIER_WP_CONTENT === substr($identifiablePath, 0, strlen($this->pathIdentifier::IDENTIFIER_WP_CONTENT))):
                $partName = PartIdentifier::WP_CONTENT_PART_SIZE_IDENTIFIER;

                if ($this->pathIdentifier->hasDropinsFile($identifiablePath)) {
                    $dropinsPartName = PartIdentifier::DROPIN_PART_SIZE_IDENTIFIER;
                    if (!isset($collectPartSize[$dropinsPartName])) {
                        $collectPartSize[$dropinsPartName] = 0;
                    }

                    $collectPartSize[$dropinsPartName] += $newBytesWritten;
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

 
        if (!isset($collectPartSize[$partName])) {
            $collectPartSize[$partName] = 0;
        }

        $collectPartSize[$partName] += $newBytesWritten;
        $jobDataDto->setCategorySizes($collectPartSize);
    }








    private function updateIndexInformationForAlreadyAddedIndex(int $writtenBytesTotal): int
    {
        $lastLine = $this->tempBackupIndex->readLines(1, null, BufferedCache::POSITION_BOTTOM);
        if (!is_array($lastLine)) {
            debug_log('Failed to read backup metadata file index information. Error: The last line is no array. Last line: ' . $lastLine);
            throw new RuntimeException('Failed to read backup metadata file index information. Error: The last line is no array.');
        }

        $lastLine = array_filter($lastLine, [$this->backupFileIndex, 'isIndexLine']);

        if (count($lastLine) !== 1) {
            debug_log('Failed to read backup metadata file index information. Error: The last line is not an array or element with countable interface. Last line: ' . print_r($lastLine, true));
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

 
        $this->addIndexPartSize($identifiablePath, $writtenBytesTotal - (int)$writtenPreviously);

        return $bytesWritten;
    }

    private function isBackupFormatV1(): bool
    {
 
        $jobDataDto = $this->jobDataDto;
        return $jobDataDto->getIsBackupFormatV1();
    }
}
