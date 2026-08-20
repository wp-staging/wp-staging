<?php








namespace WPStaging\Framework\Job\Dto;

use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Queue\FinishedQueueException;

use function WPStaging\functions\debug_log;

class JobDataDto extends AbstractDto
{
 
    const FILTER_IS_MULTIPART_BACKUP = 'wpstg.backup.isMultipartBackup';

 
    const FILTER_MAX_MULTIPART_BACKUP_SIZE = 'wpstg.backup.maxMultipartBackupSize';

 
    const FILTER_RESOURCES_EXECUTION_TIME_LIMIT = 'wpstg.resources.executionTimeLimit';

 
    const FILTER_RESOURCES_BACKUP_RESTORE_MAX_EXECUTION_TIME_IN_SECONDS = 'wpstg.resourceTrait.backupRestoreMaxExecutionTimeInSeconds';

 
    const FILTER_RESOURCES_FILE_APPEND_TIME_LIMIT = 'wpstg.resource.file_append_time_limit';

 
    const FILTER_RESOURCES_IGNORE_TIME_LIMIT = 'wpstg.resources.ignoreTimeLimit';

 
    const FILTER_RESOURCES_MEMORY_LIMIT = 'wpstg.resources.memoryLimit';

 
    const FILTER_RESOURCES_IGNORE_MEMORY_LIMIT = 'wpstg.resources.ignoreMemoryLimit';

 
    const FILTER_BACKUP_INMEMORY_EXTRACTION_LIMIT = 'wpstg.backup.inmemory_extraction.limit';

 
    const FILTER_BACKUP_USE_INMEMORY_EXTRACTION = 'wpstg.backup.use_inmemory_extraction';




    const FILTER_PERFORMANCE_MODE = 'wpstg.job.performance_mode';

 
    protected $id;

 
    protected $init;

 
    protected $finished;

 
    protected $statusCheck;

 
    protected $lastQueryInfoJSON;





    protected $currentExecutionTimeDatabaseImport = 10;

 
    protected $isSlowMySqlServer = false;

 
    protected $dbRequestTime = 0;

 
    protected $batchSize = 0;

 
    private $tableAverageRowLength = 0;

 
    protected $taskHealthName = '';

 
    protected $taskHealthSequentialFailedRetries = 0;

 
    protected $taskHealthResponded = false;

 
    protected $taskHealthIsRetrying = false;

 
    protected $queueOffset = 0;

 
    protected $queueCount = 0;

 
    protected $databaseOnlyBackup = false;

 
    protected $requirementFailReason = '';

 
    protected $startTime;

 
    protected $endTime;

 
    protected $duration;

 
    protected $cleaned;

 
    protected $taskQueue;

 
    protected $currentTaskIndex;

 
    protected $retries;

 
    protected $totalChunks = 0;

 
    protected $currentTaskData = [];

 
    protected $isWpCliRequest = false;

 
    protected $isRestRequest = false;

 
    protected $isSyncRequest = false;





    protected $numberOfRetries = 0;




    public function getId()
    {
        if (empty($this->id)) {
            throw new \UnexpectedValueException('ID is not set');
        }

        return $this->id;
    }




    public function setId($id)
    {
        $this->id = $id;
    }




    public function isInit()
    {
        return $this->init;
    }




    public function setInit($init)
    {
        $this->init = $init;
    }




    public function isFinished()
    {
        return $this->finished;
    }




    public function setFinished($finished)
    {
        $this->finished = $finished;
    }




    public function isStatusCheck()
    {
        return $this->statusCheck;
    }




    public function setStatusCheck($statusCheck)
    {
        $this->statusCheck = $statusCheck;
    }




    public function getIsSlowMySqlServer()
    {
        return $this->isSlowMySqlServer;
    }





    public function setIsSlowMySqlServer($isSlowMySqlServer)
    {
        $this->isSlowMySqlServer = $isSlowMySqlServer;
    }




    public function getDbRequestTime()
    {
        return $this->dbRequestTime;
    }




    public function setDbRequestTime($dbRequestTime)
    {
        $this->dbRequestTime = $dbRequestTime;
    }




    public function getBatchSize()
    {
        return $this->batchSize;
    }




    public function setBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;
    }




    public function getLastQueryInfoJSON()
    {
        return $this->lastQueryInfoJSON;
    }




    public function setLastQueryInfoJSON($lastQueryInfoJSON)
    {
        if (is_array($lastQueryInfoJSON)) {
            $lastQueryInfoJSON = json_encode($lastQueryInfoJSON);
            debug_log('Trying to hydrate lastqueryinfoJSON with an array. String expected.');
        }

        $this->lastQueryInfoJSON = $lastQueryInfoJSON;
    }




    public function getCurrentExecutionTimeDatabaseImport(): int
    {
        $time = $this->currentExecutionTimeDatabaseImport;
        if ($time < 10) {
            return 10;
        }

        return $time;
    }




    public function incrementCurrentExecutionTimeDatabaseImport()
    {
        $this->currentExecutionTimeDatabaseImport += 5;
    }





    public function setCurrentExecutionTimeDatabaseImport($currentExecutionTimeDatabaseImport = 0)
    {
        $this->currentExecutionTimeDatabaseImport = $currentExecutionTimeDatabaseImport;
    }




    public function getTableAverageRowLength()
    {
        return $this->tableAverageRowLength;
    }




    public function setTableAverageRowLength($tableAverageRowLength)
    {
        $this->tableAverageRowLength = $tableAverageRowLength;
    }




    public function getTaskHealthName()
    {
        return $this->taskHealthName;
    }




    public function setTaskHealthName($taskHealthName)
    {
        $this->taskHealthName = $taskHealthName;
    }




    public function getTaskHealthSequentialFailedRetries()
    {
        return $this->taskHealthSequentialFailedRetries;
    }




    public function setTaskHealthSequentialFailedRetries($taskHealthSequentialFailedRetries)
    {
        $this->taskHealthSequentialFailedRetries = $taskHealthSequentialFailedRetries;
    }




    public function getTaskHealthResponded()
    {
        return $this->taskHealthResponded;
    }




    public function setTaskHealthResponded($taskHealthResponded)
    {
        $this->taskHealthResponded = $taskHealthResponded;
    }




    public function getTaskHealthIsRetrying()
    {
        return $this->taskHealthIsRetrying;
    }




    public function setTaskHealthIsRetrying($taskHealthIsRetrying)
    {
        $this->taskHealthIsRetrying = $taskHealthIsRetrying;
    }




    public function getQueueOffset()
    {
        return (int)$this->queueOffset;
    }




    public function setQueueOffset($queueOffset)
    {
        $this->queueOffset = (int)$queueOffset;
    }




    public function getQueueCount()
    {
        return (int)$this->queueCount;
    }




    public function setQueueCount($queueCount)
    {
        $this->queueCount = (int)$queueCount;
    }




    public function getDatabaseOnlyBackup()
    {
        return (bool)$this->databaseOnlyBackup;
    }




    public function setDatabaseOnlyBackup($databaseOnlyBackup)
    {
        $this->databaseOnlyBackup = (bool)$databaseOnlyBackup;
    }




    public function getRequirementFailReason()
    {
        return $this->requirementFailReason;
    }




    public function setRequirementFailReason($requirementFailReason)
    {
        $this->requirementFailReason = $requirementFailReason;
    }




    public function getStartTime()
    {
        return $this->startTime;
    }




    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;
    }




    public function getEndTime()
    {
        return $this->endTime;
    }




    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;
    }






    public function getDuration()
    {
        if (is_int($this->startTime) && is_int($this->endTime)) {
            return $this->endTime - $this->startTime;
        }

        return 0;
    }




    public function setDuration($duration)
    {
        $this->duration = $duration;
    }




    public function isCleaned()
    {
        return $this->cleaned;
    }




    public function setCleaned($cleaned = true)
    {
        $this->cleaned = $cleaned;
    }

 
    public function setCurrentTaskIndex($index)
    {
        $this->currentTaskIndex = $index;
    }

 
    public function getCurrentTaskIndex()
    {
        return $this->currentTaskIndex;
    }

 
    public function setTaskQueue($queue)
    {
        $this->taskQueue = $queue;
    }

 
    public function getTaskQueue()
    {
        return $this->taskQueue;
    }

 
    public function getCurrentTask()
    {
        if (empty($this->taskQueue[$this->currentTaskIndex])) {
            $debugTaskQueue = print_r($this->taskQueue, true);
            debug_log("getCurrenTask queue is empty $debugTaskQueue Current task index: $this->currentTaskIndex");
            return '';
        }

        return $this->taskQueue[$this->currentTaskIndex];
    }

 
    public function moveToNextTask()
    {
        $this->checkNextTask();
        $this->currentTaskIndex++;
    }

 
    public function checkNextTask()
    {
        if (count($this->taskQueue) === $this->currentTaskIndex + 1) {
            throw new FinishedQueueException();
        }
    }

 
    public function getRetries()
    {
        return $this->retries;
    }

 
    public function setRetries($retries)
    {
        $this->retries = $retries;
    }




    public function getCurrentTaskData(): array
    {
        return $this->currentTaskData;
    }




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




    public function incrementTotalChunks()
    {
        $this->totalChunks++;
    }




    public function getIsWpCliRequest(): bool
    {
        return $this->isWpCliRequest;
    }




    public function setIsWpCliRequest(bool $isWpCliRequest)
    {
        $this->isWpCliRequest = $isWpCliRequest;
    }




    public function getIsRestRequest(): bool
    {
        return $this->isRestRequest;
    }





    public function setIsRestRequest(bool $isRestRequest)
    {
        $this->isRestRequest = $isRestRequest;
    }




    public function getIsSyncRequest(): bool
    {
        return $this->isSyncRequest;
    }





    public function setIsSyncRequest(bool $isSyncRequest)
    {
        $this->isSyncRequest = $isSyncRequest;
    }




    public function getNumberOfRetries(): int
    {
        return $this->numberOfRetries;
    }





    public function setNumberOfRetries(int $numberOfRetries = 0)
    {
        $this->numberOfRetries = $numberOfRetries;
    }




    public function incrementNumberOfRetries()
    {
        $this->numberOfRetries++;
    }




    public function resetNumberOfRetries()
    {
        $this->numberOfRetries = 0;
    }

    public function getIsFastPerformanceMode(): bool
    {
        $mode = Hooks::applyFilters(self::FILTER_PERFORMANCE_MODE, 'fast');

 
        if (empty($mode)) {
            return true;
        }

        $mode = strtolower($mode);
        if (!in_array($mode, ['fast', 'safe'], true)) {
            return true;
        }

        return $mode === 'fast';
    }
}
