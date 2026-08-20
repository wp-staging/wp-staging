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
 
    protected $jobDataDto;

 
    private $sites;








    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Sites $sites)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->sites = $sites;
    }




    public static function getTaskName()
    {
        return 'staging_finish_delete';
    }




    public static function getTaskTitle()
    {
        return 'Finish Staging Site Delete';
    }




    public function execute()
    {
        $this->getJobTransientCache()->completeJob();
        $stagingSite  = $this->jobDataDto->getStagingSite();
        $stagingSites = $this->sites->tryGettingStagingSites();
        if (isset($stagingSites[$this->jobDataDto->getCloneId()])) {
            unset($stagingSites[$this->jobDataDto->getCloneId()]);
            $this->sites->updateStagingSites($stagingSites);
        }

        $this->logger->info(sprintf(
            'Staging Site "%s" deleted.',
            $stagingSite->getSiteName()
        ));

        return $this->generateResponse();
    }
}
