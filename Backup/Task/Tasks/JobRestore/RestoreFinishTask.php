<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use RuntimeException;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Framework\Notices\ObjectCacheNotice;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

/**
 * @todo register analytics event and cleaning here
 */
class RestoreFinishTask extends RestoreTask
{
    /** @var ObjectCacheNotice */
    protected $objectCacheNotice;

    public static function getTaskName()
    {
        return 'backup_restore_finish';
    }

    public static function getTaskTitle()
    {
        return 'Finishing Restore';
    }

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, ObjectCacheNotice $objectCacheNotice)
    {
        $this->objectCacheNotice = $objectCacheNotice;
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
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
        } catch (RuntimeException $e) {
            $this->logger->critical($e->getMessage());

            return $this->generateResponse(false);
        }

        return $this->generateResponse();
    }
}
