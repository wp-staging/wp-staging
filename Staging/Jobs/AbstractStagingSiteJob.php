<?php

namespace WPStaging\Staging\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\ErrorCode;
use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Staging\Interfaces\StagingSiteDtoInterface;
use WPStaging\Staging\PrefixOwnership;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyMuPluginsTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyPluginsTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyThemesTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyUploadsTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyWpAdminTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyWpContentTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyWpIncludesTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyWpRootDirectoriesTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyWpRootFilesTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\FilesystemScannerTask;




abstract class AbstractStagingSiteJob extends AbstractJob
{
 
    const FILESYSTEM_TASKS = [
        FilesystemScannerTask::class,
        CopyWpRootFilesTask::class,
        CopyWpAdminTask::class,
        CopyWpIncludesTask::class,
        CopyPluginsTask::class,
        CopyMuPluginsTask::class,
        CopyThemesTask::class,
        CopyUploadsTask::class,
        CopyWpContentTask::class,
        CopyWpRootDirectoriesTask::class,
    ];

 
    protected $tasks = [];

    protected function getJobTasks()
    {
        return $this->tasks;
    }

    protected function execute()
    {
        try {
            if ($this->isRewritingExistingStagingSite()) {
                $this->assertOwnsStagingSiteTables();
            }

            $response = $this->getResponse($this->currentTask->execute());
        } catch (\Exception $e) {
            $this->currentTask->getLogger()->critical($e->getMessage(), ['errorCode' => ErrorCode::fromThrowable($e)]);
            $response = $this->getResponse($this->currentTask->generateResponse(false));
        }

        return $response;
    }





    protected function isRewritingExistingStagingSite(): bool
    {
        return false;
    }

    protected function addFilesystemTasks()
    {
        $replacements = $this->getFilesystemTaskReplacements();
        foreach (self::FILESYSTEM_TASKS as $task) {
            $this->tasks[] = isset($replacements[$task]) ? $replacements[$task] : $task;
        }
    }






    protected function getFilesystemTaskReplacements(): array
    {
        return [];
    }




    private function assertOwnsStagingSiteTables()
    {
 
        $jobDataDto = $this->jobDataDto;
        WPStaging::make(PrefixOwnership::class)->assertCanModifyTables(
            $jobDataDto->getStagingSite()->getUsedPrefix(),
            $jobDataDto->getCloneId(),
            $jobDataDto->getStagingSite()
        );
    }
}
