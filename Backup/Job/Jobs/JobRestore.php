<?php

namespace WPStaging\Backup\Job\Jobs;

use RuntimeException;
use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsBackupRestore;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Job\AbstractJob;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Task\Tasks\CleanupBakTablesTask;
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

    /**
     * @return string
     */
    public static function getJobName(): string
    {
        return 'backup_restore';
    }

    /**
     * @return array
     */
    protected function getJobTasks(): array
    {
        return $this->tasks;
    }

    /**
     * @return void
     */
    public function onWpShutdown()
    {
        if ($this->jobDataDto->isFinished()) {
            WPStaging::make(AnalyticsBackupRestore::class)->enqueueFinishEvent($this->jobDataDto->getId(), $this->jobDataDto);
        }

        parent::onWpShutdown();
    }

    /**
     * @return TaskResponseDto
     */
    protected function execute(): TaskResponseDto
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

    /**
     * @throws \Exception
     * @return void
     */
    protected function init()
    {
        if ($this->jobDataDto->getBackupMetadata()) {
            return;
        }

        $backupMetadata = (new BackupMetadata())->hydrateByFilePath($this->jobDataDto->getFile());

        if (!$this->isValidMetadata($backupMetadata)) {
            throw new RuntimeException('Failed to get backup metadata.');
        }

        $this->jobDataDto->setBackupMetadata($backupMetadata);
        $this->jobDataDto->setTmpDirectory($this->getJobTmpDirectory());
        $this->jobDataDto->setIsSameSiteBackupRestore($this->isSameSiteBackupRestore($backupMetadata));

        $this->tasks[] = StartRestoreTask::class;
        $this->tasks[] = CleanupTmpFilesTask::class;
        $this->tasks[] = CleanupTmpTablesTask::class;
        if ($backupMetadata->getIsExportingDatabase()) {
            $this->tasks[] = CleanupBakTablesTask::class;
        }

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

    /**
     * @return void
     */
    protected function setRequirementTask()
    {
        $this->tasks[] = RestoreRequirementsCheckTask::class;
    }

    /**
     * @return void
     */
    protected function addMultisiteTasks()
    {
        // no-op
    }

    /**
     * @return void
     */
    protected function addWordPressComTasks()
    {
        // no-op
    }

    /**
     * @param BackupMetadata $backupMetadata
     * @return bool
     */
    protected function isSameSiteBackupRestore(BackupMetadata $backupMetadata): bool
    {
        $this->jobDataDto->setIsUrlSchemeMatched(true);

        // Exclusive check for multisite subdomain installs
        if (is_multisite() && is_subdomain_install() !== $backupMetadata->getSubdomainInstall()) {
            return false;
        }

        // If ABSPATH is different
        if (ABSPATH !== $backupMetadata->getAbsPath()) {
            return false;
        }

        $currentSiteURL = site_url();
        $backupSiteURL  = $backupMetadata->getSiteUrl();
        if ($currentSiteURL === $backupSiteURL) {
            return true;
        }

        $currentSiteURLWithoutScheme = preg_replace('#^https?://#', '', rtrim($currentSiteURL, '/'));
        $backupSiteURLWithoutScheme  = preg_replace('#^https?://#', '', rtrim($backupSiteURL, '/'));
        if ($currentSiteURLWithoutScheme === $backupSiteURLWithoutScheme) {
            $this->jobDataDto->setIsUrlSchemeMatched(false);
            return false;
        }

        return false;
    }

    /**
     * @return string
     */
    private function getJobTmpDirectory(): string
    {
        $dir = $this->directory->getTmpDirectory() . $this->jobDataDto->getId();
        $this->filesystem->mkdir($dir);

        return trailingslashit($dir);
    }

    /**
     * @return void
     */
    private function addDatabaseTasks()
    {
        $metadata = $this->jobDataDto->getBackupMetadata();
        if ($metadata->getIsMultipartBackup()) {
            foreach ($metadata->getMultipartMetadata()->getDatabaseParts() as $ignored) {
                $this->tasks[] = RestoreDatabaseTask::class;
            }
        } else {
            $this->tasks[] = RestoreDatabaseTask::class;
        }

        $this->addWordPressComTasks();
        $this->addMultisiteTasks();
        $this->addNetworkSiteTasks();

        $this->tasks[] = UpdateBackupsScheduleTask::class;
        $this->tasks[] = RenameDatabaseTask::class;
        $this->tasks[] = CleanupTmpTablesTask::class;
    }

    /**
     * @return void
     */
    protected function addNetworkSiteTasks()
    {
        // no-op
    }

    /**
     * @return void
     */
    private function addExtractFilesTasks()
    {
        $metadata = $this->jobDataDto->getBackupMetadata();
        if (!$metadata->getIsMultipartBackup()) {
            $this->tasks[] = ExtractFilesTask::class;
            return;
        }

        foreach ($metadata->getMultipartMetadata()->getFileParts() as $ignored) {
            $this->tasks[] = ExtractFilesTask::class;
        }
    }

    /**
     * @param BackupMetadata $metadata
     *
     * @return bool
     */
    private function isValidMetadata(BackupMetadata $metadata): bool
    {
        $extension = pathinfo($this->jobDataDto->getFile(), PATHINFO_EXTENSION);
        if ($extension !== 'sql') {
            return !empty($metadata->getHeaderStart());
        }

        return !empty($metadata->getMaxTableLength()) && !empty($metadata->getMultipartMetadata()->getDatabaseParts());
    }
}
