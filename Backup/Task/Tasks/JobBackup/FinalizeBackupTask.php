<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use Exception;
use RuntimeException;
use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Analytics\Actions\AnalyticsBackupCreate;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\BufferedCache;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Dto\Task\Backup\Response\FinalizeBackupResponseDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupMetadataEditor;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Backup\Service\Compressor;
use WPStaging\Backup\WithBackupIdentifier;
use WPStaging\Vendor\lucatume\DI52\NotFoundException;
use WPStaging\Backup\Dto\Service\CompressorDto;
use WPStaging\Framework\SiteInfo;

class FinalizeBackupTask extends BackupTask
{
    use WithBackupIdentifier;

    /** @var Compressor */
    protected $compressor;

    /** @var \wpdb */
    protected $wpdb;

    /** @var PathIdentifier */
    protected $pathIdentifier;

    /** @var BackupMetadataEditor */
    protected $backupMetadataEditor;

    /** @var AnalyticsBackupCreate */
    protected $analyticsBackupCreate;

    /** @var BufferedCache */
    protected $sqlCache;

    /** @var SiteInfo */
    protected $siteInfo;

    /** @var array */
    protected $databaseParts = [];

    /** @var int */
    protected $currentFileIndex = 0;

    /** @var array */
    protected $currentFileInfo = [];

    /**
     * @param Compressor $compressor
     * @param BufferedCache $sqlCache
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param SeekableQueueInterface $taskQueue
     * @param PathIdentifier $pathIdentifier
     * @param BackupMetadataEditor $backupMetadataEditor
     * @param AnalyticsBackupCreate $analyticsBackupCreate
     * @param SiteInfo $siteInfo
     */
    public function __construct(
        Compressor $compressor,
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
        $this->compressor            = $compressor;
        $this->sqlCache              = $sqlCache;
        $this->wpdb                  = $wpdb;
        $this->pathIdentifier        = $pathIdentifier;
        $this->backupMetadataEditor  = $backupMetadataEditor;
        $this->analyticsBackupCreate = $analyticsBackupCreate;
        $this->siteInfo              = $siteInfo;
    }

    /**
     * @example 'backup_site_restore_themes'
     * @return string
     */
    public static function getTaskName(): string
    {
        return 'backup_combine';
    }

    /**
     * @example 'Restoring Themes From Backup'
     * @return string
     */
    public static function getTaskTitle(): string
    {
        return 'Preparing Backup File';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute(): TaskResponseDto
    {
        $this->prepareSetup();
        $this->prepareCompressor();
        $compressorDto  = $this->compressor->getDto();
        $isUploadBackup = count($this->jobDataDto->getStorages()) > 0;

        try {
            $this->addFilesIndex();
            $this->addBackupMetadata($compressorDto, $isUploadBackup);
        } catch (Exception $e) {
            $this->logger->critical(esc_html__('Failed to create backup file: ', 'wp-staging') . $e->getMessage());
            return $this->generateResponse(false);
        }

        $steps = $this->stepsDto;

        $metadataAdded = $compressorDto->getWrittenBytesTotal() >= $compressorDto->getFileSize();
        $isLastStep = ($steps->getCurrent() + 1) >= $steps->getTotal();

        if ($metadataAdded && $isLastStep) {
            $steps->finish();
            $this->logger->info(esc_html__('Successfully created backup file', 'wp-staging'));

            return $this->generateResponse(false);
        }

        $incrementStep = true;
        if (!$metadataAdded) {
            $incrementStep = false;
            $this->logger->info(sprintf('Written %d bytes to compressed backup', $compressorDto->getWrittenBytesTotal()));
        }

        return $this->generateResponse($incrementStep);
    }

    /**
     * @return void
     */
    protected function prepareSetup()
    {
        if ($this->stepsDto->getTotal() > 0) {
            return;
        }

        $this->jobDataDto->setCurrentMultipartFileInfoIndex(0);

        if (!$this->jobDataDto->getIsMultipartBackup()) {
            $this->stepsDto->setTotal(1);
            $this->jobDataDto->setMultipartFilesInfo([
                [
                    'category'    => '',
                    'index'       => null,
                    'filePath'    => null,
                    'destination' => null,
                    'status'      => 'Pending',
                    'sizeBeforeAddingIndex' => 0
                ]
            ]);

            return;
        }

        $this->stepsDto->setTotal(count($this->jobDataDto->getMultipartFilesInfo()));
    }

    /**
     * @return void
     */
    protected function prepareCompressor()
    {
        $multipartFilesInfo     = $this->jobDataDto->getMultipartFilesInfo();
        $this->currentFileIndex = $this->jobDataDto->getCurrentMultipartFileInfoIndex();
        $this->currentFileInfo  = $multipartFilesInfo[$this->currentFileIndex];
        $this->compressor->setCategoryIndex($this->currentFileInfo['index'], false);
        $this->compressor->setCategory($this->currentFileInfo['category']);
        $this->compressor->setIsLocalBackup($this->jobDataDto->isLocalBackup());
    }

    /**
     * @return string
     */
    protected function getPrefix(): string
    {
        if (is_multisite() && !$this->jobDataDto->getIsNetworkSiteBackup()) {
            return $this->wpdb->base_prefix;
        }

        return $this->wpdb->prefix;
    }

    /**
     * @param CompressorDto $compressorDto
     * @param bool $isUploadBackup
     * @return BackupMetadata
     */
    protected function prepareBackupMetadata(CompressorDto $compressorDto, bool $isUploadBackup): BackupMetadata
    {
        $backupMetadata = $compressorDto->getBackupMetadata();
        $backupMetadata->setId($this->jobDataDto->getId());
        $backupMetadata->setTotalDirectories($this->jobDataDto->getTotalDirectories());
        $backupMetadata->setTotalFiles($this->jobDataDto->getTotalFiles());
        $backupMetadata->setName($this->jobDataDto->getName());
        $backupMetadata->setIsAutomatedBackup($this->jobDataDto->getIsAutomatedBackup());
        $backupMetadata->setPrefix($this->getPrefix());

        // What the backup includes
        $backupMetadata->setIsExportingPlugins($this->jobDataDto->getIsExportingPlugins());
        $backupMetadata->setIsExportingMuPlugins($this->jobDataDto->getIsExportingMuPlugins());
        $backupMetadata->setIsExportingThemes($this->jobDataDto->getIsExportingThemes());
        $backupMetadata->setIsExportingUploads($this->jobDataDto->getIsExportingUploads());
        $backupMetadata->setIsExportingOtherWpContentFiles($this->jobDataDto->getIsExportingOtherWpContentFiles());
        $backupMetadata->setIsExportingDatabase($this->jobDataDto->getIsExportingDatabase());
        $backupMetadata->setScheduleId($this->jobDataDto->getScheduleId());
        $backupMetadata->setMultipartMetadata(null);
        $backupMetadata->setCreatedOnPro(WPStaging::isPro());
        $backupMetadata->setHostingType($this->siteInfo->getHostingType());

        $this->addSystemInfoToBackupMetadata($backupMetadata);

        if ($this->jobDataDto->getIsExportingDatabase()) {
            $backupMetadata->setDatabaseFile($this->pathIdentifier->transformPathToIdentifiable($this->jobDataDto->getDatabaseFile()));
            $backupMetadata->setDatabaseFileSize($this->jobDataDto->getDatabaseFileSize());

            $maxTableLength = 0;
            foreach ($this->jobDataDto->getTablesToBackup() as $table) {
                // Get the biggest table name, without the prefix.
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

        $backupMetadata->setNetworkAdmins([]);
        if (is_multisite()) {
            $this->addMultisiteMetadata($backupMetadata);
        }

        return $backupMetadata;
    }

    /**
     * @see \wp_version_check
     * @see https://codex.wordpress.org/Converting_Database_Character_Sets
     */
    protected function addSystemInfoToBackupMetadata(BackupMetadata &$backupMetadata)
    {
        global $wp_version, $wp_db_version;
        /**
         * @var string $wp_version
         * @var int    $wp_db_version
         */
        include ABSPATH . WPINC . '/version.php';

        /** @var Database $database */
        $database = WPStaging::make(Database::class);

        $serverType = $database->getServerType();
        $mysqlVersion = $database->getSqlVersion($compact = true);

        $backupMetadata->setPhpVersion(phpversion());
        $backupMetadata->setWpVersion($wp_version);
        /** @phpstan-ignore-line */
        $backupMetadata->setWpDbVersion($wp_db_version);
        /** @phpstan-ignore-line */
        $backupMetadata->setDbCollate($this->wpdb->collate);
        $backupMetadata->setDbCharset($this->wpdb->charset);
        $backupMetadata->setSqlServerVersion($serverType . ' ' . $mysqlVersion);
    }

    /**
     * @return FinalizeBackupResponseDto
     */
    protected function getResponseDto(): FinalizeBackupResponseDto
    {
        return new FinalizeBackupResponseDto();
    }

    /**
     * @param BackupMetadata $backupMetadata
     * @param bool $isUploadBackup
     * @return void
     * @throws RuntimeException
     */
    protected function addSplitMetadata(BackupMetadata $backupMetadata, bool $isUploadBackup)
    {
        // no-op, used in pro version.
    }

    /**
     * @param BackupMetadata $backupMetadata
     * @return void
     */
    protected function addMultisiteMetadata(BackupMetadata $backupMetadata)
    {
        // no-op, used in pro version.
    }

    /**
     * @throws NotFoundException
     */
    protected function addFilesIndex()
    {
        if ($this->currentFileInfo['status'] !== 'Pending') {
            return;
        }

        if ($this->currentFileInfo['category'] === DatabaseBackupTask::PART_IDENTIFIER) {
            $this->currentFileInfo['status'] = 'IndexAdded';
            $this->jobDataDto->updateMultipartFileInfo($this->currentFileInfo, $this->currentFileIndex);
            return;
        }

        try {
            $backupSizeBeforeAddingIndex = $this->compressor->addFileIndex();
        } catch (NotFoundException $ex) {
            throw new NotFoundException($ex->getMessage());
        }

        $compressorDto = $this->compressor->getDto();
        $isFilesIndexAdded = $compressorDto->getWrittenBytesTotal() >= $compressorDto->getFileSize();

        if (!$isFilesIndexAdded) {
            return;
        }

        $this->currentFileInfo['sizeBeforeAddingIndex'] = $backupSizeBeforeAddingIndex;
        $this->currentFileInfo['status'] = 'IndexAdded';
        $this->jobDataDto->updateMultipartFileInfo($this->currentFileInfo, $this->currentFileIndex);
    }

    /**
     * @param CompressorDto $compressorDto
     * @param bool $isUploadBackup
     * @return void
     * @throws RuntimeException
     */
    protected function addBackupMetadata(CompressorDto $compressorDto, bool $isUploadBackup)
    {
        if ($this->currentFileInfo['status'] !== 'IndexAdded') {
            return;
        }

        $backupMetadata = $this->prepareBackupMetadata($compressorDto, $isUploadBackup);
        if (!$this->jobDataDto->getIsMultipartBackup()) {
            // Write the Backup metadata
            $backupFilePath = $this->compressor->generateBackupMetadata($this->currentFileInfo['sizeBeforeAddingIndex']);
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

    /**
     * @param BackupMetadata $backupMetadata
     * @return void
     * @throws RuntimeException
     */
    protected function addMultipartInfoToMetadata(BackupMetadata $backupMetadata)
    {
        // no-op, used in pro version.
    }

    /**
     * @return string
     */
    protected function getFinalBackupParentDirectory(): string
    {
        return $this->compressor->getFinalBackupParentDirectory($this->jobDataDto->isLocalBackup());
    }
}
