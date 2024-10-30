<?php

namespace WPStaging\Framework\Job\Task;

use Exception;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Exceptions\WPStagingException;
use WPStaging\Framework\Job\Dto\AbstractTaskDto;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

abstract class AbstractTask
{
    use ResourceTrait;

    /** @var Logger */
    protected $logger;

    /** @var Cache */
    protected $cache;

    /** @var bool */
    protected $prepared;

    // TODO RPoC
    /** @var string|null */
    protected $jobName;

    /** @var int|null */
    protected $jobId;

    /** @var bool */
    protected $debug;

    /** @var StepsDto */
    protected $stepsDto;

    /** @var JobDataDto */
    protected $jobDataDto;

    /** @var AbstractJob */
    protected $job;

    /** @var AbstractTaskDto */
    protected $currentTaskDto;

    /** @var SeekableQueueInterface */
    protected $taskQueue;

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue)
    {
        /** @var Logger logger */
        $this->logger    = $logger; // @phpstan-ignore-line
        $this->cache     = $cache;
        $this->stepsDto  = $stepsDto;
        $this->taskQueue = $taskQueue;

        $this->init();
    }

    /**
     * @return TaskResponseDto
     */
    abstract public function execute();

    /**
     * @example 'backup_site_restore_themes'
     * @return string
     */
    public static function getTaskName()
    {
        throw new WPStagingException('Any extending class MUST override the getTaskName method.');
    }

    /**
     * @example 'Restoring Themes From Backup'
     * @return string
     */
    public static function getTaskTitle()
    {
        throw new WPStagingException('Any extending class MUST override the getTaskTitle method.');
    }

    /**
     * @param AbstractJob $job
     * @return void
     */
    public function setJobContext(AbstractJob $job)
    {
        $this->cache->setLifetime(HOUR_IN_SECONDS);
        $this->cache->setFilename('task_steps_' . static::getTaskName());

        $this->stepsDto->hydrate($this->cache->get([
            'current' => 0,
            'total'   => 0,
        ]));

        $this->job = $job;
    }

    /**
     * @param JobDataDto $jobDataDto
     * @return void
     */
    public function setJobDataDto(JobDataDto $jobDataDto)
    {
        $this->jobDataDto = $jobDataDto;
        $this->taskQueue->setup(static::getTaskName());
        $this->taskQueue->seek($this->jobDataDto->getQueueOffset());
        $this->setupCurrentTaskDto();
    }

    /**
     * @var bool $incrementStep Whether to increment the step when generating a response or not.
     *                          This might be false when you want to generate a response and still be
     *                          able to retry the same step in the next request.
     *
     * @return TaskResponseDto
     */
    public function generateResponse($incrementStep = true): TaskResponseDto
    {
        if ($incrementStep) {
            $this->stepsDto->incrementCurrentStep();
        }

        // TODO Hydrate
        $response = $this->getResponseDto();
        $response->setIsRunning(!$this->stepsDto->isFinished());
        $response->setPercentage($this->stepsDto->getPercentage());
        $response->setTotal($this->stepsDto->getTotal());
        $response->setStep($this->stepsDto->getCurrent());
        $response->setTask($this->getTaskName());
        $response->setStatusTitle(static::getTaskTitle());
        $response->setJobId($this->jobDataDto->getId());

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

        $response = apply_filters('wpstg.task.response', $response);

        return $response;
    }

    /**
     * Save StepsDto to disk.
     * This happens automatically during the shutdown process,
     * but it can also be called manually.
     * @return void
     */
    public function persistStepsDto()
    {
        $this->cache->save($this->stepsDto->toArray(), true);
    }

    /**
     * @return string|null
     */
    public function getJobName()
    {
        return $this->jobName;
    }

    /**
     * @param string|null $jobName
     */
    public function setJobName($jobName)
    {
        $this->jobName = $jobName;
    }

    /**
     * @return string|int|null
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * @param string|int|null $jobId
     */
    public function setJobId($jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = (bool)$debug;
    }

    /**
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * @return SeekableQueueInterface
     */
    public function getQueue(): SeekableQueueInterface
    {
        return $this->taskQueue;
    }

    /**
     * @param AbstractTaskDto $taskDto
     * @return void
     */
    public function setCurrentTaskDto(AbstractTaskDto $taskDto)
    {
        $this->currentTaskDto = $taskDto;
        $this->jobDataDto->setCurrentTaskData($taskDto->toArray());
    }

    /**
     * @return TaskResponseDto
     */
    protected function getResponseDto()
    {
        return new TaskResponseDto();
    }

    /** @return string */
    protected function getCurrentTaskType(): string
    {
        return '';
    }

    /**
     * @return void
     */
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
        // no-op, can be overridden in child classes
    }

    protected function addLogMessageToResponse(TaskResponseDto $response)
    {
        $response->addMessage($this->logger->getLastLogMsg());
    }
}
