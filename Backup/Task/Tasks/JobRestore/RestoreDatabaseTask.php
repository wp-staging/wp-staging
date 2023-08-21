<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use Exception;
use RuntimeException;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Backup\Service\Database\Importer\DatabaseSearchReplacerInterface;
use WPStaging\Backup\Service\Multipart\MultipartRestoreInterface;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Framework\Filesystem\MissingFileException;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class RestoreDatabaseTask extends RestoreTask
{
    /**
     * After this time, we will increase the execution by 5s for database restore.
     * @var int
     */
    const MAX_RETRIES = 3;

    /**
     * After this time (in seconds), we will stop the database restore.
     * @var int
     */
    const MAX_EXECUTION_TIME_ALLOWED = 60;

    /** @var DatabaseImporter */
    private $databaseImporter;

    /** @var PathIdentifier */
    private $pathIdentifier;

    /** @var DatabaseSearchReplacerInterface */
    private $databaseSearchReplacer;

    /** @var MultipartRestoreInterface */
    private $multipartRestore;

    public function __construct(DatabaseImporter $databaseImporter, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, PathIdentifier $pathIdentifier, DatabaseSearchReplacerInterface $databaseSearchReplacer, MultipartRestoreInterface $multipartRestore)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        $this->databaseImporter = $databaseImporter;
        $this->databaseImporter->setup($this->logger, $stepsDto, $this);

        $this->pathIdentifier         = $pathIdentifier;
        $this->databaseSearchReplacer = $databaseSearchReplacer;
        $this->multipartRestore       = $multipartRestore;
    }

    public static function getTaskName(): string
    {
        return 'backup_restore_database';
    }

    public static function getTaskTitle(): string
    {
        return 'Restoring Database';
    }

    public function execute()
    {
        if ($this->jobDataDto->getIsMissingDatabaseFile()) {
            $partIndex = $this->jobDataDto->getDatabasePartIndex();
            $this->jobDataDto->setDatabasePartIndex($partIndex + 1);
            $this->logger->warning(sprintf('Skip restoring rest of database part: %d.', $partIndex));
            return $this->generateResponse(false);
        }

        try {
            $this->prepare();
        } catch (MissingFileException $e) {
            return $this->generateResponse(false);
        }

        $start            = microtime(true);
        $getCurrentBefore = $this->stepsDto->getCurrent();
        $getTotal         = $this->stepsDto->getTotal();

        if ($getTotal === 0) {
            $this->logger->critical('Total number of queries is 0. Stop restoring backup. Contact support@wp-staging.com.');
            throw new Exception('Total number of queries is 0. Stop restoring backup');
        }

        $this->setupExecutionTime();
        $this->databaseImporter->restore($this->jobDataDto->getTmpDatabasePrefix());

        $getCurrent = $this->stepsDto->getCurrent();

        if ($getCurrent > $getTotal) {
            $getCurrent = $getCurrent - ( $getCurrent - $getTotal );
        }

        $queriesPerSecond = ($getCurrent - $getCurrentBefore) / (microtime(true) - $start);
        $queriesPerSecond = (int)$queriesPerSecond;

        if ($queriesPerSecond > 0) {
            $queriesPerSecond = number_format_i18n($queriesPerSecond);
        }

        $queriesLog = sprintf('Executed %s/%s queries (%s queries per second)', number_format_i18n($getCurrent), number_format_i18n($getTotal), $queriesPerSecond);
        $this->logger->info($queriesLog);

        if ($queriesPerSecond === 0) {
            $this->maybeUpdateExecutionTime();
        } else {
            $this->jobDataDto->resetNumberOfQueryAttemptsWithZeroResult();
        }

        if ($this->stepsDto->isFinished() && $this->jobDataDto->getBackupMetadata()->getIsMultipartBackup()) {
            $this->jobDataDto->setDatabasePartIndex($this->jobDataDto->getDatabasePartIndex() + 1);
            $this->stepsDto->setCurrent(0);

            // To make sure finish condition work.
            $this->stepsDto->setTotal(0);
        }

        return $this->generateResponse(false);
    }

    /**
     * @see \WPStaging\Backup\Service\Database\Exporter\RowsExporter::setupBackupSearchReplace For Backup Search/Replace.
     */
    public function prepare()
    {
        $metadata = $this->jobDataDto->getBackupMetadata();
        if ($metadata->getIsMultipartBackup()) {
            $this->multipartRestore->prepareDatabaseRestore($this->jobDataDto, $this->logger, $this->databaseImporter, $this->stepsDto, $this->databaseSearchReplacer, $this->pathIdentifier->getBackupDirectory());
            return;
        }

        $databaseFile = $this->pathIdentifier->transformIdentifiableToPath($metadata->getDatabaseFile());
        $fileSize = filesize($databaseFile);

        if ($fileSize === false || $fileSize === 0) {
            throw new RuntimeException(sprintf('Could not get database file size for %s', $databaseFile));
        }

        if (!file_exists($databaseFile)) {
            throw new RuntimeException(sprintf('Can not find database file %s', $databaseFile));
        }

        $this->databaseImporter->setFile($databaseFile);
        $this->databaseImporter->seekLine($this->stepsDto->getCurrent());

        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setTotal($this->databaseImporter->getTotalLines());
        }

        $this->databaseImporter->setSearchReplace($this->databaseSearchReplacer->getSearchAndReplace($this->jobDataDto, get_site_url(), get_home_url()));
    }

    protected function setupExecutionTime()
    {
        static::$backupRestoreMaxExecutionTimeInSeconds           = $this->jobDataDto->getCurrentExecutionTimeDatabaseRestore();
        DatabaseImporter::$backupRestoreMaxExecutionTimeInSeconds = $this->jobDataDto->getCurrentExecutionTimeDatabaseRestore();
    }

    /**
     * @throws RuntimeException
     */
    protected function maybeUpdateExecutionTime()
    {
        $this->jobDataDto->incrementNumberOfQueryAttemptsWithZeroResult();
        if ($this->jobDataDto->getNumberOfQueryAttemptsWithZeroResult() < self::MAX_RETRIES) {
            return;
        }

        $this->jobDataDto->incrementCurrentExecutionTimeDatabaseRestore();
        $this->jobDataDto->resetNumberOfQueryAttemptsWithZeroResult();

        $currentExecutionTimeDatabaseRestore = $this->jobDataDto->getCurrentExecutionTimeDatabaseRestore();
        if ($currentExecutionTimeDatabaseRestore > self::MAX_EXECUTION_TIME_ALLOWED) {
            throw new RuntimeException(sprintf(esc_html__('Cannot increase execution time. Max allowed execution time of %s seconds exceeded.', 'wp-staging'), self::MAX_EXECUTION_TIME_ALLOWED));
        }

        $this->logger->warning(sprintf(esc_html__('Repeat database restore after increasing execution time to %s seconds', 'wp-staging'), $currentExecutionTimeDatabaseRestore));
    }
}
