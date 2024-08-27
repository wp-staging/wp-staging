<?php

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\Job\Jobs\JobCancel;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\AnalyticsEventDto;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Exception\ProcessLockedException;

class Cancel extends AbstractTemplateComponent
{
    /** @var ProcessLock */
    protected $processLock;

    public function __construct(TemplateEngine $templateEngine, ProcessLock $processLock)
    {
        $this->processLock = $processLock;

        parent::__construct($templateEngine);
    }

    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        try {
            $this->processLock->checkProcessLocked();
        } catch (ProcessLockedException $e) {
            wp_send_json_error($e->getMessage(), $e->getCode());
        }

        /** @var JobCancel $job */
        $job = WPStaging::getInstance()->get(JobCancel::class);

        if (isset($_POST['isInit']) && sanitize_text_field($_POST['isInit']) === 'yes') {
            $jobDataDto = WPStaging::getInstance()->getContainer()->make(JobDataDto::class);
            $jobDataDto->setInit(true);
            $jobDataDto->setId(substr(md5(mt_rand() . time()), 0, 12));
            $job->setJobDataDto($jobDataDto);

            $jobId = isset($_POST['jobIdBeingCancelled']) ? html_entity_decode(sanitize_text_field($_POST['jobIdBeingCancelled'])) : '';

            AnalyticsEventDto::enqueueCancelEvent($jobId);
        }

        wp_send_json($job->prepareAndExecute());
    }
}
