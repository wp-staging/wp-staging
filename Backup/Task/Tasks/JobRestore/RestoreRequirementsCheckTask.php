<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use RuntimeException;
use WPStaging\Backup\BackupHeader;
use WPStaging\Backup\Service\ZlibCompressor;
use WPStaging\Framework\Analytics\Actions\AnalyticsBackupRestore;
use WPStaging\Framework\Database\TableDto;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Job\Exception\ThresholdException;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class RestoreRequirementsCheckTask extends RestoreTask
{
    /** @var TableService */
    protected $tableService;

    /** @var JobRestoreDataDto $jobDataDto */
    protected $jobDataDto;

    /** @var DiskWriteCheck */
    protected $diskWriteCheck;

    /** @var string A WPSTAGING backup with a version lower than this one is a beta release for pro. */
    const BETA_VERSION_LIMIT_PRO = '4';

    /** @var AnalyticsBackupRestore */
    protected $analyticsBackupRestore;

    /**
     * @var SiteInfo
     */
    protected $siteInfo;

    /** @var ZlibCompressor */
    protected $zlibCompressor;

    public function __construct(
        TableService $tableService,
        JobDataDto $jobDataDto,
        LoggerInterface $logger,
        Cache $cache,
        StepsDto $stepsDto,
        SeekableQueueInterface $taskQueue,
        DiskWriteCheck $diskWriteCheck,
        AnalyticsBackupRestore $analyticsBackupRestore,
        SiteInfo $siteInfo,
        ZlibCompressor $zlibCompressor
    ) {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->tableService           = $tableService;
        // @phpstan-ignore-next-line
        $this->jobDataDto             = $jobDataDto;
        $this->diskWriteCheck         = $diskWriteCheck;
        $this->analyticsBackupRestore = $analyticsBackupRestore;
        $this->siteInfo               = $siteInfo;
        $this->zlibCompressor         = $zlibCompressor;
    }

    public static function getTaskName()
    {
        return 'backup_restore_requirement_check';
    }

    public static function getTaskTitle()
    {
        return 'Restore Requirements Check';
    }

    public function execute()
    {
        if (!$this->stepsDto->getTotal()) {
            // The only requirement checking that really needs a step is the free disk space one, all other happens instantly.
            $this->stepsDto->setTotal(1);
        }

        try {
            // Warnings
            $this->shouldWarnIfRestoringBackupWithShortOpenTags();
            $this->shouldWarnIfRunning32Bits();
            $this->shouldWarnIfTheresNotEnoughFreeDiskSpace();

            // Errors
            $this->cannotRestoreOnMultisite();
            $this->cannotMigrate();
            $this->cannotRestoreMultipartBackup();
            $this->cannotRestoreIfCantWriteToDisk();
            $this->cannotRestoreMultisiteBackupOnSingleSite();
            $this->cannotHaveConflictingPrefix();
            $this->cannotHaveTableThatWillExceedLength();
            $this->cannotRestoreIfThereIsNotEnoughFreeDiskSpaceForTheDatabase();
            $this->cannotRestoreIfBackupGeneratedOnProVersion();
            $this->cannotRestoreIfBackupGeneratedOnNewerBackupVersion();
            $this->cannotRestoreIfBackupGeneratedOnNewerWPDbVersion();
            $this->cannotRestoreIfAnyTemporaryPrefixIsCurrentSitePrefix();
            $this->cannotRestoreBackupCreatedBeforeMVP();
            $this->cannotRestoreIfInvalidSiteOrHomeUrl();
            $this->cannotRestoreCompressedBackup();
        } catch (ThresholdException $e) {
            $this->logger->info($e->getMessage());

            return $this->generateResponse(false);
        } catch (RuntimeException $e) {
            $this->logger->critical($e->getMessage());

            $this->jobDataDto->setRequirementFailReason($e->getMessage());
            // todo: Set the requirement fail reason
            $this->analyticsBackupRestore->enqueueFinishEvent($this->jobDataDto->getId(), $this->jobDataDto);

            return $this->generateResponse(false);
        }

        $this->analyticsBackupRestore->enqueueStartEvent($this->jobDataDto->getId(), $this->jobDataDto);
        $this->logger->info(__('Backup Requirements check passed...', 'wp-staging'));

        return $this->generateResponse();
    }

    protected function shouldWarnIfRestoringBackupWithShortOpenTags()
    {
        $shortTagsEnabledInBackupBeingRestored = $this->jobDataDto->getBackupMetadata()->getPhpShortOpenTags();

        if ($shortTagsEnabledInBackupBeingRestored) {
            $shortTagsEnabledInThisSite = $this->siteInfo->isPhpShortTagsEnabled();

            if (!$shortTagsEnabledInThisSite) {
                $this->logger->warning(__('This backup was generated on a server with PHP ini directive "short_open_tags" enabled, which is disabled in this server. This might cause errors after Restore.', 'wp-staging'));
            }
        }
    }

    protected function cannotRestoreIfCantWriteToDisk()
    {
        try {
            $this->diskWriteCheck->testDiskIsWriteable();
        } catch (DiskNotWritableException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    protected function shouldWarnIfRunning32Bits()
    {
        if (PHP_INT_SIZE === 4) {
            $this->logger->warning(__('You are running a 32-bit version of PHP. 32-bits PHP can\'t handle backups larger than 2GB. You might face a critical error. Consider upgrading to 64-bit.', 'wp-staging'));
        }
    }

    protected function shouldWarnIfTheresNotEnoughFreeDiskSpace()
    {
        $fileBeingRestored = $this->jobDataDto->getFile();

        try {
            $file = new FileObject($fileBeingRestored, 'r');
        } catch (\Exception $e) {
            $this->logger->warning(__('Could not open the backup file for requirement checking.', 'wp-staging'));
            return;
        }

        try {
            $this->diskWriteCheck->checkPathCanStoreEnoughBytes(WP_CONTENT_DIR, $file->getSize());
        } catch (DiskNotWritableException $e) {
            $this->logger->warning($e->getMessage());
            return;
        } catch (RuntimeException $e) {
            // soft error, no action needed, but log
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * @throws RuntimeException When trying to restore a .wpstg file generated from a multi-site
     *                          installation into a single-site.
     */
    protected function cannotRestoreMultisiteBackupOnSingleSite()
    {
        $backupType = $this->jobDataDto->getBackupMetadata()->getBackupType();

        if ($backupType === BackupMetadata::BACKUP_TYPE_MULTISITE && !is_multisite()) {
            throw new RuntimeException('This is a full multisite backup, but this site is a single-site WordPress installation, so the recovery program cannot proceed.');
        }
    }

    protected function cannotHaveConflictingPrefix()
    {
        global $wpdb;

        $basePrefix = $wpdb->base_prefix;

        if (($basePrefix === $this->jobDataDto->getTmpDatabasePrefix() || $basePrefix === DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP)) {
            throw new RuntimeException("Can not proceed. The production site database table prefix uses \"$basePrefix\" which is used for temporary tables by WP STAGING. Please, feel free to reach out to WP STAGING support for assistance.");
        }
    }

    protected function cannotHaveTableThatWillExceedLength()
    {
        global $wpdb;

        $prefix = $wpdb->base_prefix;

        $tables = $this->tableService->findTableStatusStartsWith($prefix);

        if (empty($tables)) {
            // This should never happen, as we are running this in the context of a WordPress plugin.
            throw new RuntimeException("We could not find any tables with the prefix \"$prefix\". The backup restore cannot start. Please, feel free to reach out to WP STAGING support for assistance.");
        }

        $maxLengthOfTableBeingRestored = $this->jobDataDto->getBackupMetadata()->getMaxTableLength();

        if ($maxLengthOfTableBeingRestored + strlen($prefix) > 64) {
            throw new RuntimeException("MySQL has a limit of 64 characters for table names. One of the tables in the backup, combined with the base prefix of your WordPress installation ('$prefix'), would exceed this limit, which is why the backup restore cannot start. Please contact WP STAGING support for assistance.");
        }

        $this->jobDataDto->setShortNamesTablesToDrop();
        $this->jobDataDto->setShortNamesTablesToRestore();

        $requireShortNamesForTablesToDrop = false;
        /** @var TableDto $table */
        foreach ($tables as $table) {
            if (!$table instanceof TableDto) {
                throw new RuntimeException("We could not read information from tables to determine whether the backup restore is able to run or not, therefore the backup restore cannot start. Please, feel free to reach out to WP STAGING support for assistance.");
            }

            $unprefixedName = substr($table->getName(), strpos($table->getName(), $prefix));

            if (strlen($unprefixedName) + strlen(DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP) > 64) {
                $requireShortNamesForTablesToDrop = true;
                $shortName = uniqid(DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP) . str_pad(rand(0, 999999), 6, '0');
                $this->jobDataDto->addShortNameTableToDrop($table->getName(), $shortName);
                $this->logger->warning("MySQL has a limit of 64 characters for table names. One of your tables, combined with the temporary prefix used by the backup restore, would exceed this limit, therefore the backup will be restored with a shorter name and change it back to original name if restoration fails otherwise drop it along with other backups table. The table with the extra-long name is: \"{$table->getName()}\". It will be backup with the name: \"{$shortName}\", So in case anything goes wrong you can restore it back.");
            }
        }

        $this->jobDataDto->setRequireShortNamesForTablesToDrop($requireShortNamesForTablesToDrop);

        if ($maxLengthOfTableBeingRestored + strlen($this->jobDataDto->getTmpDatabasePrefix()) > 64) {
            $this->logger->warning("MySQL has a limit of 64 characters for table names. One of the tables in the backup would exceed this limit in combination with the temporary prefix used by the backup, so the table is restored with a shorter name and changed back to the original name after a successful restore.");
            $this->jobDataDto->setRequireShortNamesForTablesToRestore(true);
        }
    }

    /**
     * When restoring a backup, we detect and can recover from disk fulls while
     * extracting the .wpstg file to a temporary directory. However, depending
     * on the size of the database in this backup, we might hit disk limits
     * while inserting data into MySQL.
     *
     * We cannot prevent every possible issue, but we can try to catch some.
     *
     * This method tries to write a file the same size as the database being
     * restored to the filesystem. If there is not enough disk space for
     * this operation, there will hardly be enough disk space to restore the
     * database.
     *
     * @throws ThresholdException
     */
    protected function cannotRestoreIfThereIsNotEnoughFreeDiskSpaceForTheDatabase()
    {
        $databaseFileSize = $this->jobDataDto->getBackupMetadata()->getDatabaseFileSize();

        // Early bail: No database in this backup
        if (empty($databaseFileSize)) {
            $this->stepsDto->incrementCurrentStep();

            return;
        }

        /**
         * We estimate we need 110% of the original backup file of free disk space for the restore process.
         *
         * wp-content/uploads/wp-staging/tmp/restore/wp-content/* (extracted files)
         * Tmp database (in MySQL)
         */
        $estimatedSizeNeeded = (int)($databaseFileSize * 1.1);

        $tmpFile = __DIR__ . '/diskCheck.wpstg';

        if (!file_exists($tmpFile) && !touch($tmpFile)) {
            throw new RuntimeException(sprintf('The backup restore could not write to the temporary file %s.', esc_html($tmpFile)));
        }

        $fileObject = new FileObject($tmpFile, 'a');

        $writtenBytes = $this->jobDataDto->getExtractorFileWrittenBytes();
        $timesWritten = 0;
        $fiveMb       = str_repeat('a', 5 * MB_IN_BYTES);

        while ($writtenBytes < $estimatedSizeNeeded) {
            $writtenNow = $fileObject->fwrite($fiveMb);

            if ($writtenNow === 0) {
                unlink($fileObject->getPathname());
                throw new RuntimeException(sprintf('It seems there is not enough free disk space to restore this backup. The backup restore needs %s of free disk space to proceed, therefore the restore will not continue.', esc_html(size_format($estimatedSizeNeeded))));
            } else {
                $writtenBytes += $writtenNow;
            }

            // Only check threshold every now and then
            if ($timesWritten++ >= 5) {
                if ($this->isThreshold()) {
                    $this->jobDataDto->setExtractorFileWrittenBytes($fileObject->getSize());
                    $percentage = (int)(($writtenBytes / $estimatedSizeNeeded) * 100);
                    throw ThresholdException::thresholdHit(sprintf('Checking if there is enough free disk space to restore... (%d%%)', esc_html($percentage)));
                }

                $timesWritten = 0;
            }
        }

        unlink($fileObject->getPathname());
        $this->jobDataDto->setExtractorFileWrittenBytes(0);
        $this->stepsDto->incrementCurrentStep();
    }

    /**
     * Disallows backups generated in newer versions of WP STAGING to be restored
     * using older versions of WP STAGING.
     */
    protected function cannotRestoreIfBackupGeneratedOnNewerBackupVersion()
    {
        $backupVersion = $this->jobDataDto->getBackupMetadata()->getBackupVersion();
        // No backup version mean older backups, generated before this feature was implemented.
        // So allow it to restore.
        if (empty($backupVersion)) {
            return;
        }

        if (version_compare($backupVersion, $this->getCurrentBackupVersion(), '<=')) {
            return;
        }

        if ($this->isDevVersion()) {
            $this->logger->warning(sprintf("Backup generated on newer Backup version: %s. Allowed to continue due to WPSTG_IS_DEV...", esc_html($backupVersion)));
            return;
        }

        throw new RuntimeException(sprintf('This backup was created with a newer version of WP Staging! Please update the WP Staging plugin first! Then start the restoration of the backup again. - Backup Format Version: %s.', esc_html($backupVersion)));
    }

    /**
     * Disallows backups generated in the PRO version to be restored using the free version.
     */
    protected function cannotRestoreIfBackupGeneratedOnProVersion()
    {
        $metadata = $this->jobDataDto->getBackupMetadata();

        // Early bail: free version
        if (!$metadata->getCreatedOnPro()) {
            return;
        }

        throw new RuntimeException('This backup was generated on WP STAGING PRO and cannot be restored on FREE version. Please upgrade to <a href="https://wp-staging.com" target="_blank">WP STAGING PRO</a> to restore this Backup.');
    }

    /*
     * Disallow backups that contains database generated in newer versions of WordPress to be restored
     * in older versions of WordPress that has a different database schema.
     */
    protected function cannotRestoreIfBackupGeneratedOnNewerWPDbVersion()
    {
        if (!$this->jobDataDto->getBackupMetadata()->getIsExportingDatabase()) {
            return;
        }

        /**
         * @var string $GLOBALS['wp_version']
         * @var int    $GLOBALS['wp_db_version']
         */
        include ABSPATH . WPINC . '/version.php';

        // use $GLOBALS to make phpstan happy
        // This should never happen
        if (!isset($GLOBALS['wp_version']) || !isset($GLOBALS['wp_db_version'])) {
            $this->logger->warning('Could not determine the WP DB Schema Version in the Backup. No action is necessary, the backup will proceed...');

            return;
        }

        if (version_compare((int)$this->jobDataDto->getBackupMetadata()->getWpDbVersion(), (int)$GLOBALS['wp_db_version'], '>')) {
            $this->logger->debug(sprintf(
                'The backup is using an incompatible database schema version, generated in a newer version of WordPress. Schema version in the backup: %s. Current WordPress Schema version: %s',
                $this->jobDataDto->getBackupMetadata()->getWpDbVersion(),
                $GLOBALS['wp_db_version']
            ));

            throw new RuntimeException(sprintf(
                'Please update WordPress to continue restoring this backup! This backup contains a database generated on WordPress %s. You are running WordPress %s, which has an incompatible database schema version.',
                $this->jobDataDto->getBackupMetadata()->getWpVersion(),
                $GLOBALS['wp_version']
            ));
        }
    }

    /*
     * Disallow backups generated in the MVP to be restored using the newer version of WP STAGING.
     */
    protected function cannotRestoreBackupCreatedBeforeMVP()
    {
        if ($this->isDevVersion()) {
            return;
        }

        $metadata = $this->jobDataDto->getBackupMetadata();

        // Early bail: free version
        if (!$metadata->getCreatedOnPro()) {
            return;
        }

        if (version_compare($metadata->getWpstgVersion(), self::BETA_VERSION_LIMIT_PRO, '<')) {
            throw new RuntimeException('This backup was generated on a beta version of WP STAGING. Create a new Backup using the latest version of WP STAGING. Please feel free to get in touch with our support if you need assistance.');
        }
    }

    protected function cannotRestoreIfAnyTemporaryPrefixIsCurrentSitePrefix()
    {
        // Early bail if not restoring database
        if (!$this->jobDataDto->getBackupMetadata()->getIsExportingDatabase()) {
            return;
        }

        global $wpdb;

        $prefix = $wpdb->base_prefix;

        // Should not happen but if it does, add a bail for such cases
        if (DatabaseImporter::TMP_DATABASE_PREFIX === $prefix || DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP === $prefix) {
            throw new RuntimeException(sprintf('Restore stopped! Your current site prefix is %s. This is a temporary prefix used by WP Staging during restore. Please contact support to get help restoring the backup.', $prefix));
        }
    }

    protected function cannotRestoreIfInvalidSiteOrHomeUrl()
    {
        if (!parse_url($this->jobDataDto->getBackupMetadata()->getSiteUrl(), PHP_URL_HOST)) {
            throw new RuntimeException('This backup contains an invalid Site URL. Please contact support to get help restoring the backup.');
        }

        if (!parse_url($this->jobDataDto->getBackupMetadata()->getHomeUrl(), PHP_URL_HOST)) {
            throw new RuntimeException('This backup contains an invalid Home URL. Please contact support to get help restoring the backup.');
        }
    }

    protected function cannotRestoreOnMultisite()
    {
        if (is_multisite()) {
            throw new RuntimeException('Cannot restore! Free Version doesn\'t support restore of multisite backups. <a href="https://wp-staging.com" target="_blank">Get WP Staging Pro</a> to restore this backup on this website.');
        }
    }

    protected function cannotMigrate()
    {
        if (!$this->jobDataDto->getIsUrlSchemeMatched()) {
            throw new RuntimeException(sprintf("Cannot Restore this backup! This backup has different URL scheme (%s) than your current site scheme (%s). <a href='https://wp-staging.com' target='_blank'>Get WP Staging Pro</a> to restore this backup on this website.", esc_html($this->getUrlScheme($this->jobDataDto->getBackupMetadata()->getSiteUrl())), esc_html($this->getUrlScheme(site_url()))));
        }

        if (!$this->jobDataDto->getIsSameSiteBackupRestore()) {
            throw new RuntimeException(sprintf('Cannot restore this backup! Free Version doesn\'t support site migration and can only restore backups created on the same domain, host and server. This backup has been created on %s and you are trying to restore the backup on %s. <a href="https://wp-staging.com" target="_blank">Get WP Staging Pro</a> to restore this backup on this website.', esc_url($this->jobDataDto->getBackupMetadata()->getSiteUrl()), esc_url(site_url())));
        }
    }

    protected function cannotRestoreMultipartBackup()
    {
        if ($this->jobDataDto->getBackupMetadata()->getIsMultipartBackup()) {
            throw new RuntimeException('Cannot restore! Free Version doesn\'t support restore of multipart backups. <a href="https://wp-staging.com" target="_blank">Get WP Staging Pro</a> to restore this multipart backup on this website.');
        }
    }

    protected function cannotRestoreCompressedBackup()
    {
        if ($this->jobDataDto->getBackupMetadata()->getIsZlibCompressed()) {
            if (!$this->zlibCompressor->supportsCompression()) {
                // todo: add link to compression support article.
                throw new RuntimeException('Cannot restore! This backup is compressed, but your server does not support compression. Click <a href="https://wp-staging.com/how-to-install-and-activate-gzcompress-and-gzuncompress-functions-in-php/" target="_blank">here</a> to learn how to fix it.');
            } elseif ($this->zlibCompressor->supportsCompression() && !$this->zlibCompressor->canUseCompression()) {
                throw new RuntimeException('Cannot restore! This backup is compressed, you need WP Staging Pro to Restore it. Click <a href="https://wp-staging.com?utm_source=wpstg-license-ui&utm_medium=website&utm_campaign=compressed-backup-restore&utm_id=purchase-key&utm_content=wpstaging" target="_blank">Get WP Staging Pro</a> to restore this backup on this website.');
            }
        }
    }

    /**
     * @return bool
     */
    protected function isDevVersion()
    {
        return defined('WPSTG_IS_DEV') && WPSTG_IS_DEV;
    }

    /**
     * @return string
     */
    protected function getCurrentBackupVersion()
    {
        return BackupHeader::BACKUP_VERSION;
    }

    /**
     * @param string $tableName
     * @return string
     */
    protected function getUrlScheme(string $url): string
    {
        return parse_url($url, PHP_URL_SCHEME);
    }

    /**
     * This method make sure that there should be something to restore from the backup
     * Otherwise stop the restore process
     * @throws RuntimeException
     */
    protected function checkNothingToRestore()
    {
        /** @var BackupMetadata */
        $backupMetadata = $this->jobDataDto->getBackupMetadata();
        if ($backupMetadata->getIsExportingDatabase() && !$this->isBackupPartSkipped(PartIdentifier::DATABASE_PART_IDENTIFIER)) {
            return;
        }

        if ($backupMetadata->getIsExportingMuPlugins() && !$this->isBackupPartSkipped(PartIdentifier::MU_PLUGIN_PART_IDENTIFIER)) {
            return;
        }

        if ($backupMetadata->getIsExportingPlugins() && !$this->isBackupPartSkipped(PartIdentifier::PLUGIN_PART_IDENTIFIER)) {
            return;
        }

        if ($backupMetadata->getIsExportingThemes() && !$this->isBackupPartSkipped(PartIdentifier::THEME_PART_IDENTIFIER)) {
            return;
        }

        if ($backupMetadata->getIsExportingUploads() && !$this->isBackupPartSkipped(PartIdentifier::UPLOAD_PART_IDENTIFIER)) {
            return;
        }

        if ($backupMetadata->getIsExportingOtherWpContentFiles() && !$this->isBackupPartSkipped(PartIdentifier::WP_CONTENT_PART_IDENTIFIER)) {
            return;
        }

        if ($backupMetadata->getIsExportingOtherWpRootFiles() && !$this->isBackupPartSkipped(PartIdentifier::WP_ROOT_PART_IDENTIFIER)) {
            return;
        }

        throw new RuntimeException(esc_html(sprintf('Nothing to restore from the backup. The following backup parts are excluded from restore by the filter `%s`: %s.', RestoreTask::FILTER_EXCLUDE_BACKUP_PARTS, implode(', ', Hooks::applyFilters(RestoreTask::FILTER_EXCLUDE_BACKUP_PARTS, [])))));
    }
}
