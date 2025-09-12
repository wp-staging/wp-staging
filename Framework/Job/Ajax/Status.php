<?php

namespace WPStaging\Framework\Job\Ajax;

use WPStaging\Backup\Job\JobBackupProvider;
use WPStaging\Backup\Job\JobRestoreProvider;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Pro\Backup\Job\JobRemoteUploadProvider;
use WPStaging\Staging\Jobs\StagingJobsProvider;

class Status extends AbstractTemplateComponent
{
    /**
     * @return void
     */
    public function ajaxProcess()
    {
        if (!$this->canRenderAjax()) {
            wp_send_json_error(null, 401);
        }

        $job = $this->getJobInstance();
        if ($job->getIsCancelled()) {
            wp_send_json([
                'status' => "JOB_CANCEL",
            ]);

            // die is already called by wp_send_json
            // but we are still calling return here
            // to ensure no further code is executed
            return;
        }

        $job->prepare();

        wp_send_json($job->getJobDataDto());
    }

    /**
     * @return AbstractJob
     */
    private function getJobInstance(): AbstractJob
    {
        $jobType = trim($this->getJobType());
        if (strpos($jobType, 'Staging_') === 0) {
            return WPStaging::make(StagingJobsProvider::class)->getJob($jobType);
        }

        if ($jobType === JobTransientCache::JOB_TYPE_BACKUP) {
            return WPStaging::make(JobBackupProvider::class)->getJob();
        }

        if ($jobType === JobTransientCache::JOB_TYPE_RESTORE) {
            return WPStaging::make(JobRestoreProvider::class)->getJob();
        }

        if ($jobType === JobTransientCache::JOB_TYPE_REMOTE_UPLOAD) {
            return WPStaging::make(JobRemoteUploadProvider::class)->getJob();
        }

        throw new \Exception('Not a valid job type!');
    }

    private function getJobType(): string
    {
        if (empty($_POST['type'])) {
            throw new \Exception('Job Type Missing!');
        }

        return sanitize_text_field($_POST['type']);
    }
}
