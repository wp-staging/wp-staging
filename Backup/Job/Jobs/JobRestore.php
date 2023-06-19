<?php

namespace WPStaging\Backup\Job\Jobs;

use RuntimeException;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsBackupRestore;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Job\AbstractJob;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Task\Tasks\JobRestore\StartRestoreTask;
use WPStaging\Backup\Task\Tasks\CleanupTmpTablesTask;
use WPStaging\Backup\Task\Tasks\CleanupTmpFilesTask;
use WPStaging\Backup\Task\Tasks\JobRestore\CleanExistingMediaTask;
use WPStaging\Backup\Task\Tasks\JobRestore\ExtractFilesTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreLanguageFilesTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreOtherFilesInWpContentTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RenameDatabaseTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreRequirementsCheckTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreDatabaseTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreMuPluginsTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestorePluginsTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreThemesTask;
use WPStaging\Backup\Task\Tasks\JobRestore\UpdateBackupsScheduleTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreFinishTask;

class JobRestore extends AbstractJob
{
    const TMP_DIRECTORY = 'tmp/restore/';

    /** @var JobRestoreDataDto $jobDataDto */
    protected $jobDataDto;

    /** @var array The array of tasks to execute for this job. Populated at init(). */
    protected $tasks = [];

    public static function getJobName()
    {
        return 'backup_restore';
    }

    protected function getJobTasks()
    {
        return $this->tasks;
    }

    public function onWpShutdown()
    {
        if ($this->jobDataDto->isFinished()) {
            WPStaging::make(AnalyticsBackupRestore::class)->enqueueFinishEvent($this->jobDataDto->getId(), $this->jobDataDto);
        }

        parent::onWpShutdown();
    }

    protected function execute()
    {
        //$this->startBenchmark();

        try {
            $response = $this->getResponse($this->currentTask->execute());
        } catch (\Exception $e) {
            $this->currentTask->getLogger()->critical($e->getMessage());
            $response = $this->getResponse($this->currentTask->generateResponse(false));
        }

        //$this->finishBenchmark(get_class($this->currentTask));

        return $response;
    }

    protected function init()
    {
        if ($this->jobDataDto->getBackupMetadata()) {
            return;
        }

        try {
            $backupMetadata = (new BackupMetadata())->hydrateByFilePath($this->jobDataDto->getFile());
        } catch (\Exception $e) {
            throw $e;
        }

        if (!$this->isValidMetadata($backupMetadata)) {
            throw new RuntimeException('Failed to get backup metadata.');
        }

        $this->jobDataDto->setBackupMetadata($backupMetadata);
        $this->jobDataDto->setTmpDirectory($this->getJobTmpDirectory());
        $this->jobDataDto->setIsSameSiteBackupRestore($this->isSameSiteBackupRestore($backupMetadata));

        $this->tasks[] = StartRestoreTask::class;
        $this->tasks[] = CleanupTmpFilesTask::class;
        $this->tasks[] = CleanupTmpTablesTask::class;
        $this->setRequirementTask();

        if ($backupMetadata->getIsExportingUploads()) {
            $this->tasks[] = CleanExistingMediaTask::class;
        }

        $this->addExtractFilesTasks();

        if ($backupMetadata->getIsExportingThemes()) {
            $this->tasks[] = RestoreThemesTask::class;
        }

        if ($backupMetadata->getIsExportingPlugins()) {
            $this->tasks[] = RestorePluginsTask::class;
        }

        if (
            $backupMetadata->getIsExportingThemes()
            || $backupMetadata->getIsExportingPlugins()
            || $backupMetadata->getIsExportingMuPlugins()
            || $backupMetadata->getIsExportingOtherWpContentFiles()
        ) {
            $this->tasks[] = RestoreLanguageFilesTask::class;
        }

        if ($backupMetadata->getIsExportingOtherWpContentFiles()) {
            $this->tasks[] = RestoreOtherFilesInWpContentTask::class;
        }

        if ($backupMetadata->getIsExportingDatabase()) {
            $this->addDatabaseTasks();
        }

        if ($backupMetadata->getIsExportingMuPlugins()) {
            $this->tasks[] = RestoreMuPluginsTask::class;
        }

        $this->tasks[] = CleanupTmpFilesTask::class;
        $this->tasks[] = RestoreFinishTask::class;
    }

    protected function setRequirementTask()
    {
        $this->tasks[] = RestoreRequirementsCheckTask::class;
    }

    protected function addMultisiteTasks()
    {
        // no-op
    }

    /**
     * @param $backupMetadata
     * @return bool
     */
    protected function isSameSiteBackupRestore($backupMetadata)
    {
        if (is_multisite()) {
            $isSameSite = site_url() === $backupMetadata->getSiteUrl() &&
                ABSPATH === $backupMetadata->getAbsPath() &&
                is_subdomain_install() === $backupMetadata->getSubdomainInstall();
        } else {
            $isSameSite = site_url() === $backupMetadata->getSiteUrl() &&
                ABSPATH === $backupMetadata->getAbsPath();
        }

        return $isSameSite;
    }

    /**
     * @return string
     */
    private function getJobTmpDirectory()
    {
        $dir = $this->directory->getTmpDirectory() . $this->jobDataDto->getId();
        $this->filesystem->mkdir($dir);

        return trailingslashit($dir);
    }

    private function addDatabaseTasks()
    {
        $metadata = $this->jobDataDto->getBackupMetadata();
        if ($metadata->getIsMultipartBackup()) {
            foreach ($metadata->getMultipartMetadata()->getDatabaseParts() as $part) {
                $this->tasks[] = RestoreDatabaseTask::class;
            }
        } else {
            $this->tasks[] = RestoreDatabaseTask::class;
        }

        $this->addMultisiteTasks();

        $this->tasks[] = UpdateBackupsScheduleTask::class;
        $this->tasks[] = RenameDatabaseTask::class;
        $this->tasks[] = CleanupTmpTablesTask::class;
    }

    private function addExtractFilesTasks()
    {
        $metadata = $this->jobDataDto->getBackupMetadata();
        if (!$metadata->getIsMultipartBackup()) {
            $this->tasks[] = ExtractFilesTask::class;
            return;
        }

        foreach ($metadata->getMultipartMetadata()->getFileParts() as $part) {
            $this->tasks[] = ExtractFilesTask::class;
        }
    }

    /**
     * @param BackupMetadata $metadata
     *
     * @return bool
     */
    private function isValidMetadata($metadata)
    {
        $extension = pathinfo($this->jobDataDto->getFile(), PATHINFO_EXTENSION);
        if ($extension !== 'sql') {
            return !empty($metadata->getHeaderStart());
        }

        return !empty($metadata->getMaxTableLength()) && !empty($metadata->getMultipartMetadata()->getDatabaseParts());
    }
}
