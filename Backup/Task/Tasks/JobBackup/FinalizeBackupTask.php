<?php

 
 
 

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use Exception;
use RuntimeException;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Analytics\Actions\AnalyticsBackupCreate;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\BufferedCache;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Dto\Task\Backup\Response\FinalizeBackupResponseDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Exceptions\MissingBackupPartException;
use WPStaging\Backup\Service\BackupMetadataEditor;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Backup\Service\Archiver;
use WPStaging\Backup\WithBackupIdentifier;
use WPStaging\Vendor\lucatume\DI52\NotFoundException;
use WPStaging\Backup\Dto\Service\ArchiverDto;
use WPStaging\Framework\Job\Exception\NotFinishedException;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Job\Exception\ThresholdException;
use WPStaging\Framework\SiteInfo;

class FinalizeBackupTask extends BackupTask
{
    use WithBackupIdentifier;

 
    protected $archiver;

 
    protected $wpdb;

 
    protected $pathIdentifier;

 
    protected $backupMetadataEditor;

 
    protected $analyticsBackupCreate;

 
    protected $sqlCache;

 
    protected $siteInfo;

 
    protected $databaseParts = [];

 
    protected $currentFileIndex = 0;

 
    protected $currentFileInfo = [];













    public function __construct(
        Archiver $archiver,
        BufferedCache $sqlCache,
        LoggerInterface $logger,
        Cache $cache,
        StepsDto $stepsDto,
        SeekableQueueInterface $taskQueue,
        PathIdentifier $pathIdentifier,
        BackupMetadataEditor $backupMetadataEditor,
        AnalyticsBackupCreate $analyticsBackupCreate,
        SiteInfo $siteInfo
    ) {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        global $wpdb;
        $this->archiver              = $archiver;
        $this->sqlCache              = $sqlCache;
        $this->wpdb                  = $wpdb;
        $this->pathIdentifier        = $pathIdentifier;
        $this->backupMetadataEditor  = $backupMetadataEditor;
        $this->analyticsBackupCreate = $analyticsBackupCreate;
        $this->siteInfo              = $siteInfo;
    }





    public static function getTaskName(): string
    {
        return 'backup_combine';
    }





    public static function getTaskTitle(): string
    {
        return 'Preparing Backup File';
    }




    public function execute(): TaskResponseDto
    {
        $this->prepareSetup();
        $this->prepareArchiver();
        $archiverDto  = $this->archiver->getDto();
        $isUploadBackup = count($this->jobDataDto->getStorages()) > 0;

        try {
            $this->addFilesIndex();
            $this->addBackupMetadata($archiverDto, $isUploadBackup);
        } catch (Exception $e) {
            $this->logger->critical(sprintf('Failed to create backup file: %s', $e->getMessage()));
            return $this->generateResponse(false);
        }

        $steps = $this->stepsDto;

        $metadataAdded = $archiverDto->getWrittenBytesTotal() >= $archiverDto->getFileSize();
        $isLastStep = ($steps->getCurrent() + 1) >= $steps->getTotal();

        if ($metadataAdded && $isLastStep) {
            $steps->finish();
            $this->logger->info('Backup file finalized successfully. Now testing backup integrity...');

            return $this->generateResponse(false);
        }

        $incrementStep = true;
        if (!$metadataAdded) {
            $incrementStep = false;
            $this->logger->info(sprintf('Adding backup metadata. Written %d bytes for finalizing backup', $archiverDto->getWrittenBytesTotal()));
        }

        return $this->generateResponse($incrementStep);
    }




    protected function prepareSetup()
    {
        if ($this->jobDataDto->getIsMultipartBackup()) {
            if ($this->stepsDto->getTotal() > 0) {
                return;
            }

            $this->jobDataDto->setCurrentMultipartFileInfoIndex(0);
            $this->stepsDto->setTotal(count($this->jobDataDto->getMultipartFilesInfo()));

            return;
        }

 
 
 
 
        if ($this->stepsDto->getTotal() > 0 && $this->hasMultipartFileInfoForCurrentIndex()) {
            return;
        }

        $this->jobDataDto->setCurrentMultipartFileInfoIndex(0);
        $this->stepsDto->setTotal(1);
        $this->jobDataDto->setMultipartFilesInfo([
            [
                'category'              => '',
                'index'                 => null,
                'filePath'              => null,
                'destination'           => null,
                'status'                => 'Pending',
                'sizeBeforeAddingIndex' => 0,
            ],
        ]);
    }




    protected function prepareArchiver()
    {
        $this->currentFileIndex = $this->jobDataDto->getCurrentMultipartFileInfoIndex();
        $this->currentFileInfo  = $this->getCurrentMultipartFileInfo();
        $this->archiver->createArchiveFile(Archiver::CREATE_BINARY_HEADER);
        $this->archiver->setIsLocalBackup($this->jobDataDto->isLocalBackup());
    }




    protected function hasMultipartFileInfoForCurrentIndex(): bool
    {
        $multipartFilesInfo = $this->jobDataDto->getMultipartFilesInfo();

        return isset($multipartFilesInfo[$this->jobDataDto->getCurrentMultipartFileInfoIndex()]);
    }








    protected function getCurrentMultipartFileInfo(): array
    {
        $multipartFilesInfo = $this->jobDataDto->getMultipartFilesInfo();
        $currentIndex       = $this->jobDataDto->getCurrentMultipartFileInfoIndex();

        if (!isset($multipartFilesInfo[$currentIndex])) {
            throw MissingBackupPartException::forPartIndex($currentIndex);
        }

        return $multipartFilesInfo[$currentIndex];
    }




    protected function getPrefix(): string
    {
        if (is_multisite() && !$this->jobDataDto->getIsNetworkSiteBackup()) {
            return $this->wpdb->base_prefix;
        }

        return $this->wpdb->prefix;
    }






    protected function prepareBackupMetadata(ArchiverDto $archiverDto, bool $isUploadBackup): BackupMetadata
    {
        $backupMetadata = $archiverDto->getBackupMetadata();
        $backupMetadata->setId($this->jobDataDto->getId());
        $backupMetadata->setTotalDirectories($this->jobDataDto->getTotalDirectories());
        $backupMetadata->setTotalFiles($this->jobDataDto->getTotalFiles());
        $backupMetadata->setName($this->jobDataDto->getName());
        $backupMetadata->setIsAutomatedBackup($this->jobDataDto->getIsAutomatedBackup());
        $backupMetadata->setIsBeforeUpdateBackup($this->jobDataDto->getIsBeforeUpdateBackup());
        $backupMetadata->setPrefix($this->getPrefix());

 
        $backupMetadata->setIsExportingPlugins($this->jobDataDto->getIsExportingPlugins());
        $backupMetadata->setIsExportingMuPlugins($this->jobDataDto->getIsExportingMuPlugins());
        $backupMetadata->setIsExportingThemes($this->jobDataDto->getIsExportingThemes());
        $backupMetadata->setIsExportingUploads($this->jobDataDto->getIsExportingUploads());
        $backupMetadata->setIsExportingOtherWpContentFiles($this->jobDataDto->getIsExportingOtherWpContentFiles());
        $backupMetadata->setIsExportingOtherWpRootFiles($this->jobDataDto->getIsExportingOtherWpRootFiles());
        $backupMetadata->setIsExportingDatabase($this->jobDataDto->getIsExportingDatabase());
        $backupMetadata->setScheduleId($this->jobDataDto->getScheduleId());
        $backupMetadata->setScheduleRecurrence($this->jobDataDto->getScheduleRecurrence());
        $backupMetadata->setMultipartMetadata(null);
        $backupMetadata->setCreatedOnPro(WPStaging::isPro());
        $backupMetadata->setHostingType($this->siteInfo->getHostingType());
        $backupMetadata->setIsContaining2GBFile($this->jobDataDto->getIsContaining2GBFile());
        $backupMetadata->setIsZlibCompressed($this->jobDataDto->getIsCompressed());

        $this->addSystemInfoToBackupMetadata($backupMetadata);

        if ($this->jobDataDto->getIsExportingDatabase()) {
            $backupMetadata->setDatabaseFile($this->pathIdentifier->transformPathToIdentifiable($this->jobDataDto->getDatabaseFile()));
            $backupMetadata->setDatabaseFileSize($this->jobDataDto->getDatabaseFileSize());

            $maxTableLength = 0;
            foreach ($this->jobDataDto->getTablesToBackup() as $table) {
 
                $maxTableLength = max($maxTableLength, strlen(substr($table, strlen($this->wpdb->base_prefix))));
            }

            $backupMetadata->setMaxTableLength($maxTableLength);

            $backupMetadata->setNonWpTables($this->jobDataDto->getNonWpTables());
        }

        $backupMetadata->setPlugins(array_keys(get_plugins()));

        $backupMetadata->setMuPlugins(array_keys(get_mu_plugins()));

        $themes = search_theme_directories() ?: [];
        $backupMetadata->setThemes(array_keys($themes));

        if ($this->jobDataDto->getIsMultipartBackup()) {
            $this->addSplitMetadata($backupMetadata, $isUploadBackup);
        }

        $backupMetadata->setTotalChunks($this->jobDataDto->getTotalChunks());
        $backupMetadata->setNetworkAdmins([]);
        if (is_multisite()) {
            $this->addMultisiteMetadata($backupMetadata);
        }

        return $backupMetadata;
    }





    protected function addSystemInfoToBackupMetadata(BackupMetadata &$backupMetadata)
    {
        global $wp_version, $wp_db_version;




        include ABSPATH . WPINC . '/version.php';

 
        $database = WPStaging::make(Database::class);

        $serverType = $database->getServerType();
        $mysqlVersion = $database->getSqlVersion($compact = true);

        $backupMetadata->setPhpVersion(phpversion());
        $backupMetadata->setWpVersion($wp_version);
        /** @phpstan-ignore-line */
        $backupMetadata->setWpDbVersion((string)$wp_db_version);
        /** @phpstan-ignore-line */
        $backupMetadata->setDbCollate($this->wpdb->collate);
        $backupMetadata->setDbCharset($this->wpdb->charset);
        $backupMetadata->setSqlServerVersion($serverType . ' ' . $mysqlVersion);
    }




    protected function getResponseDto(): FinalizeBackupResponseDto
    {
        return new FinalizeBackupResponseDto();
    }







    protected function addSplitMetadata(BackupMetadata $backupMetadata, bool $isUploadBackup)
    {
 
    }





    protected function addMultisiteMetadata(BackupMetadata $backupMetadata)
    {
 
    }




    protected function addFilesIndex()
    {
        if ($this->currentFileInfo['status'] !== 'Pending') {
            return;
        }

        if (($this->jobDataDto->getIsBackupFormatV1()) && $this->currentFileInfo['category'] === PartIdentifier::DATABASE_PART_IDENTIFIER) {
            $this->currentFileInfo['status'] = 'IndexAdded';
            $this->jobDataDto->updateMultipartFileInfo($this->currentFileInfo, $this->currentFileIndex);
            return;
        }

        try {
            $backupSizeBeforeAddingIndex = $this->archiver->addFileIndex();
        } catch (NotFinishedException $ex) {
            $backupSizeBeforeAddingIndex = null;
        } catch (NotFoundException $ex) {
            throw new NotFoundException($ex->getMessage());
        } catch (ThresholdException $e) {
            $backupSizeBeforeAddingIndex = null;
        }

        $archiverDto = $this->archiver->getDto();
        $isFilesIndexAdded = $archiverDto->getWrittenBytesTotal() >= $archiverDto->getFileSize();

        if (!$isFilesIndexAdded) {
            return;
        }

        $this->currentFileInfo['sizeBeforeAddingIndex'] = $backupSizeBeforeAddingIndex;
        $this->currentFileInfo['status'] = 'IndexAdded';
        $this->jobDataDto->updateMultipartFileInfo($this->currentFileInfo, $this->currentFileIndex);
    }







    protected function addBackupMetadata(ArchiverDto $archiverDto, bool $isUploadBackup)
    {
        if ($this->currentFileInfo['status'] !== 'IndexAdded') {
            return;
        }

        $backupMetadata = $this->prepareBackupMetadata($archiverDto, $isUploadBackup);
        if (!$this->jobDataDto->getIsMultipartBackup()) {
 
            $backupFilePath = $this->archiver->generateBackupMetadata($this->currentFileInfo['sizeBeforeAddingIndex']);
            $this->jobDataDto->setBackupFilePath($backupFilePath);

            if ($isUploadBackup) {
                $backupName = basename($backupFilePath);
                $filesToUpload = [];
                $filesToUpload[$backupName] = $backupFilePath;
                $this->jobDataDto->setFilesToUpload($filesToUpload);
            }

            return;
        }

        $this->addMultipartInfoToMetadata($backupMetadata);
    }






    protected function addMultipartInfoToMetadata(BackupMetadata $backupMetadata)
    {
 
    }




    protected function getFinalBackupParentDirectory(): string
    {
        return $this->archiver->getFinalBackupParentDirectory($this->jobDataDto->isLocalBackup());
    }
}
