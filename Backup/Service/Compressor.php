<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints
// TODO PHP7.1; constant visibility

namespace WPStaging\Backup\Service;

use Exception;
use LogicException;
use RuntimeException;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Dto\JobDataDto;
use WPStaging\Backup\Dto\Service\CompressorDto;
use WPStaging\Backup\Exceptions\DiskNotWritableException;
use WPStaging\Backup\Service\Multipart\MultipartSplitInterface;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Adapter\PhpAdapter;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Utils\Cache\BufferedCache;
use WPStaging\Vendor\lucatume\DI52\NotFoundException;

use function WPStaging\functions\debug_log;

class Compressor
{
    const BACKUP_DIR_NAME = 'backups';

    /** @var BufferedCache */
    private $tempBackupIndex;

    /** @var BufferedCache */
    private $tempBackup;

    /** @var CompressorDto */
    private $compressorDto;

    /** @var PathIdentifier */
    private $pathIdentifier;

    /** @var int */
    private $compressedFileSize = 0;

    /** @var JobDataDto */
    private $jobDataDto;

    /** @var PhpAdapter */
    private $phpAdapter;

    /** @var MultipartSplitInterface */
    private $multipartSplit;

    /**
     * Category can be: empty string|null|false, plugins, mu-plugins, themes, uploads, other, database
     * Where empty string|null|false is used for single file backup,
     * And other is for files from wp-content not including plugins, mu-plugins, themes, uploads
     * @var string
     */
    private $category = '';

    /**
     * The current index of category in which appending files
     * Not used in single file backup
     * @var int
     */
    private $categoryIndex = 0;

    /** @var bool */
    private $isLocalBackup = false;

    /** @var int */
    protected $bytesWrittenInThisRequest = 0;

    // TODO telescoped
    public function __construct(BufferedCache $cacheIndex, BufferedCache $tempBackup, PathIdentifier $pathIdentifier, JobDataDto $jobDataDto, CompressorDto $compressorDto, PhpAdapter $phpAdapter, MultipartSplitInterface $multipartSplit)
    {
        $this->jobDataDto      = $jobDataDto;
        $this->compressorDto   = $compressorDto;
        $this->tempBackupIndex = $cacheIndex;
        $this->tempBackup      = $tempBackup;
        $this->pathIdentifier  = $pathIdentifier;
        $this->phpAdapter      = $phpAdapter;
        $this->multipartSplit  = $multipartSplit;

        $this->setCategory('');
    }

    /**
     * @param int $index
     * @param bool $isCreateBinaryHeader
     */
    public function setCategoryIndex($index, $isCreateBinaryHeader = true)
    {
        if (empty($index)) {
            $index = 0;
        }

        $this->categoryIndex = $index;
        $this->setCategory($this->category, $isCreateBinaryHeader);
    }

    /**
     * @param string $category
     * @param bool $isCreateBinaryHeader
     */
    public function setCategory($category = '', $isCreateBinaryHeader = false)
    {
        $this->category = $category;
        $this->setupTmpBackupFile();

        if ($isCreateBinaryHeader && !$this->tempBackup->isValid()) {
            // Create temp file with binary header
            $this->tempBackup->save(file_get_contents(WPSTG_PLUGIN_DIR . 'Backup/wpstgBackupHeader.txt'));
        }
    }

    /**
     * Setup temp backup file and temp files index file for the given job id,
     * If multipart backup category and category index are given, then they are used to create unique file names
     */
    public function setupTmpBackupFile()
    {
        $additionalInfo = empty($this->category) ? '' : $this->category . '_' . $this->categoryIndex . '_';

        $postFix = $additionalInfo . $this->jobDataDto->getId();

        //debug_log("[Set Tmp Backup Files] File name postfix: " . $postFix);

        $this->tempBackup->setFilename('temp_wpstg_backup_' . $postFix);
        $this->tempBackup->setLifetime(DAY_IN_SECONDS);

        $tempBackupIndexFilePrefix = 'temp_backup_index_';
        $this->tempBackupIndex->setFilename($tempBackupIndexFilePrefix . $postFix);
        $this->tempBackupIndex->setLifetime(DAY_IN_SECONDS);
    }

    /**
     * @param int $fileSize
     * @param int $maxPartSize
     * @return bool
     */
    public function doExceedMaxPartSize($fileSize, $maxPartSize)
    {
        $allowedSize     = $fileSize - $this->compressorDto->getWrittenBytesTotal();
        $sizeAfterAdding = $allowedSize + filesize($this->tempBackup->getFilePath());
        return $sizeAfterAdding >= $maxPartSize;
    }

    /**
     * @var bool $isLocalBackup
     */
    public function setIsLocalBackup($isLocalBackup)
    {
        $this->isLocalBackup = $isLocalBackup;
    }

    /**
     * @return CompressorDto
     */
    public function getDto()
    {
        return $this->compressorDto;
    }

    /**
     * @return int
     */
    public function getBytesWrittenInThisRequest()
    {
        return $this->bytesWrittenInThisRequest;
    }

    /**
     * @param string $fullFilePath
     * @param string $indexPath
     *
     * `true` -> finished
     * `false` -> not finished
     * `null` -> skip / didn't do anything
     *
     * @throws DiskNotWritableException
     * @throws RuntimeException
     *
     * @return bool|null
     */
    public function appendFileToBackup(string $fullFilePath, string $indexPath = '')
    {
        // We can use evil '@' as we don't check is_file || file_exists to speed things up.
        // Since in this case speed > anything else
        // However if @ is not used, depending on if file exists or not this can throw E_WARNING.
        $resource = @fopen($fullFilePath, 'rb');
        if (!$resource) {
            debug_log("appendFileToBackup(): Can't open file {$fullFilePath} for reading");
            return null;
        }

        if (empty($indexPath)) {
            $indexPath = $fullFilePath;
        }

        $fileStats = fstat($resource);
        $this->initiateDtoByFilePath($fullFilePath, $fileStats);
        $this->compressorDto->setIndexPath($indexPath);
        $writtenBytesBefore = $this->compressorDto->getWrittenBytesTotal();
        $writtenBytesTotal  = $this->appendToCompressedFile($resource, $fullFilePath);
        $bytesAddedForIndex = $this->addIndex($writtenBytesTotal);
        $retries            = 0;
        while ($bytesAddedForIndex === 0 && $retries < 3) {
            $delayInMs = $this->getDelayForRetry($retries);
            // sleep in ms
            usleep($delayInMs);
            $bytesAddedForIndex = $this->addIndex($writtenBytesTotal);
            $retries++;
        }

        $this->compressorDto->setWrittenBytesTotal($writtenBytesTotal);

        $this->bytesWrittenInThisRequest += $writtenBytesTotal - $writtenBytesBefore;

        $isFinished = $this->compressorDto->isFinished();

        $this->compressorDto->resetIfFinished();

        return $isFinished;
    }

    /**
     * @param string $filePath
     * @param array $fileStats
     */
    public function initiateDtoByFilePath($filePath, array $fileStats = [])
    {
        if ($filePath === null || ($filePath === $this->compressorDto->getFilePath() && $fileStats['size'] === $this->compressorDto->getFileSize())) {
            return;
        }

        $this->compressorDto->setFilePath($filePath);
        $this->compressorDto->setFileSize($fileStats['size']);
    }

    /**
     * @param int    $sizeBeforeAddingIndex
     * @param string $category
     * @param string $partName
     * @param int    $categoryIndex
     */
    public function generateBackupMetadataForBackupPart($sizeBeforeAddingIndex, $category, $partName, $categoryIndex)
    {
        $this->category      = $category;
        $this->categoryIndex = $categoryIndex;
        $this->setupTmpBackupFile();
        $this->generateBackupMetadata($sizeBeforeAddingIndex, $partName, $isBackupPart = true);
    }

    /**
     * Combines index and compressed file, renames / moves it to destination
     *
     * This function is called only once, so performance improvements has no impact here.
     *
     * @param int $backupSizeBeforeAddingIndex
     * @param string $finalFileNameOnRename
     * @param bool $isBackupPart
     *
     * @return string|null
     */
    public function generateBackupMetadata($backupSizeBeforeAddingIndex = 0, $finalFileNameOnRename = '', $isBackupPart = false)
    {
        clearstatcache();
        $backupSizeAfterAddingIndex = filesize($this->tempBackup->getFilePath());

        $backupMetadata = $this->compressorDto->getBackupMetadata();
        $backupMetadata->setHeaderStart($backupSizeBeforeAddingIndex);
        $backupMetadata->setHeaderEnd($backupSizeAfterAddingIndex);

        if ($isBackupPart) {
            $this->multipartSplit->updateMultipartMetadata($this->jobDataDto, $backupMetadata, $this->category, $this->categoryIndex);
        }

        if ($this->jobDataDto instanceof JobBackupDataDto) {
            /** @var JobBackupDataDto */
            $jobDataDto = $this->jobDataDto;
            $backupMetadata->setIndexPartSize($jobDataDto->getCategorySizes());
        }

        $this->tempBackup->append(json_encode($backupMetadata));

        return $this->renameBackup($finalFileNameOnRename);
    }

    /**
     * @return array
     */
    public function getFinalizeBackupInfo()
    {
        return [
            'category'              => $this->category,
            'index'                 => $this->categoryIndex,
            'filePath'              => $this->tempBackup->getFilePath(),
            'destination'           => $this->getDestinationPath(),
            'status'                => 'Pending',
            'sizeBeforeAddingIndex' => 0
        ];
    }

    /** @return int|null */
    public function addFileIndex()
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
        $writtenBytes = $this->compressorDto->getWrittenBytesTotal();
        if ($lastLine !== PHP_EOL && $writtenBytes === 0) {
            $this->tempBackup->append(''); // ensure that file index start from new line. See https://github.com/wp-staging/wp-staging-pro/issues/2861
        }

        clearstatcache();
        $backupSizeBeforeAddingIndex = filesize($this->tempBackup->getFilePath());

        // Write the index to the backup file, regardless of resource limits threshold
        // @throws Exception
        $writtenBytes = $this->appendToCompressedFile($indexResource, $this->tempBackupIndex->getFilePath());
        $this->compressorDto->setWrittenBytesTotal($writtenBytes);

        if ($writtenBytes === 0) {
            $this->jobDataDto->setRetries($this->jobDataDto->getRetries() + 1);
        } else {
            $this->jobDataDto->setRetries(0);
        }

        // close the index file handle to make it deletable for Windows where PHP < 7.3
        fclose($indexResource);

        if ($this->jobDataDto->getRetries() > 3) {
            debug_log('[Add File Index] Failed to write files-index to backup file!');
            throw new Exception('Failed to write files-index to backup file!');
        } elseif ($writtenBytes === 0) {
            debug_log('[Add File Index] Failed to write any byte to files-index! Retrying...');
        }

        if (!$this->compressorDto->isFinished()) {
            return null;
        }

        $this->tempBackupIndex->delete();
        $this->compressorDto->reset();

        $this->tempBackup->append(PHP_EOL);

        return $backupSizeBeforeAddingIndex;
    }

    /**
     * @return string
     */
    private function getDestinationPath()
    {
        $extension = "wpstg";

        if ($this->category !== '') {
            $index     = $this->categoryIndex === 0 ? '' : ($this->categoryIndex . '.');
            $extension = $this->category . '.' . $index . $extension;
        }

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
    public function getFinalPath($renameFileTo = '', $isLocalBackup = true)
    {
        $backupsDirectory = $this->getFinalBackupParentDirectory($isLocalBackup);
        if ($renameFileTo === '') {
            $renameFileTo = $this->getDestinationPath();
        }

        return $backupsDirectory . $renameFileTo;
    }

    /**
     * @return string
     */
    public function getFinalBackupParentDirectory($isLocalBackup = true)
    {
        if ($isLocalBackup) {
            return WPStaging::make(BackupsFinder::class)->getBackupsDirectory();
        }

        return WPStaging::make(Directory::class)->getCacheDirectory();
    }

    /**
     * Get delay in milliseconds for retry according to retry number
     *
     * @param int $retry
     * @return float
     */
    protected function getDelayForRetry($retry)
    {
        $delay = 0.1;
        for ($i = 0; $i < $retry; $i++) {
            $delay *= 2;
        }

        return $delay * 1000;
    }

    /**
     * Convert PIPE character (|) to {WPSTG_PIPE}
     * Convert COLON character (:) to {WPSTG_COLON}
     * @param string $filePath
     * @return string
     */
    protected function filterPathForFileIndex(string $filePath): string
    {
        return str_replace(['|', ':'], ['{WPSTG_PIPE}', '{WPSTG_COLON}'], $filePath);
    }

    /** @var string $renameFileTo */
    private function renameBackup($renameFileTo = '')
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
     * @param  int $writtenBytesTotal
     * @return int
     * @throws \WPStaging\Framework\Exceptions\IOException
     * @throws LogicException
     * @throws RuntimeException
     */
    private function addIndex($writtenBytesTotal)
    {
        clearstatcache();
        if (file_exists($this->tempBackup->getFilePath())) {
            $this->compressedFileSize = filesize($this->tempBackup->getFilePath());
        }

        $start = max($this->compressedFileSize - $writtenBytesTotal, 0);

        if ($this->compressorDto->isIndexPositionCreated($this->category, $this->categoryIndex)) {
            return $this->updateIndexInformationForAlreadyAddedIndex($writtenBytesTotal);
        }

        $identifiablePath = $this->pathIdentifier->transformPathToIdentifiable($this->compressorDto->getIndexPath());
        $identifiablePath = $this->filterPathForFileIndex($identifiablePath);
        $info             = $identifiablePath . '|' . $start . ':' . $writtenBytesTotal;
        $bytesWritten     = $this->tempBackupIndex->append($info);
        $this->compressorDto->setIndexPositionCreated(true);

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
        $jobBackupDataDto->setTotalFiles($jobBackupDataDto->getTotalFiles() + 1);

        $this->multipartSplit->incrementFileCountInPart($jobBackupDataDto, $this->category, $this->categoryIndex);

        return $bytesWritten;
    }

    /**
     * At the moment this is used when processing adding of big file which is not done in a single request
     * @param int $writtenBytesTotal
     * @return int
     * @throws RuntimeException
     */
    private function updateIndexInformationForAlreadyAddedIndex($writtenBytesTotal)
    {
        $lastLine = $this->tempBackupIndex->readLines(1, null, BufferedCache::POSITION_BOTTOM);
        if (!is_array($lastLine)) {
            debug_log('Failed to read backup metadata file index information. Error: The last line is no array. Last line: ' . $lastLine);
            throw new RuntimeException('Failed to read backup metadata file index information. Error: The last line is no array.');
        }

        $lastLine = array_filter($lastLine, function ($item) {
            return !empty($item) && strpos($item, ':') !== false && strpos($item, '|') !== false;
        });

        if (count($lastLine) !== 1) {
            debug_log('Failed to read backup metadata file index information. Error: The last line is not an array or element with countable interface. Last line: ' . print_r($lastLine, 1));
            throw new RuntimeException('Failed to read backup metadata file index information. Error: The last line is not an array or element with countable interface.');
        }

        $lastLine = array_shift($lastLine);

        list($relativePath, $indexPosition) = explode('|', trim($lastLine));

        // ['9378469', '4491']
        list($offsetStart, $writtenPreviously) = explode(':', trim($indexPosition));

        // @todo Should we use mb_strlen($_writtenBytes, '8bit') instead of strlen?
        $this->tempBackupIndex->deleteBottomBytes(strlen($lastLine));

        $identifiablePath = $this->pathIdentifier->transformPathToIdentifiable($this->compressorDto->getIndexPath());
        $identifiablePath = $this->filterPathForFileIndex($identifiablePath);
        $info             = $identifiablePath . '|' . $offsetStart . ':' . $writtenBytesTotal;
        $bytesWritten     = $this->tempBackupIndex->append($info);
        $this->compressorDto->setIndexPositionCreated(true, $this->category, $this->categoryIndex);

        // We only need to increment newly added bytes
        $this->addIndexPartSize($identifiablePath, $writtenBytesTotal - (int)$writtenPreviously);

        return $bytesWritten;
    }

    /**
     * @param $resource
     * @param $filePath
     *
     * @return int
     * @throws DiskNotWritableException
     * @throws RuntimeException
     */
    private function appendToCompressedFile($resource, $filePath)
    {
        try {
            return $this->tempBackup->appendFile(
                $resource,
                $this->compressorDto->getWrittenBytesTotal()
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
     */
    private function addIndexPartSize($identifiablePath, $newBytesWritten)
    {
        // Early bail if jobDataDto is not instance of jobBackupDataDto
        if (!$this->jobDataDto instanceof JobBackupDataDto) {
            return;
        }

        /** @var JobBackupDataDto $jobDataDto */
        $jobDataDto = $this->jobDataDto;

        $collectPartsize = $jobDataDto->getCategorySizes();

        $partName = 'unknownSize';
        switch ($identifiablePath) {
            case ($this->pathIdentifier::IDENTIFIER_WP_CONTENT === substr($identifiablePath, 0, strlen($this->pathIdentifier::IDENTIFIER_WP_CONTENT))):
                $partName = 'wpcontentSize';
                break;
            case ($this->pathIdentifier::IDENTIFIER_PLUGINS === substr($identifiablePath, 0, strlen($this->pathIdentifier::IDENTIFIER_PLUGINS))):
                $partName = 'pluginsSize';
                break;
            case ($this->pathIdentifier::IDENTIFIER_THEMES === substr($identifiablePath, 0, strlen($this->pathIdentifier::IDENTIFIER_THEMES))):
                $partName = 'themesSize';
                break;
            case ($this->pathIdentifier::IDENTIFIER_MUPLUGINS === substr($identifiablePath, 0, strlen($this->pathIdentifier::IDENTIFIER_MUPLUGINS))):
                $partName = 'mupluginsSize';
                break;
            case ($this->pathIdentifier::IDENTIFIER_UPLOADS === substr($identifiablePath, 0, strlen($this->pathIdentifier::IDENTIFIER_UPLOADS))):
                $partName = 'uploadsSize';
                if (substr($identifiablePath, -4) === '.sql') {
                    $partName = 'sqlSize';
                }

                break;
            case ($this->pathIdentifier::IDENTIFIER_LANG === substr($identifiablePath, 0, strlen($this->pathIdentifier::IDENTIFIER_LANG))):
                $partName = 'langSize';
                break;
        }

        // TODO: This should never happen. Log this when we have our own Logger, see https://github.com/wp-staging/wp-staging-pro/pull/2440#discussion_r1247951548
        if (!isset($collectPartsize[$partName])) {
            $collectPartsize[$partName] = 0;
        }

        $collectPartsize[$partName] += $newBytesWritten;
        $jobDataDto->setCategorySizes($collectPartsize);
    }
}
