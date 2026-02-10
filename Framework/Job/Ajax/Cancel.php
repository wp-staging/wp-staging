<?php

namespace WPStaging\Framework\Job\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Job\Jobs\JobCancel;

class Cancel extends AbstractTemplateComponent
{
    /** @var ProcessLock */
    protected $processLock;

    public function __construct(TemplateEngine $templateEngine, ProcessLock $processLock)
    {
        $this->processLock = $processLock;

        parent::__construct($templateEngine);
    }

    public function ajaxProcess()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        try {
            $this->processLock->checkProcessLocked();
        } catch (ProcessLockedException $e) {
            if ($this->shouldContinuePollingWhileLocked()) {
                wp_send_json([
                    'isRunning' => true,
                ]);
            }

            wp_send_json_error($e->getMessage(), $e->getCode());
        }

        /** @var JobCancel $job */
        $job = WPStaging::getInstance()->get(JobCancel::class);

        wp_send_json($job->prepareAndExecute());
    }

    /**
     * When cancellation is already requested, continue polling instead of treating lock errors as fatal.
     *
     * @return bool
     */
    protected function shouldContinuePollingWhileLocked(): bool
    {
        /** @var JobTransientCache $jobTransientCache */
        $jobTransientCache = WPStaging::make(JobTransientCache::class);
        return $jobTransientCache->getJobStatus() === JobTransientCache::STATUS_CANCELLED;
    }
}
