<?php

 
 

namespace WPStaging\Framework\Job;

use RuntimeException;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\Exceptions\WPStagingException;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Interfaces\ShutdownableInterface;
use WPStaging\Framework\Job\Dto\AbstractDto;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Job\Exception\TaskHealthException;
use WPStaging\Framework\Job\Task\AbstractTask;
use WPStaging\Framework\Traits\BenchmarkTrait;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Queue\FinishedQueueException;

use function WPStaging\functions\debug_log;

abstract class AbstractJob implements ShutdownableInterface
{
    use BenchmarkTrait;

 
    protected $jobDataDto;

 
    private $jobDataCache;

 
    private $hasPersisted = false;

 
    private $hasShutdownBackstop = false;

 
    protected $currentTaskName;

 
    protected $currentTask;

 
    protected $filesystem;

 
    protected $directory;

 
    protected $processLock;

 
    protected $diskFullCheck;

 
    protected $jobTransientCache;

 
    protected $memoryExhaustErrorTmpFile = false;

    protected $maxRetries = 10;




    protected $isCancelJob = false;

    public function __construct(
        Cache $jobDataCache,
        JobDataDto $jobDataDto,
        Filesystem $filesystem,
        Directory $directory,
        ProcessLock $processLock,
        DiskWriteCheck $diskFullCheck,
        JobTransientCache $jobTransientCache
    ) {
        $this->jobDataDto   = $jobDataDto;
        $this->jobDataCache = $jobDataCache;
        $this->filesystem   = $filesystem;
        $this->directory    = $directory;

        $this->jobDataCache->setLifetime(HOUR_IN_SECONDS);
        $this->jobDataCache->setFilename('jobCache_' . $this::getJobName());

        $this->processLock   = $processLock;
        $this->diskFullCheck = $diskFullCheck;
        $this->maxRetries    = apply_filters(Assets::FILTER_TESTS_MAXIMUM_RETRIES, $this->maxRetries);

        $this->jobTransientCache = $jobTransientCache;
    }










    public function persist()
    {
        if ($this->jobDataDto->isStatusCheck()) {
            return;
        }

        try {
            $this->diskFullCheck->testDiskIsWriteable();
        } catch (DiskNotWritableException $e) {
 
        }

        if ($this->jobDataDto->isFinished() && !$this->jobDataDto->isCleaned()) {
            $this->cleanup();
            $this->jobDataDto->setCleaned();
            $this->hasPersisted = true;
            return;
        }

        if ($this->currentTask instanceof AbstractTask) {
            $this->jobDataDto->setQueueOffset($this->currentTask->getQueue()->getOffset());
            $this->currentTask->persistStepsDto();
        }

        $this->persistJobDataDto();

        $this->hasPersisted = true;
    }




    public function persistJobDataDto()
    {
        $data = $this->jobDataDto->toArray();

        try {
 
 
            if ($this->jobDataCache->save($data, true) === false) {
                throw new \RuntimeException('Could not persist Job data to cache.');
            }
        } catch (\Exception $e) {
            debug_log("Could not persist Job data to cache:"  . $e->getMessage());
            throw new \RuntimeException('Could not persist Job data to cache: ' . $e->getMessage(), 0, $e);
        }
    }








    public function onWpShutdown()
    {
        if ($this->hasPersisted) {
            return;
        }

        $this->persist();
    }
















    public function persistIfShutdownActionDidNotRun()
    {
        if ($this->hasPersisted) {
            return;
        }

        try {
            $this->persist();
        } catch (\Throwable $e) {
 
 
            debug_log('Job state could not be persisted on shutdown: ' . $e->getMessage());
        }
    }




    protected function registerShutdownBackstop()
    {
        if ($this->hasShutdownBackstop) {
            return;
        }

        $this->hasShutdownBackstop = true;

        register_shutdown_function([$this, 'persistIfShutdownActionDidNotRun']);
    }





    public static function getJobName()
    {
        throw new WPStagingException('Any extending class MUST override the getJobName method.');
    }

 
    abstract protected function getJobTasks();

 
    abstract protected function execute();

 
    abstract protected function init();

 
    public function prepareAndExecute()
    {
 
 
 
 
 
        $this->processLock->lockProcess();

        try {
 
            $this->diskFullCheck->hasDiskWriteTestFailed();
        } catch (DiskNotWritableException $e) {
            $this->jobDataCache->delete();

            return $this->getJobFailResponse($e->getMessage());
        }

        if ($this->getIsCancelled()) {
            $this->jobDataCache->delete();

            return $this->getJobCancelResponse();
        }

        try {
            try {
                $this->prepare();
            } catch (TaskHealthException $e) {
                if ($e->getCode() === TaskHealthException::CODE_TASK_FAILED_TOO_MANY_TIMES) {
                    $this->jobDataCache->delete();

                    return $this->getJobFailResponse($e->getMessage());
                } else {
                    return $this->getJobRetryResponse($e->getMessage());
                }
            } catch (RuntimeException $ex) {
                $this->jobDataCache->delete();

                return $this->getJobFailResponse($ex->getMessage());
            }

            $this->registerShutdownBackstop();

 
            $response = $this->execute();








            $nextTask = $this->jobDataDto->getCurrentTask();

            if (is_subclass_of($nextTask, AbstractTask::class)) {
                $response->setStatusTitle(call_user_func("$nextTask::getTaskTitle"));
            }

            $this->removeMemoryExhaustErrorTmpFile();

            if ($this->getIsCancelled()) {
                $this->jobDataCache->delete();

                return $this->getJobCancelResponse();
            }

            return $response;
        } catch (DiskNotWritableException $e) {






            return $this->getJobRetryResponse($e->getMessage());
        }
    }




    public function updateTasks()
    {
        $this->init();
        $this->addTasks($this->getJobTasks());
    }




    public function getTransientCache(): JobTransientCache
    {
        return $this->jobTransientCache;
    }




    public function getJobDataDto()
    {
        return $this->jobDataDto;
    }




    public function setJobDataDto($jobDataDto)
    {
        $this->jobDataDto = $jobDataDto;
    }

    public function getIsCancelled(): bool
    {
        if ($this->isCancelJob) {
            return false;
        }

        try {
            return $this->jobTransientCache->getJobStatus() === JobTransientCache::STATUS_CANCELLED;
        } catch (\Throwable $e) {
 
            return false;
        }
    }




    protected function checkLastTaskHealth()
    {
 
        if ($this->jobDataDto->getTaskHealthIsRetrying()) {
            $this->jobDataDto->setTaskHealthIsRetrying(false);

            return;
        }

        if (!$this->jobDataDto->getTaskHealthResponded()) {
 
            $this->jobDataDto->setTaskHealthSequentialFailedRetries($this->jobDataDto->getTaskHealthSequentialFailedRetries() + 1);
            $this->jobDataCache->save($this->jobDataDto);

            if ($this->jobDataDto->getTaskHealthSequentialFailedRetries() >= $this->maxRetries) {
                throw TaskHealthException::taskFailedTooManyTimes();
            } else {
                $this->jobDataDto->setTaskHealthIsRetrying(true);
                throw TaskHealthException::retryingTask($this->jobDataDto->getTaskHealthSequentialFailedRetries(), $this->maxRetries);
            }
        }
    }

    public function prepare()
    {
        $data = $this->jobDataCache->get([]);

        if ($data) {
            $this->jobDataDto->hydrate($data);
        }

 
        WPStaging::getInstance()->getContainer()->singleton(JobDataDto::class, $this->jobDataDto);

        $action = empty($_GET['action']) ? '' : sanitize_text_field($_GET['action']);
        if (empty($action)) {
            $action = empty($_POST['action']) ? '' : sanitize_text_field($_POST['action']);
        }

        $this->jobDataDto->setStatusCheck(in_array($action, ['wpstg--backups--status', 'wpstg--job--status'], true));
        if ($this->jobDataDto->isStatusCheck()) {
            return;
        }

        if ($this->jobDataDto->isInit()) {
            $this->cleanup();
            $this->init();
            $this->jobDataDto->setCurrentTaskIndex(0);
            $this->jobDataDto->setCurrentTaskData([]);
            $this->addTasks($this->getJobTasks());
        } else {
            $this->checkLastTaskHealth();
        }

        $this->jobDataDto->setInit(false);

        $this->currentTaskName = $this->jobDataDto->getCurrentTask();

        if (empty($this->currentTaskName)) {
            throw new \RuntimeException('Internal error: Next task of queue job is null or invalid.');
        }

 
        $this->currentTask = WPStaging::getInstance()->get($this->currentTaskName);

        if (!$this->currentTask instanceof AbstractTask) {
            throw new \RuntimeException('Is there enough free disk space? Please free up some space. Delete old backup files and staging sites and try again. Error: Next task of queue job is null or invalid. Task name: ' . $this->currentTaskName . ' Task: ' . print_r($this->currentTask, true));
        }

        if (!$this->jobDataDto instanceof AbstractDto) {
            throw new \RuntimeException('Job Queue DTO is null or invalid.');
        }

        $this->currentTask->setJobContext($this);
        $this->currentTask->setJobDataDto($this->jobDataDto);
        $this->currentTask->setJobId($this->jobDataDto->getId());
        $this->currentTask->setJobName($this::getJobName());
        $this->currentTask->setDebug(defined('WPSTG_DEBUG') && WPSTG_DEBUG);
        $this->currentTask->setupLogger();

 
        $this->jobDataDto->setTaskHealthName($this->currentTaskName);
        $this->jobDataDto->setTaskHealthResponded(false);
    }

    public function commitLogs()
    {
        if ($this->currentTask instanceof AbstractTask) {
            $this->currentTask->commitLogs();
        }
    }

 
    public function getCurrentTask()
    {
        return $this->currentTask;
    }





    public function setMemoryExhaustErrorTmpFile(string $memoryExhaustErrorTmpFile)
    {
        $this->memoryExhaustErrorTmpFile = $memoryExhaustErrorTmpFile;
    }

    protected function removeMemoryExhaustErrorTmpFile()
    {
        if ($this->memoryExhaustErrorTmpFile === '') {
            return;
        }

        if (file_exists($this->memoryExhaustErrorTmpFile)) {
            unlink($this->memoryExhaustErrorTmpFile);
        }
    }

    protected function cleanup()
    {
 
        $this->filesystem->setExcludePaths(['*.*', '!*.cache.php', '!*.cache', '!*.wpstg', '!*.sql']);
        $this->filesystem->delete($this->directory->getCacheDirectory(), $deleteSelf = false);
        $this->filesystem->setExcludePaths([]);
        $this->filesystem->mkdir($this->directory->getCacheDirectory(), true);
    }




    protected function deleteJobDataCache()
    {
        $this->jobDataCache->delete();
    }






    protected function getResponse(TaskResponseDto $response)
    {
        $this->jobDataDto->setTaskHealthResponded(true);
        $this->jobDataDto->setTaskHealthSequentialFailedRetries(0);

        $response->setJob(substr($this->findCurrentJob(), 3));

 
        if ($response->isRunning()) {
            $className = get_class($this->currentTask);
        }

        try {
            if (!$response->isRunning()) {
                $this->jobDataDto->moveToNextTask();
 
 
 
                $this->persistJobDataDto();
            }
        } catch (FinishedQueueException $e) {
            $this->jobDataDto->setFinished(true);
 
            $this->persistJobDataDto();

            return $response;
        }

        $response->setIsRunning(true);

        return $response;
    }

    private function findCurrentJob()
    {
        $class = explode('\\', static::class);

        return end($class);
    }

    protected function addTasks(array $tasks = [])
    {
        $this->jobDataDto->setTaskQueue($tasks);
    }

    protected function getJobCancelResponse(): TaskResponseDto
    {
        $response = new TaskResponseDto();
        $response->setIsRunning(false);
        $response->setJobStatus('JOB_CANCEL');
        $response->addMessage([
            'type'    => 'critical',
            'date'    => $this->getFormattedDate(),
            'message' => esc_html__('Job is cancelled', 'wp-staging'),
        ]);

        return $response;
    }

    protected function getJobFailResponse(string $message): TaskResponseDto
    {
        $response = new TaskResponseDto();
        $response->setIsRunning(false);
        $response->setJobStatus('JOB_FAIL');
        $response->addMessage([
            'type'    => 'critical',
            'date'    => $this->getFormattedDate(),
            'message' => esc_html($message, 'wp-staging'),
        ]);

        return $response;
    }

    protected function getJobRetryResponse(string $message): TaskResponseDto
    {
        $response = new TaskResponseDto();
        $response->setIsRunning(true);
        $response->setJobStatus('JOB_RETRY');
        $response->addMessage([
            'type'    => 'warning',
            'date'    => $this->getFormattedDate(),
            'message' => esc_html($message, 'wp-staging'),
        ]);

        return $response;
    }




    private function getFormattedDate()
    {
        return current_time(Logger::LOG_DATETIME_FORMAT);
    }
}
