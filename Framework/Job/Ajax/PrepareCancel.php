<?php

namespace WPStaging\Framework\Job\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\AnalyticsEventDto;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Dto\JobCancelDataDto;
use WPStaging\Framework\Job\Jobs\JobCancel;
use WPStaging\Framework\Job\JobTransientCache;

class PrepareCancel extends PrepareJob
{
    /**
     * @var string
     */
    const ACTION_JOB_CANCEL = 'wpstg.job_cancel';

    /** @var JobCancelDataDto */
    private $jobDataDto;

    /** @var JobCancel */
    private $jobCancel;

    /**
     * @param array|null $data
     * @return void
     */
    public function ajaxPrepare($data)
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            wp_send_json_error(null, 401);
        }

        $response = $this->prepare($data);

        if ($response instanceof \WP_Error) {
            wp_send_json_error($response->get_error_message(), $response->get_error_code());
        } else {
            wp_send_json_success();
        }
    }

    /**
     * @param array|null $data
     * @return array|\WP_Error
     */
    public function prepare($data = null)
    {
        try {
            $this->cancelCurrentRunningJob();
            $sanitizedData = $this->setupInitialData($data);
        } catch (\Exception $e) {
            return new \WP_Error(400, $e->getMessage());
        }

        AnalyticsEventDto::enqueueCancelEvent($sanitizedData['jobIdBeingCancelled']);

        return $sanitizedData;
    }

    /**
     * @param array|null $data
     * @return array
     */
    private function setupInitialData($data): array
    {
        $sanitizedData = $this->validateAndSanitizeData($data);
        $this->clearCacheFolder();

        // Lazy-instantiation to avoid process-lock checks conflicting with running processes.
        $services = WPStaging::getInstance()->getContainer();
        /** @var JobCancelDataDto */
        $this->jobDataDto = $services->get(JobCancelDataDto::class);
        /** @var JobCancel */
        $this->jobCancel = $services->get(JobCancel::class);

        $this->jobDataDto->hydrate($sanitizedData);
        $this->jobDataDto->setInit(true);
        $this->jobDataDto->setFinished(false);
        $this->jobDataDto->setStartTime(time());
        $this->jobDataDto->setId(substr(md5(mt_rand() . time()), 0, 12));

        $this->jobCancel->getTransientCache()->cancelJob($this->getJobTitle($this->jobDataDto->getType()));
        $this->jobCancel->setJobDataDto($this->jobDataDto);

        return $sanitizedData;
    }

    /**
     * This is an abstract method and used by BG Processor for preparing cli jobs,
     * but data is always fixed for canceling jobs, we get it from the transient cache.
     * So method name here is misleading
     * @param array|null $data
     * @return array
     */
    public function validateAndSanitizeData($data): array
    {
        // data is always fixed for canceling jobs, we get it from the transient cache
        $jobData = $this->getJobData();
        $data = [
            'type'                => $jobData['type'],
            'jobIdBeingCancelled' => $jobData['jobId'],
        ];

        return $data;
    }

    /**
     * @return JobCancel|null The current reference to the Cancel Job, if any.
     */
    public function getJob()
    {
        return $this->jobCancel;
    }

    /**
     * Persists the current Job Cancel status.
     *
     * @return bool Whether the current Job status was persisted or not.
     */
    public function persist(): bool
    {
        if (!$this->jobCancel instanceof JobCancel) {
            return false;
        }

        $this->jobCancel->persist();

        return true;
    }

    /**
     * @param JobTransientCache|null $jobTransientCache
     * @return array
     * @throws \Exception
     */
    private function getJobData($jobTransientCache = null): array
    {
        if ($jobTransientCache === null) {
            $jobTransientCache = WPStaging::make(JobTransientCache::class);
        }

        $jobData = $jobTransientCache->getJob();
        if (empty($jobData['status']) || $jobData['status'] !== JobTransientCache::STATUS_RUNNING) {
            throw new \Exception('Job is not running!');
        }

        return $jobData;
    }

    private function getJobTitle(string $type): string
    {
        switch ($type) {
            case JobTransientCache::JOB_TYPE_BACKUP:
                return esc_html__('Canceling Backup', 'wp-staging');
            case JobTransientCache::JOB_TYPE_PULL_PREPARE:
                return esc_html__('Canceling Pull', 'wp-staging');
            case JobTransientCache::JOB_TYPE_PULL_RESTORE:
                return esc_html__('Canceling Pull', 'wp-staging');
            default:
                return esc_html__('Canceling', 'wp-staging');
        }
    }

    /**
     * @return void
     */
    private function cancelCurrentRunningJob()
    {
        /**
         * lazy loaded if we only need it for background jobs
         * @var JobTransientCache
         */
        $jobTransientCache = WPStaging::make(JobTransientCache::class);
        $jobData           = $this->getJobData($jobTransientCache);
        // Check if the job is running
        if ($jobData['status'] !== JobTransientCache::STATUS_RUNNING) {
            return;
        }

        Hooks::callInternalHook(self::ACTION_JOB_CANCEL, [
            'jobTransientCache' => $jobTransientCache,
        ]);

        $queueId = $jobData['queueId'];
        if (empty($queueId)) {
            return;
        }

        /**
         * @var Queue
         */
        $queue = WPStaging::make(Queue::class);
        $queue->cancelJob($queueId);
    }
}
