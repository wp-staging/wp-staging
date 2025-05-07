<?php

namespace WPStaging\Framework\Job\BackgroundProcessing;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Job\Ajax\PrepareCancel as AjaxPrepareCancel;
use WPStaging\Framework\BackgroundProcessing\Job\PrepareJob;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\Job\Jobs\JobCancel;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Utils\Times;

use function WPStaging\functions\debug_log;

/**
 * Class PrepareCancel
 * Prepares a Cancel to be executed using Background Processing.
 *
 * @package WPStaging\Framework\Job\BackgroundProcessing
 */
class PrepareCancel extends PrepareJob
{
    /**
     * @param AjaxPrepareCancel $ajaxPrepareCancel
     * @param Queue             $queue
     * @param ProcessLock       $processLock
     * @param Times             $times
     */
    public function __construct(AjaxPrepareCancel $ajaxPrepareCancel, Queue $queue, ProcessLock $processLock, Times $times)
    {
        parent::__construct($ajaxPrepareCancel, $queue, $processLock, $times);
    }

    /**
     * Returns the default data configuration that will be used to prepare a cancel job
     */
    public function getDefaultDataConfiguration(): array
    {
        return [
            'isInit' => true,
        ];
    }

    protected function maybeInitJob(array $args)
    {
        if ($args['isInit']) {
            debug_log('[Background] Initiating Cancel Job', 'info', false);
            $prepareCancel = WPStaging::make(AjaxPrepareCancel::class);
            $prepareCancel->prepare($args);
            $this->job = $prepareCancel->getJob();
        } else {
            $this->job = WPStaging::make(JobCancel::class)->getJob();
        }
    }
}
