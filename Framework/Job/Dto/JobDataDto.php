<?php

namespace WPStaging\Framework\Job\Dto;

use WPStaging\Framework\Queue\FinishedQueueException;

use function WPStaging\functions\debug_log;

class JobDataDto extends AbstractDto
{
    /**
     * @var string
     */
    const FILTER_IS_MULTIPART_BACKUP = 'wpstg.backup.isMultipartBackup';

    /**
     * @var string
     */
    const FILTER_MAX_MULTIPART_BACKUP_SIZE = 'wpstg.backup.maxMultipartBackupSize';

    /** @var string|int|null */
    protected $id;

    /** @var bool */
    protected $init;

    /** @var bool */
    protected $finished;

    /** @var bool */
    protected $statusCheck;

    /** @var string */
    protected $lastQueryInfoJSON;

    /** @var bool */
    protected $isSlowMySqlServer = false;

    /** @var double */
    protected $dbRequestTime = 0;

    /** @var int */
    protected $batchSize = 0;

    /** @var int */
    private $tableAverageRowLength = 0;

    /** @var string The name of the task we are checking the health */
    protected $taskHealthName = '';

    /** @var int How many times this task failed in sequence */
    protected $taskHealthSequentialFailedRetries = 0;

    /** @var bool Whether the task has responded */
    protected $taskHealthResponded = false;

    /** @var bool Whether the task is currently retrying a request that failed */
    protected $taskHealthIsRetrying = false;

    /** @var int Where to set the Task queue offset */
    protected $queueOffset = 0;

    /** @var int Calculating the queue count is expensive, so we store it here as a metadata */
    protected $queueCount = 0;

    /** @var bool Whether this backup contains only a database */
    protected $databaseOnlyBackup = false;

    /** @var string The reason why a requirement fail, if it failed. */
    protected $requirementFailReason = '';

    /** @var int Unix timestamp of when this job started. */
    protected $startTime;

    /** @var int Unix timestamp of when this job finished, if it finished at all. */
    protected $endTime;

    /** @var int How long this job took to run, in seconds. */
    protected $duration;

    /** @var bool Whether this job cleaned. */
    protected $cleaned;

    /** @var array list of tasks to be performed in this job */
    protected $taskQueue;

    /** @var int pointer|index to the current task in the queue */
    protected $currentTaskIndex;

    /** @var int how often a request is retried */
    protected $retries;

    /** @var int How many chunks of compressed data this backup has. */
    protected $totalChunks = 0;

    /** @var array Data for the current task. */
    protected $currentTaskData = [];

    /** @var bool */
    protected $isWpCliRequest = false;

    /**
     * Number of retries for the current task
     * @var int
     */
    private $numberOfRetries = 0;

    /**
     * @return string|int|null
     */
    public function getId()
    {
        if (empty($this->id)) {
            throw new \UnexpectedValueException('ID is not set');
        }

        return $this->id;
    }

    /**
     * @param string|int|null $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return bool
     */
    public function isInit()
    {
        return $this->init;
    }

    /**
     * @param bool $init
     */
    public function setInit($init)
    {
        $this->init = $init;
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        return $this->finished;
    }

    /**
     * @param bool $finished
     */
    public function setFinished($finished)
    {
        $this->finished = $finished;
    }

    /**
     * @return bool
     */
    public function isStatusCheck()
    {
        return $this->statusCheck;
    }

    /**
     * @param bool $statusCheck
     */
    public function setStatusCheck($statusCheck)
    {
        $this->statusCheck = $statusCheck;
    }

    /**
     * @return bool
     */
    public function getIsSlowMySqlServer()
    {
        return $this->isSlowMySqlServer;
    }

    /**
     * @param bool
     * @return void
     */
    public function setIsSlowMySqlServer($isSlowMySqlServer)
    {
        $this->isSlowMySqlServer = $isSlowMySqlServer;
    }

    /**
     * @return float|int
     */
    public function getDbRequestTime()
    {
        return $this->dbRequestTime;
    }

    /**
     * @param float|int $dbRequestTime
     */
    public function setDbRequestTime($dbRequestTime)
    {
        $this->dbRequestTime = $dbRequestTime;
    }

    /**
     * @return int
     */
    public function getBatchSize()
    {
        return $this->batchSize;
    }

    /**
     * @param int $batchSize
     */
    public function setBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;
    }

    /**
     * @return string
     */
    public function getLastQueryInfoJSON()
    {
        return $this->lastQueryInfoJSON;
    }

    /**
     * @param string $lastQueryInfoJSON
     */
    public function setLastQueryInfoJSON($lastQueryInfoJSON)
    {
        if (is_array($lastQueryInfoJSON)) {
            $lastQueryInfoJSON = json_encode($lastQueryInfoJSON);
            debug_log('Trying to hydrate lastqueryinfoJSON with an array. String expected.');
        }

        $this->lastQueryInfoJSON = $lastQueryInfoJSON;
    }

    /**
     * @return int
     */
    public function getTableAverageRowLength()
    {
        return $this->tableAverageRowLength;
    }

    /**
     * @param int $tableAverageRowLength
     */
    public function setTableAverageRowLength($tableAverageRowLength)
    {
        $this->tableAverageRowLength = $tableAverageRowLength;
    }

    /**
     * @return string
     */
    public function getTaskHealthName()
    {
        return $this->taskHealthName;
    }

    /**
     * @param string $taskHealthName
     */
    public function setTaskHealthName($taskHealthName)
    {
        $this->taskHealthName = $taskHealthName;
    }

    /**
     * @return int
     */
    public function getTaskHealthSequentialFailedRetries()
    {
        return $this->taskHealthSequentialFailedRetries;
    }

    /**
     * @param int $taskHealthSequentialFailedRetries
     */
    public function setTaskHealthSequentialFailedRetries($taskHealthSequentialFailedRetries)
    {
        $this->taskHealthSequentialFailedRetries = $taskHealthSequentialFailedRetries;
    }

    /**
     * @return bool
     */
    public function getTaskHealthResponded()
    {
        return $this->taskHealthResponded;
    }

    /**
     * @param bool $taskHealthResponded
     */
    public function setTaskHealthResponded($taskHealthResponded)
    {
        $this->taskHealthResponded = $taskHealthResponded;
    }

    /**
     * @return bool
     */
    public function getTaskHealthIsRetrying()
    {
        return $this->taskHealthIsRetrying;
    }

    /**
     * @param bool $taskHealthIsRetrying
     */
    public function setTaskHealthIsRetrying($taskHealthIsRetrying)
    {
        $this->taskHealthIsRetrying = $taskHealthIsRetrying;
    }

    /**
     * @return int
     */
    public function getQueueOffset()
    {
        return (int)$this->queueOffset;
    }

    /**
     * @param bool $queueOffset
     */
    public function setQueueOffset($queueOffset)
    {
        $this->queueOffset = (int)$queueOffset;
    }

    /**
     * @return int
     */
    public function getQueueCount()
    {
        return (int)$this->queueCount;
    }

    /**
     * @param int $queueCount
     */
    public function setQueueCount($queueCount)
    {
        $this->queueCount = (int)$queueCount;
    }

    /**
     * @return bool
     */
    public function getDatabaseOnlyBackup()
    {
        return (bool)$this->databaseOnlyBackup;
    }

    /**
     * @param bool $databaseOnlyBackup
     */
    public function setDatabaseOnlyBackup($databaseOnlyBackup)
    {
        $this->databaseOnlyBackup = (bool)$databaseOnlyBackup;
    }

    /**
     * @return string
     */
    public function getRequirementFailReason()
    {
        return $this->requirementFailReason;
    }

    /**
     * @param string $requirementFailReason
     */
    public function setRequirementFailReason($requirementFailReason)
    {
        $this->requirementFailReason = $requirementFailReason;
    }

    /**
     * @return int
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param int $startTime
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;
    }

    /**
     * @return int
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param int $endTime
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;
    }

    /**
     * This method expects the job to have finished successfully, otherwise it will return zero.
     *
     * @return int
     */
    public function getDuration()
    {
        if (is_int($this->startTime) && is_int($this->endTime)) {
            return $this->endTime - $this->startTime;
        }

        return 0;
    }

    /**
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return bool
     */
    public function isCleaned()
    {
        return $this->cleaned;
    }

    /**
     * @param bool $cleaned
     */
    public function setCleaned($cleaned = true)
    {
        $this->cleaned = $cleaned;
    }

    /** @param int $index */
    public function setCurrentTaskIndex($index)
    {
        $this->currentTaskIndex = $index;
    }

    /** @return int */
    public function getCurrentTaskIndex()
    {
        return $this->currentTaskIndex;
    }

    /** @param array $queue */
    public function setTaskQueue($queue)
    {
        $this->taskQueue = $queue;
    }

    /** @return array */
    public function getTaskQueue()
    {
        return $this->taskQueue;
    }

    /** @return string */
    public function getCurrentTask()
    {
        if (empty($this->taskQueue[$this->currentTaskIndex])) {
            $debugTaskQueue = print_r($this->taskQueue, true);
            debug_log("getCurrenTask queue is empty $debugTaskQueue Current task index: $this->currentTaskIndex");
            return '';
        }

        return $this->taskQueue[$this->currentTaskIndex];
    }

    /** @throws FinishedQueueException */
    public function moveToNextTask()
    {
        if (count($this->taskQueue) === $this->currentTaskIndex + 1) {
            throw new FinishedQueueException();
        }

        $this->currentTaskIndex++;
    }

    /** @return int */
    public function getRetries()
    {
        return $this->retries;
    }

    /** @param int $retries */
    public function setRetries($retries)
    {
        $this->retries = $retries;
    }

    /**
     * @return array
     */
    public function getCurrentTaskData(): array
    {
        return $this->currentTaskData;
    }

    /**
     * @param array $currentTaskData
     */
    public function setCurrentTaskData(array $currentTaskData)
    {
        $this->currentTaskData = $currentTaskData;
    }

    public function getTotalChunks(): int
    {
        return $this->totalChunks;
    }

    public function setTotalChunks(int $totalChunks)
    {
        $this->totalChunks = $totalChunks;
    }

    /**
     * @return bool
     */
    public function getIsWpCliRequest(): bool
    {
        return $this->isWpCliRequest;
    }

    /**
     * @param bool $isWpCliRequest
     */
    public function setIsWpCliRequest(bool $isWpCliRequest)
    {
        $this->isWpCliRequest = $isWpCliRequest;
    }

    /**
     * @return int
     */
    public function getNumberOfRetries(): int
    {
        return $this->numberOfRetries;
    }

    /**
     * @param int $numberOfRetries
     * @return void
     */
    public function setNumberOfRetries(int $numberOfRetries = 0)
    {
        $this->numberOfRetries = $numberOfRetries;
    }

    /**
     * @return void
     */
    public function incrementNumberOfRetries()
    {
        $this->numberOfRetries++;
    }

    /**
     * @return void
     */
    public function resetNumberOfRetries()
    {
        $this->numberOfRetries = 0;
    }
}
