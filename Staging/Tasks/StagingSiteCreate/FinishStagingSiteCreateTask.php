<?php

namespace WPStaging\Staging\Tasks\StagingSiteCreate;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Staging\Dto\Job\StagingSiteCreateDataDto;
use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Sites;
use WPStaging\Staging\Tasks\StagingTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class FinishStagingSiteCreateTask extends StagingTask
{
    /** @var StagingSiteCreateDataDto */
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
        return 'staging_site_create_finish';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return 'Finishing Staging Site Creation';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute()
    {
        $stagingSites = $this->sites->tryGettingStagingSites();
        $stagingSite  = $this->buildStagingSite();
        $stagingSites[$this->jobDataDto->getCloneId()] = $stagingSite->toArray();
        $this->sites->updateStagingSites($stagingSites);
        $this->logger->info(sprintf(
            'Staging Site "%s" created.',
            $stagingSite->getSiteName()
        ));

        return $this->generateResponse();
    }

    protected function buildStagingSite(): StagingSiteDto
    {
        $stagingSite = new StagingSiteDto();
        $stagingSite->setCloneId($this->jobDataDto->getCloneId());
        $stagingSite->setPrefix($this->jobDataDto->getDatabasePrefix());
        $stagingSite->setStatus(StagingSiteDto::STATUS_FINISHED);
        $stagingSite->setDirectoryName($this->jobDataDto->getName());
        $stagingSite->setPath($this->jobDataDto->getStagingSitePath());
        $stagingSite->setUrl($this->jobDataDto->getStagingSiteUrl());
        $stagingSite->setDatetime(time());
        $stagingSite->setVersion(WPStaging::getVersion());
        $stagingSite->setOwnerId(get_current_user_id());

        return $stagingSite;
    }
}
