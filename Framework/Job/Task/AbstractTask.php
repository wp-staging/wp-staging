<?php








namespace WPStaging\Framework\Job\Task;

use Exception;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Exceptions\WPStagingException;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Dto\AbstractTaskDto;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Logger\SseEventCache;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

abstract class AbstractTask
{
    use ResourceTrait;

 
    const FILTER_TASK_RESPONSE = 'wpstg.task.response';

 
    const FILTER_REMOTE_STORAGES_CHUNK_SIZE = 'wpstg.remoteStorages.chunkSize';

 
    const FILTER_REMOTE_STORAGES_DELAY_BETWEEN_REQUESTS = 'wpstg.remoteStorages.delayBetweenRequests';

 
    const FILTER_CHUNK_DOWNLOAD_CLOUD_FILE_TO_FOLDER_CHUNK_SIZE = 'wpstg.chunkDownloadCloudFileToFolder.chunkSize';





    const ACTION_TASK_RESPONSE = 'wpstg_task_response';






    const MAX_WAIT_TASK_THRESHOLD_SECONDS = 15;

 
    protected $logger;

 
    protected $cache;

 
    protected $prepared;

 
 
    protected $jobName;

 
    protected $jobId;

 
    protected $debug;

 
    protected $stepsDto;

 
    protected $jobDataDto;

 
    protected $job;

 
    protected $currentTaskDto;

 
    protected $taskQueue;

 
    protected $isWaitTask = false;

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue)
    {
 
        $this->logger    = $logger; // @phpstan-ignore-line
        $this->cache     = $cache;
        $this->stepsDto  = $stepsDto;
        $this->taskQueue = $taskQueue;

        $this->init();
    }




    abstract public function execute();





    public static function getTaskName()
    {
        throw new WPStagingException('Any extending class MUST override the getTaskName method.');
    }





    public static function getTaskTitle()
    {
        throw new WPStagingException('Any extending class MUST override the getTaskTitle method.');
    }





    public function setJobContext(AbstractJob $job)
    {
        $this->cache->setLifetime(HOUR_IN_SECONDS);
        $this->cache->setFilename('task_steps_' . static::getTaskName());

        $stepsData = $this->cache->get([
            'current' => 0,
            'total'   => 0,
        ]);

        if (empty($stepsData)) {
            $stepsData = [
                'current' => 0,
                'total'   => 0,
            ];
        }

        $this->stepsDto->hydrate($stepsData);
        $this->job = $job;
    }





    public function setJobDataDto(JobDataDto $jobDataDto)
    {
        $this->jobDataDto = $jobDataDto;
        $this->taskQueue->setup(static::getTaskName());
        $this->taskQueue->seek($this->jobDataDto->getQueueOffset());
        $this->setupCurrentTaskDto();
    }








    public function generateResponse($incrementStep = true): TaskResponseDto
    {
        if ($incrementStep) {
            $this->stepsDto->incrementCurrentStep();
        }

 
        $response = $this->getResponseDto();
        $response->setIsRunning(!$this->stepsDto->isFinished());
        $response->setPercentage($this->stepsDto->getPercentage());
        $response->setTotal($this->stepsDto->getTotal());
        $response->setStep($this->stepsDto->getCurrent());
        $response->setTask($this->getTaskName());
        $response->setStatusTitle(static::getTaskTitle());
        $response->setJobId($this->jobDataDto->getId());

        if (!$this->isWaitTask) {
            $this->updateTaskProgress($response);
        }

        $this->addLogMessageToResponse($response);

        $this->logger->setFileName(sprintf(
            '%s__%s__%s',
            $this->getJobName(),
            date('Y_m_d__H'),
            $this->getJobId()
        ));

        if ($this->stepsDto->isFinished()) {
            $this->taskQueue->seek(0);
            $this->jobDataDto->setQueueOffset(0);
            $response->setPercentage(0);
            $this->jobDataDto->setCurrentTaskData([]);
            $this->cache->delete();
            $this->jobDataDto->setCurrentTaskData([]);
        } else {
            $this->persistStepsDto();
        }

        $this->job->getTransientCache()->update();
        Hooks::callInternalHook(self::ACTION_TASK_RESPONSE, [
            'jobDataDto'        => $this->jobDataDto,
            'jobTransientCache' => $this->job->getTransientCache(),
            'isWaitTask'        => $this->isWaitTask,
        ]);

        $response = Hooks::applyFilters(self::FILTER_TASK_RESPONSE, $response);

        return $response;
    }







    public function persistStepsDto()
    {
        $this->cache->save($this->stepsDto->toArray(), true);
    }




    public function getJobName()
    {
        return $this->jobName;
    }




    public function setJobName($jobName)
    {
        $this->jobName = $jobName;
    }




    public function getJobId()
    {
        return $this->jobId;
    }




    public function setJobId($jobId)
    {
        $this->jobId = $jobId;
    }




    public function setDebug($debug)
    {
        $this->debug = (bool)$debug;
    }




    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function setupLogger()
    {
        if ($this->logger instanceof Logger) {
            $this->logger->setupSseLogger((string)$this->jobId);
        }
    }




    public function getQueue(): SeekableQueueInterface
    {
        return $this->taskQueue;
    }





    public function setCurrentTaskDto(AbstractTaskDto $taskDto)
    {
        $this->currentTaskDto = $taskDto;
        $this->jobDataDto->setCurrentTaskData($taskDto->toArray());
    }

    public function commitLogs()
    {
        if ($this->logger instanceof Logger) {
            $this->logger->commit();
        }
    }

    public function getJobTransientCache(): JobTransientCache
    {
        return $this->job->getTransientCache();
    }




    public function persistJobDataDto()
    {
        $this->job->persistJobDataDto();
    }




    protected function getResponseDto()
    {
        return new TaskResponseDto();
    }

    protected function updateTaskProgress(TaskResponseDto $response)
    {
        if ($this->logger instanceof Logger) {
            $this->logger->pushSseEvent(SseEventCache::EVENT_TYPE_TASK, [
                'title'      => $response->getStatusTitle(),
                'percentage' => $response->getPercentage(),
            ]);
        }
    }

 
    protected function getCurrentTaskType(): string
    {
        return '';
    }




    protected function setupCurrentTaskDto()
    {
        $currentTaskType = $this->getCurrentTaskType();
        if (empty($currentTaskType) || !class_exists($currentTaskType)) {
            return;
        }

        try {
            $currentTaskData      = $this->jobDataDto->getCurrentTaskData();
            $this->currentTaskDto = WPStaging::make($currentTaskType);
            $this->currentTaskDto->hydrateProperties($currentTaskData);
        } catch (Exception $e) {
        }
    }

    protected function init()
    {
 
    }

    protected function addLogMessageToResponse(TaskResponseDto $response)
    {
        $response->addMessage($this->logger->getLastLogMsg());
    }




    protected function updateJob()
    {
        $this->job->setJobDataDto($this->jobDataDto);
        $this->job->updateTasks();
    }
}
