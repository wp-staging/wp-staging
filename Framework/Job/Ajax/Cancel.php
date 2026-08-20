<?php

namespace WPStaging\Framework\Job\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Job\Jobs\JobCancel;
use WPStaging\Framework\Traits\JobResponseTrait;

class Cancel extends AbstractTemplateComponent
{
    use JobResponseTrait;

 
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
            $this->processLock->lockProcess();
        } catch (ProcessLockedException $e) {
            if ($this->shouldContinuePollingWhileLocked()) {
                wp_send_json([
                    'isRunning' => true,
                ]);
            }

            wp_send_json_error($e->getMessage(), $e->getCode());
        }

 
        $job = WPStaging::getInstance()->get(JobCancel::class);

        $this->sendJobResponse($job);
    }






    protected function shouldContinuePollingWhileLocked(): bool
    {
 
        $jobTransientCache = WPStaging::make(JobTransientCache::class);
        return $jobTransientCache->getJobStatus() === JobTransientCache::STATUS_CANCELLED;
    }
}
