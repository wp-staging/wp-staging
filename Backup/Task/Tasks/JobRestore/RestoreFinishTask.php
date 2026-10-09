<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use RuntimeException;
use Throwable;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Dto\Task\Restore\Response\RestoreFinishResponseDto;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Framework\Logger\SseEventCache;
use WPStaging\Framework\Notices\ObjectCacheNotice;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\ThirdParty\LiteSpeedCache;
use WPStaging\Framework\Traits\EventLoggerTrait;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;




class RestoreFinishTask extends RestoreTask
{
    use EventLoggerTrait;

 
    protected $objectCacheNotice;

 
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
            $this->purgeLiteSpeedCache();

            if ($this->jobDataDto->getObjectCacheSkipped()) {
                $this->objectCacheNotice->enable();
            }

            $this->performRestoreFinishAction();
            $this->clearCacheOnWpCom();

 
            if ($this->jobDataDto->getBackupMetadata()->getIsExportingDatabase() && !$this->jobDataDto->getIsDatabaseRestoreSkipped()) {
                wp_logout();
            }
        } catch (RuntimeException $e) {
            $this->logger->critical($e->getMessage());

            return $this->generateResponse(false);
        }

 
        $response = $this->generateResponse();
        $response->setIsDatabaseRestoreSkipped($this->jobDataDto->getIsDatabaseRestoreSkipped());

        return $response;
    }






    protected function purgeLiteSpeedCache()
    {
        try {
            $statusCode = WPStaging::make(LiteSpeedCache::class)->purgeAfterRestore();
        } catch (Throwable $e) {
            $this->logger->warning('The LiteSpeed page cache could not be purged: ' . $e->getMessage());
            return;
        }

        if ($statusCode === null) {
            return;
        }

        if ($statusCode === 204) {
            $this->logger->info('LiteSpeed page cache purged.');
            return;
        }

        $this->logger->warning(sprintf(
            'The LiteSpeed page cache could not be purged: the purge request %s. Visitors may see the old site until it expires or is purged by hand.',
            $statusCode === 0 ? 'got no response' : 'was answered with HTTP ' . $statusCode
        ));
    }





    protected function clearCacheOnWpCom()
    {
 
        if (!$this->siteInfo->isHostedOnWordPressCom() || !$this->jobDataDto->getBackupMetadata()->getIsExportingDatabase() || $this->jobDataDto->getIsDatabaseRestoreSkipped()) {
            return;
        }





        global $wpdb, $wp_object_cache;

 
        wp_cache_init();

 
        $wpdb->flush();
        $wp_object_cache->flush();
        wp_suspend_cache_addition(true);
    }




    protected function getResponseDto(): RestoreFinishResponseDto
    {
        return new RestoreFinishResponseDto();
    }




    protected function performRestoreFinishAction()
    {
        $this->getJobTransientCache()->completeJob();
        $this->logger->pushSseEvent(SseEventCache::EVENT_TYPE_COMPLETE, [
            'status' => 'success',
            'data'   => [
                'message' => __('Restore completed successfully.', 'wp-staging'),
                'type'    => 'restore',
            ],
        ]);
        $this->logger->info("✓ Backup successfully restored");
        $this->logBackupRestoreCompleted($this->jobDataDto->getBackupMetadata());
    }
}
