<?php

namespace WPStaging\Framework\Job\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Staging\Jobs\StagingJobsProvider;

class Status extends AbstractTemplateComponent
{
    public function ajaxProcess()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $job = $this->getJobInstance();
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
