<?php

namespace WPStaging\Framework\Job\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
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
            wp_send_json_error($e->getMessage(), $e->getCode());
        }

        /** @var JobCancel $job */
        $job = WPStaging::getInstance()->get(JobCancel::class);

        wp_send_json($job->prepareAndExecute());
    }
}
