<?php

namespace WPStaging\Framework\Job\BackgroundProcessing;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Job\Ajax\PrepareCancel as AjaxPrepareCancel;
use WPStaging\Framework\BackgroundProcessing\Job\PrepareJob;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\Job\Exception\CancelPreparationException;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Job\Jobs\JobCancel;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Utils\Times;

use function WPStaging\functions\debug_log;







class PrepareCancel extends PrepareJob
{






    public function __construct(AjaxPrepareCancel $ajaxPrepareCancel, Queue $queue, ProcessLock $processLock, Times $times)
    {
        parent::__construct($ajaxPrepareCancel, $queue, $processLock, $times);
    }




    public function getDefaultDataConfiguration(): array
    {
        return [
            'isInit' => true,
        ];
    }


















    public function act($args)
    {
        if (!empty($args['isInit']) && !$this->targetsRunningJob($args)) {
            return $this->skipCancellation('Cancellation target changed; nothing cancelled.');
        }

        try {
            return parent::act($args);
        } catch (CancelPreparationException $e) {
            WPStaging::make(ProcessLock::class)->unlockProcess();

            return $this->skipCancellation($e->getMessage());
        }
    }





    private function skipCancellation(string $reason): \WP_Error
    {
        debug_log('[Background] Cancel skipped: ' . $reason, 'info', false);

        return new \WP_Error(499, $reason);
    }





    protected function targetsRunningJob(array $args): bool
    {
        $expectedQueueId = isset($args['expectedQueueId']) ? (string)$args['expectedQueueId'] : '';
        $expectedJobId   = isset($args['expectedJobId']) ? (string)$args['expectedJobId'] : '';
        if ($expectedQueueId === '' && $expectedJobId === '') {
            return true;
        }

        $job = WPStaging::make(JobTransientCache::class)->getJob();
        if (!is_array($job)) {
            return false;
        }

        $status = isset($job['status']) ? $job['status'] : '';
        if ($status !== JobTransientCache::STATUS_RUNNING) {
            return false;
        }

        return $this->matches($job, 'queueId', $expectedQueueId) && $this->matches($job, 'jobId', $expectedJobId);
    }







    private function matches(array $job, string $key, string $expected): bool
    {
        if ($expected === '') {
            return true;
        }

        $running = isset($job[$key]) ? (string)$job[$key] : '';

        return $running !== '' && $running === $expected;
    }







    public function prepareCleanup(array $cancelledJob)
    {
        return $this->prepare(array_merge($cancelledJob, ['isInit' => false]));
    }






    protected function maybeInitJob(array $args)
    {
        if (empty($args['isInit'])) {
            $this->job = WPStaging::make(JobCancel::class);

            return;
        }

        debug_log('[Background] Initiating Cancel Job', 'info', false);
        $prepareCancel = WPStaging::make(AjaxPrepareCancel::class);
        $prepared      = $prepareCancel->prepare($args);
        if ($prepared instanceof \WP_Error) {
            throw new CancelPreparationException($prepared->get_error_message());
        }

        $this->job = $prepareCancel->getJob();
    }
}
