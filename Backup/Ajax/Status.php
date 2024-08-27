<?php

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\Job\JobBackupProvider;
use WPStaging\Backup\Job\JobRestoreProvider;
use WPStaging\Backup\Job\Jobs\JobBackup;
use WPStaging\Backup\Job\Jobs\JobRestore;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;

class Status extends AbstractTemplateComponent
{
    const TYPE_STATUS = 'restore';

    public function render()
    {
        if (! $this->canRenderAjax()) {
            return;
        }

        $job = $this->getJob();
        $job->prepare();

        wp_send_json($job->getJobDataDto());
    }

    /**
     * @return JobBackup|JobRestore
     */
    private function getJob()
    {
        if (!empty($_GET['process']) && sanitize_text_field($_GET['process']) === self::TYPE_STATUS) {
            return WPStaging::make(JobRestoreProvider::class)->getJob();
        } else {
            return WPStaging::make(JobBackupProvider::class)->getJob();
        }
    }
}
