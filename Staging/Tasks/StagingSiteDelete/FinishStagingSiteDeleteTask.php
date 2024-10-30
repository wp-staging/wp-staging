<?php

namespace WPStaging\Staging\Tasks\StagingSiteDelete;

use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Staging\Dto\Job\StagingSiteDeleteDataDto;
use WPStaging\Staging\Sites;
use WPStaging\Staging\Tasks\StagingTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class FinishStagingSiteDeleteTask extends StagingTask
{
    /** @var StagingSiteDeleteDataDto */
    protected $jobDataDto;

    /** @var Sites */
    private $sites;

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param SeekableQueueInterface $taskQueue
     * @param Sites $sites
     */
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Sites $sites)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->sites = $sites;
    }

    /**
     * @return string
     */
    public static function getTaskName()
    {
        return 'staging_finish_delete';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return 'Finish Staging Site Delete';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute()
    {
        $stagingSite  = $this->jobDataDto->getStagingSite();
        $stagingSites = $this->sites->tryGettingStagingSites();
        unset($stagingSites[$this->jobDataDto->getCloneId()]);
        $this->sites->updateStagingSites($stagingSites);
        $this->logger->info(sprintf(
            'Staging Site "%s" deleted.',
            $stagingSite->getSiteName()
        ));

        return $this->generateResponse();
    }
}
