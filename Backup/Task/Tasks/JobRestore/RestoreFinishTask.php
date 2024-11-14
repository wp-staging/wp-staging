<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use RuntimeException;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Dto\Task\Restore\Response\RestoreFinishResponseDto;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Framework\Notices\ObjectCacheNotice;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Traits\EventLoggerTrait;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

/**
 * @todo register analytics event and cleaning here
 */
class RestoreFinishTask extends RestoreTask
{
    use EventLoggerTrait;

    /** @var ObjectCacheNotice */
    protected $objectCacheNotice;

    /** @var SiteInfo */
    protected $siteInfo;

    public static function getTaskName()
    {
        return 'backup_restore_finish';
    }

    public static function getTaskTitle()
    {
        return 'Finishing Restore';
    }

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, ObjectCacheNotice $objectCacheNotice, SiteInfo $siteInfo)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->objectCacheNotice = $objectCacheNotice;
        $this->siteInfo          = $siteInfo;
    }

    public function execute()
    {
        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setTotal(1);
        }

        try {
            if ($this->jobDataDto->getObjectCacheSkipped()) {
                $this->objectCacheNotice->enable();
            }

            $this->logger->info("################## FINISH ##################");

            $this->logBackupRestoreCompleted($this->jobDataDto->getBackupMetadata());
            $this->clearCacheAndLogoutOnWpCom();
        } catch (RuntimeException $e) {
            $this->logger->critical($e->getMessage());

            return $this->generateResponse(false);
        }

        /** @var RestoreFinishResponseDto */
        $response = $this->generateResponse();
        $response->setIsDatabaseRestoreSkipped($this->jobDataDto->getIsDatabaseRestoreSkipped());

        return $response;
    }

    /**
     * Clear cache and logout when restoring on wpcom hosted sites and when restoring database
     * @return void
     */
    protected function clearCacheAndLogoutOnWpCom()
    {
        // Early bail: if not wp.com site or database was not restored
        if (!$this->siteInfo->isHostedOnWordPressCom() || !$this->jobDataDto->getBackupMetadata()->getIsExportingDatabase() || $this->jobDataDto->getIsDatabaseRestoreSkipped()) {
            return;
        }

        /**
         * @var \wpdb $wpdb
         * @var \WP_Object_Cache $wp_object_cache
         */
        global $wpdb, $wp_object_cache;

        // Reset cache
        wp_cache_init();

        // Make sure WordPress does not try to re-use any values fetched from the database thus far.
        $wpdb->flush();
        $wp_object_cache->flush();
        wp_suspend_cache_addition(true);

        wp_logout();
    }

    /**
     * @return RestoreFinishResponseDto
     */
    protected function getResponseDto(): RestoreFinishResponseDto
    {
        return new RestoreFinishResponseDto();
    }
}
