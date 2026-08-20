<?php

namespace WPStaging\Framework\Job\Ajax;

use WPStaging\Backup\Job\JobBackupProvider;
use WPStaging\Backup\Job\JobRestoreProvider;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Backup\Job\JobExtractProvider;
use WPStaging\Staging\Jobs\StagingJobsProvider;

class Status extends AbstractTemplateComponent
{



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

 
 
 
            return;
        }

        $job->prepare();

        wp_send_json($job->getJobDataDto());
    }






    protected function getPushJob(): AbstractJob
    {
        throw new \Exception('Push is available only in PRO version!');
    }






    protected function getRemoteUploadJob(): AbstractJob
    {
        throw new \Exception('Remote Upload is available only in PRO version!');
    }




    private function getJobInstance(): AbstractJob
    {
        $jobType = trim($this->getJobType());
        if ($jobType === JobTransientCache::JOB_TYPE_STAGING_PUSH) {
            return $this->getPushJob();
        }

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
            return $this->getRemoteUploadJob();
        }

        if ($jobType === JobTransientCache::JOB_TYPE_EXTRACT) {
            return WPStaging::make(JobExtractProvider::class)->getJob();
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
