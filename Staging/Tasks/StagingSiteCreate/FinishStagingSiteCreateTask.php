<?php

namespace WPStaging\Staging\Tasks\StagingSiteCreate;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Staging\Dto\Job\StagingSiteJobsDataDto;
use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Dto\Task\Response\FinishStagingSiteResponseDto;
use WPStaging\Staging\Jobs\StagingSiteCreate;
use WPStaging\Staging\Sites;
use WPStaging\Staging\Tasks\StagingTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

use function WPStaging\functions\debug_log;

class FinishStagingSiteCreateTask extends StagingTask
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
        return 'staging_site_create_finish';
    }




    public static function getTaskTitle()
    {
        return 'Finishing Staging Site Creation';
    }




    public function execute()
    {
        $this->getJobTransientCache()->completeJob();
        $stagingSites = $this->sites->tryGettingStagingSites();
        $stagingSite  = $this->buildStagingSite();
        $stagingSites[$this->jobDataDto->getCloneId()] = $stagingSite->toArray();
        $this->sites->updateStagingSites($stagingSites);
        $this->logger->info(sprintf(
            'Staging Site "%s" created.',
            $stagingSite->getSiteName()
        ));

        $this->triggerOnStagingSiteCreatedEvent($stagingSite);

        return $this->overrideGenerateResponse();
    }

    protected function buildStagingSite(): StagingSiteDto
    {
        $stagingSite = $this->jobDataDto->getStagingSite();
        $stagingSite->setStatus(StagingSiteDto::STATUS_FINISHED);
        $stagingSite->setDatetime(time());
        $stagingSite->setVersion(WPStaging::getVersion());
        $stagingSite->setOwnerId(get_current_user_id());

        return $stagingSite;
    }

    protected function triggerOnStagingSiteCreatedEvent(StagingSiteDto $stagingSite)
    {
        Hooks::doAction(StagingSiteCreate::ACTION_CLONING_COMPLETE, $stagingSite->toArray());
        Hooks::doAction(StagingSiteCreate::ACTION_STAGING_SITE_CREATED, $stagingSite->toArray());
    }

    protected function getResponseDto()
    {
        return new FinishStagingSiteResponseDto();
    }




    private function overrideGenerateResponse()
    {
        add_filter(self::FILTER_TASK_RESPONSE, function ($response) {
            if ($response instanceof FinishStagingSiteResponseDto) {
                $response->setCloneId($this->jobDataDto->getCloneId());
                $response->setStagingSiteUrl($this->jobDataDto->getStagingSiteUrl());
            } else {
                debug_log('Fail to finalize response for staging site creation process! Response content: ' . print_r($response, true));
            }

            return $response;
        });

        return $this->generateResponse();
    }
}
