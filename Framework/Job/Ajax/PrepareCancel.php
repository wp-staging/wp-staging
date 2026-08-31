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



    const ACTION_JOB_CANCEL = 'wpstg.job_cancel';

 
    private $jobDataDto;

 
    private $jobCancel;





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






    public function prepare($data = null)
    {
        $data = is_array($data) ? $data : [];

        try {
            $data          = $this->cancelCurrentRunningJob($data);
            $sanitizedData = $this->setupInitialJob($data);
        } catch (\Exception $e) {
            return new \WP_Error(400, $e->getMessage());
        }

        AnalyticsEventDto::enqueueCancelEvent($sanitizedData['jobIdBeingCancelled']);

        return $sanitizedData;
    }





    protected function setupInitialData($data): array
    {
        $sanitizedData = $this->validateAndSanitizeData($data);
        $this->clearCacheFolder();

 
        $services = WPStaging::getInstance()->getContainer();
 
        $this->jobDataDto = $services->get(JobCancelDataDto::class);
 
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












    public function validateAndSanitizeData($data): array
    {
        if (is_array($data) && !empty($data['jobIdBeingCancelled'])) {
            return [
                'type'                => isset($data['type']) ? $data['type'] : '',
                'jobIdBeingCancelled' => $data['jobIdBeingCancelled'],
            ];
        }

 
        $jobData = $this->getJobData();
        $data = [
            'type'                => $jobData['type'],
            'jobIdBeingCancelled' => $jobData['jobId'],
        ];

        return $data;
    }




    public function getJob()
    {
        return $this->jobCancel;
    }






    public function persist(): bool
    {
        if (!$this->jobCancel instanceof JobCancel) {
            return false;
        }

        $this->jobCancel->persist();

        return true;
    }






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












    private function cancelCurrentRunningJob(array $data): array
    {




        $jobTransientCache = WPStaging::make(JobTransientCache::class);
        $jobData           = $this->getJobData($jobTransientCache);

        $this->assertTargetsExpectedJob($jobData, $data);

        Hooks::callInternalHook(self::ACTION_JOB_CANCEL, [
            'jobTransientCache' => $jobTransientCache,
        ]);

        if (empty($data['jobIdBeingCancelled'])) {
            $data['type']                = $jobData['type'];
            $data['jobIdBeingCancelled'] = $jobData['jobId'];
        }

        $queueId = $jobData['queueId'];
        if (empty($queueId)) {
            return $data;
        }




        $queue = WPStaging::make(Queue::class);
        $queue->cancelJob($queueId);

        return $data;
    }







    private function assertTargetsExpectedJob(array $jobData, array $data)
    {
        foreach (['jobId' => 'expectedJobId', 'queueId' => 'expectedQueueId'] as $jobKey => $expectedKey) {
            $expected = isset($data[$expectedKey]) ? (string)$data[$expectedKey] : '';
            if ($expected === '') {
                continue;
            }

            $running = isset($jobData[$jobKey]) ? (string)$jobData[$jobKey] : '';
            if ($running !== $expected) {
                throw new \Exception('Cancellation target changed; nothing cancelled.');
            }
        }
    }
}
