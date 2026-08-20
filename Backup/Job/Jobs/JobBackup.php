<?php








namespace WPStaging\Backup\Job\Jobs;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Exceptions\NothingToBackupException;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupMuPluginsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupOtherFilesTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupOtherWpRootFilesTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupPluginsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupRequirementsCheckTask;
use WPStaging\Backup\Task\Tasks\JobBackup\CleanupValidationFilesTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupThemesTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupUploadsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\DatabaseBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\FilesystemScannerTask;
use WPStaging\Backup\Task\Tasks\JobBackup\FinalizeBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\IncludeDatabaseTask;
use WPStaging\Backup\Task\Tasks\JobBackup\RecalibrateFilesCountTask;
use WPStaging\Backup\Task\Tasks\JobBackup\ScheduleBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\SignBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\ValidateBackupTask;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsBackupCreate;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Job\Task\AbstractTask;

class JobBackup extends AbstractJob
{
 
    const JOB_STATUS_NOTHING_TO_BACKUP = 'JOB_NOTHING_TO_BACKUP';

 
    protected $jobDataDto;

 
    protected $tasks = [];

    public static function getJobName()
    {
        return 'backup_job';
    }

    protected function getJobTasks()
    {
        return $this->tasks;
    }

    protected function execute()
    {
 

        try {
            $response = $this->getResponse($this->currentTask->execute());
        } catch (NothingToBackupException $e) {
            return $this->getNothingToBackupResponse($e->getMessage());
        } catch (\Exception $e) {
            $title = $this->currentTask->getTaskTitle();
            if (empty($title)) {
                $title = 'Backup job';
            }

            $this->currentTask->getLogger()->critical($title . ' failed! Error: ' . $e->getMessage());
            $response = $this->getResponse($this->currentTask->generateResponse(false));
        }

 

        return $response;
    }








    protected function getNothingToBackupResponse(string $message): TaskResponseDto
    {
        $this->currentTask->getLogger()->warning($message);

        WPStaging::make(AnalyticsBackupCreate::class)->enqueueFinishEvent($this->jobDataDto->getId(), $this->jobDataDto);

        $this->jobDataDto->setFinished(true);
        $this->persistJobDataDto();

        $response = $this->currentTask->generateResponse(false);
        $response->setIsRunning(false);
        $response->setJobStatus(self::JOB_STATUS_NOTHING_TO_BACKUP);

        $this->jobTransientCache->failJob(esc_html__('Nothing to backup', 'wp-staging'), $message, JobTransientCache::SEVERITY_NOTICE);

        return $response;
    }































    protected function getResponse(TaskResponseDto $response)
    {
        $response = parent::getResponse($response);

        if ($this->currentTask instanceof AbstractTask) {
            $this->jobDataDto->setQueueOffset($this->currentTask->getQueue()->getOffset());
            $this->currentTask->persistStepsDto();
        }

        $this->persistJobDataDto();

        return $response;
    }




    protected function init()
    {
        $this->setRequirementTask();

        if ($this->jobDataDto->getRepeatBackupOnSchedule() && !$this->jobDataDto->getIsCreateScheduleBackupNow()) {
            $this->addSchedulerTask();
            $this->addFinishBackupTask();
            return;
        }

        if ($this->jobDataDto->getIsCreateBackupInBackground()) {
            if ($this->jobDataDto->getRepeatBackupOnSchedule()) {
                $this->addSchedulerTask();
            }

            $this->addFinishBackupTask();
            return;
        }

        $this->setScannerTask();
        if ($this->jobDataDto->getIsExportingOtherWpContentFiles()) {
            $this->tasks[] = BackupOtherFilesTask::class;
        }

        if ($this->jobDataDto->getIsExportingPlugins()) {
            $this->tasks[] = BackupPluginsTask::class;
        }

        if ($this->jobDataDto->getIsExportingMuPlugins()) {
            $this->tasks[] = BackupMuPluginsTask::class;
        }

        if ($this->jobDataDto->getIsExportingThemes()) {
            $this->tasks[] = BackupThemesTask::class;
        }

        if ($this->jobDataDto->getIsExportingUploads()) {
            $this->tasks[] = BackupUploadsTask::class;
        }

        $this->addBackupOtherWpRootFilesTasks();

        $this->addDatabaseTasks();

        $this->addFinalizeTask();
        if ($this->jobDataDto->getRepeatBackupOnSchedule()) {
            $this->addSchedulerTask();
        }

        if (!$this->jobDataDto->getIsMultipartBackup()) {
            $this->tasks[] = RecalibrateFilesCountTask::class;
        }






        $this->addValidationTasks();

        $this->tasks[] = SignBackupTask::class;

        $this->addStoragesTasks();
        $this->addFinishBackupTask();
    }

    protected function addDatabaseTasks()
    {
        if (!$this->jobDataDto->getIsExportingDatabase()) {
            return;
        }

        $this->tasks[] = DatabaseBackupTask::class;
        $this->tasks[] = IncludeDatabaseTask::class;
    }

    protected function addCompressionTask()
    {
 
    }




    protected function addStoragesTasks()
    {
 
    }




    protected function addBackupOtherWpRootFilesTasks()
    {
        if ($this->jobDataDto->getIsExportingOtherWpRootFiles()) {
            $this->tasks[] = BackupOtherWpRootFilesTask::class;
        }
    }




    protected function addFinalizeTask()
    {
        $this->tasks[] = FinalizeBackupTask::class;
    }

    protected function addValidationTasks()
    {
        $this->tasks[] = ValidateBackupTask::class;
        $this->tasks[] = CleanupValidationFilesTask::class;
    }




    protected function addFinishBackupTask()
    {
        $this->tasks[] = FinishBackupTask::class;
    }




    protected function addSchedulerTask()
    {
        $this->tasks[] = ScheduleBackupTask::class;
    }




    protected function setRequirementTask()
    {
        $this->tasks[] = BackupRequirementsCheckTask::class;
    }




    protected function setScannerTask()
    {
        $this->tasks[] = FilesystemScannerTask::class;
    }
}
