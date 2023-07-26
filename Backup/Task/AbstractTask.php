<?php

namespace WPStaging\Backup\Task;

use WPStaging\Framework\Exceptions\WPStagingException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Backup\Dto\JobDataDto;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Backup\Job\AbstractJob;
use WPStaging\Backup\Task\Tasks\JobBackup\FilesystemScannerTask;
use WPStaging\Backup\Task\Tasks\JobRestore\ExtractFilesTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Core\Utils\Logger;

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

    /** @var SeekableQueueInterface */
    protected $taskQueue;

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue)
    {
        /** @var Logger logger */
        $this->logger    = $logger; // @phpstan-ignore-line
        $this->cache     = $cache;
        $this->stepsDto  = $stepsDto;
        $this->taskQueue = $taskQueue;

        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    /**
     * @return \WPStaging\Backup\Dto\TaskResponseDto
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

    public function setJobDataDto(JobDataDto $jobDataDto)
    {
        $this->jobDataDto = $jobDataDto;
        $this->taskQueue->setup(static::getTaskName());
        $this->taskQueue->seek($this->jobDataDto->getQueueOffset());
    }

    /**
     * @var bool $incrementStep Whether to increment the step when generating a response or not.
     *                          This might be false when you want to generate a response and still be
     *                          able to retry the same step in the next request.
     *
     * @return \WPStaging\Backup\Dto\TaskResponseDto
     */
    public function generateResponse($incrementStep = true)
    {
        if ($incrementStep) {
            $this->stepsDto->incrementCurrentStep();
        }

        // TODO Hydrate
        $response = $this->getResponseDto();
        $response->setIsRunning($this->stepsDto->isFinished());
        $response->setPercentage($this->stepsDto->getPercentage());
        $response->setTotal($this->stepsDto->getTotal());
        $response->setStep($this->stepsDto->getCurrent());
        $response->setTask($this->getTaskName());
        $response->setStatusTitle(static::getTaskTitle());
        $response->setJobId($this->jobDataDto->getId());

        /*
         * If this backup contains only a database, let's not display log entries
         * for file-related tasks, as they expose internal behavior of the backup
         * feature that are not relevant to the user.
         */
        if ($this->jobDataDto->getDatabaseOnlyBackup()) {
            if (
                !$this instanceof FilesystemScannerTask
                && !$this instanceof FileBackupTask
                && !$this instanceof ExtractFilesTask
            ) {
                $response->addMessage($this->logger->getLastLogMsg());
            }
        } else {
            $response->addMessage($this->logger->getLastLogMsg());
        }

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
            $this->cache->delete();
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

    protected function getResponseDto()
    {
        return new TaskResponseDto();
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function getQueue()
    {
        return $this->taskQueue;
    }
}
