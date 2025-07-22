<?php

namespace WPStaging\Framework\Job;

/**
 * It is used to cache the current running job data in a transient.
 * So it can be used for BackgroundLogger to push the events to the SSE stream.
 */
class JobTransientCache
{
    /**
     * This is the time in seconds that the job transient will be kept.
     * This is used to show the current job status in the UI.
     * @var int
     */
    const JOB_TRANSIENT_EXPIRY = 60 * 60 * 6; // 6 hours

    /**
     * This is the time in seconds that the job transient will be kept after the job is completed.
     * Instead of deleting the transient immediately, we reduce it expiry to 15 seconds, to every open SSE stream
     * can get the latest status of the job.
     * @var int
     */
    const JOB_TRANSIENT_EXPIRY_ON_COMPLETE = 15;

    /**
     * This is the transient key that will be used to store the current job data.
     * @var string
     */
    const TRANSIENT_CURRENT_JOB = 'wpstg_current_job';

    /**
     * @var string
     */
    const STATUS_RUNNING = 'running';

    /**
     * @var string
     */
    const STATUS_SUCCESS = 'success';

    /**
     * @var string
     */
    const STATUS_FAILED  = 'failed';

    /**
     * @var string
     */
    const STATUS_CANCELLED = 'cancelled';

    /**
     * @var string
     */
    const JOB_TYPE_BACKUP = 'Backup';

    /**
     * @var string
     */
    const JOB_TYPE_RESTORE = 'Restore';

    /**
     * @var string
     */
    const JOB_TYPE_CANCEL = 'Cancel';

    /**
     * @var string
     */
    const JOB_TYPE_PLUGINS_UPDATER = 'Plugins_Updater';

    /**
     * @var string
     */
    const JOB_TYPE_STAGING_CREATE = 'Staging_Create';

    /**
     * @var string
     */
    const JOB_TYPE_STAGING_DELETE = 'Staging_Delete';

    /**
     * @var string
     */
    const JOB_TYPE_PULL_PREPARE = 'Pull_Prepare';

    /**
     * @var string
     */
    const JOB_TYPE_PULL_RESTORE = 'Pull_Restore';

    /**
     * @var string[]
     */
    const CANCELABLE_JOBS = [
        self::JOB_TYPE_BACKUP,
        self::JOB_TYPE_RESTORE,
        self::JOB_TYPE_PULL_PREPARE,
        self::JOB_TYPE_PULL_RESTORE,
        self::JOB_TYPE_STAGING_CREATE,
    ];

    /**
     * @param string $jobId
     * @param string $jobTitle
     * @param string $jobType
     * @param string $queueId
     * @return void
     */
    public function startJob(string $jobId, string $jobTitle, string $jobType = 'job', string $queueId = '')
    {
        $jobData = [
            'jobId'   => $jobId,
            'title'   => $jobTitle,
            'type'    => $jobType,
            'status'  => self::STATUS_RUNNING,
            'start'   => time(),
            'queueId' => $queueId,
        ];

        delete_transient(self::TRANSIENT_CURRENT_JOB);
        set_transient(self::TRANSIENT_CURRENT_JOB, $jobData, self::JOB_TRANSIENT_EXPIRY);
    }

    /**
     * @param string $title
     * @return void
     */
    public function updateTitle(string $title)
    {
        $jobData = $this->getJob();
        $jobData['title'] = $title;

        set_transient(self::TRANSIENT_CURRENT_JOB, $jobData, self::JOB_TRANSIENT_EXPIRY);
    }

    /**
     * @return void
     */
    public function completeJob()
    {
        $this->stopJob(self::STATUS_SUCCESS);
    }

    /**
     * @return void
     */
    public function cancelJob(string $jobTitle)
    {
        $this->stopJob(self::STATUS_CANCELLED, $jobTitle);
    }

    /**
     * @return void
     */
    public function failJob()
    {
        $this->stopJob(self::STATUS_FAILED);
    }

    /**
     * @return array|null
     */
    public function getJob()
    {
        $jobData = get_transient(self::TRANSIENT_CURRENT_JOB);
        if (empty($jobData['jobId'])) {
            return null;
        }

        return $jobData;
    }

    public function getJobId(): string
    {
        $jobData = $this->getJob();
        if (empty($jobData['jobId'])) {
            return '';
        }

        return $jobData['jobId'];
    }

    public function getJobStatus(): string
    {
        $jobData = $this->getJob();
        if (empty($jobData['status'])) {
            return '';
        }

        return $jobData['status'];
    }

    /**
     * @param string $status
     * @param string $title
     * @return void
     */
    private function stopJob(string $status, string $title = '')
    {
        $jobData = $this->getJob();
        $jobData['status'] = $status;
        if (!empty($title)) {
            $jobData['title'] = $title;
        }

        // This will make sure to update the expiry as well if the status was already the same!
        delete_transient(self::TRANSIENT_CURRENT_JOB);
        set_transient(self::TRANSIENT_CURRENT_JOB, $jobData, self::JOB_TRANSIENT_EXPIRY_ON_COMPLETE);
    }
}
