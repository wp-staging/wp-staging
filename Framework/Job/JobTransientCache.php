<?php

namespace WPStaging\Framework\Job;





class JobTransientCache
{






    const JOB_TRANSIENT_EXPIRY = 60 * 6; 







    const JOB_TRANSIENT_EXPIRY_ON_COMPLETE = 15;





    const LAST_JOB_OUTCOME_EXPIRY = 5 * 60;





    const TRANSIENT_CURRENT_JOB = 'wpstg_current_job';




    const TRANSIENT_LAST_JOB_OUTCOME = 'wpstg_last_job_outcome';




    const STATUS_RUNNING = 'running';




    const STATUS_SUCCESS = 'success';




    const STATUS_FAILED  = 'failed';




    const STATUS_CANCELLED = 'cancelled';




    const STATUS_STALED = 'staled';




    const SEVERITY_ERROR = 'error';





    const SEVERITY_NOTICE = 'notice';




    const JOB_TYPE_BACKUP = 'Backup';




    const JOB_TYPE_RESTORE = 'Restore';




    const JOB_TYPE_EXTRACT = 'Extract';




    const JOB_TYPE_CANCEL = 'Cancel';




    const JOB_TYPE_PLUGINS_UPDATER = 'Plugins_Updater';




    const JOB_TYPE_STAGING_CREATE = 'Staging_Create';




    const JOB_TYPE_STAGING_UPDATE = 'Staging_Update';




    const JOB_TYPE_STAGING_RESET = 'Staging_Reset';




    const JOB_TYPE_STAGING_PUSH = 'Staging_Push';




    const JOB_TYPE_STAGING_DELETE = 'Staging_Delete';




    const JOB_TYPE_PULL_PREPARE = 'Pull_Prepare';




    const JOB_TYPE_PULL_RESTORE = 'Pull_Restore';




    const JOB_TYPE_REMOTE_UPLOAD = 'Remote_Upload';




    const CANCELABLE_JOBS = [
        self::JOB_TYPE_BACKUP,
        self::JOB_TYPE_RESTORE,
        self::JOB_TYPE_EXTRACT,
        self::JOB_TYPE_PULL_PREPARE,
        self::JOB_TYPE_PULL_RESTORE,
        self::JOB_TYPE_STAGING_CREATE,
        self::JOB_TYPE_STAGING_UPDATE,
        self::JOB_TYPE_STAGING_RESET,
        self::JOB_TYPE_REMOTE_UPLOAD,
    ];








    public function startJob(string $jobId, string $jobTitle, string $jobType = 'job', string $queueId = '')
    {
        $jobData = [
            'jobId'     => $jobId,
            'title'     => $jobTitle,
            'type'      => $jobType,
            'status'    => self::STATUS_RUNNING,
            'startedAt' => time(),
            'updatedAt' => time(),
            'queueId'   => $queueId,
            'message'   => '',
        ];

        delete_transient(self::TRANSIENT_CURRENT_JOB);
        set_transient(self::TRANSIENT_CURRENT_JOB, $jobData, self::JOB_TRANSIENT_EXPIRY);
    }







    public function markAsPreInitialized()
    {
        $jobData = $this->getJob();
        if ($jobData === null) {
            return;
        }

        $jobData['preInitAt'] = time();
        delete_transient(self::TRANSIENT_CURRENT_JOB);
        set_transient(self::TRANSIENT_CURRENT_JOB, $jobData, self::JOB_TRANSIENT_EXPIRY);
    }





    public function updateTitle(string $title)
    {
        $jobData = $this->getJob();
        $jobData['title']     = $title;
        $jobData['updatedAt'] = time();

        set_transient(self::TRANSIENT_CURRENT_JOB, $jobData, self::JOB_TRANSIENT_EXPIRY);
    }




    public function completeJob()
    {
        $this->stopJob(self::STATUS_SUCCESS);
    }




    public function cancelJob(string $jobTitle)
    {
        $this->stopJob(self::STATUS_CANCELLED, $jobTitle);
    }










    public function failJob(string $title = '', string $message = '', string $severity = '')
    {
        $this->stopJob(self::STATUS_FAILED, $title, $message, $severity);
    }




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





    public function findJobById(string $jobId)
    {
        if ($jobId === '') {
            return null;
        }

        $job = $this->getJob();
        if (!empty($job['jobId']) && hash_equals((string) $job['jobId'], $jobId)) {
            return $job;
        }

        $lastFinishedJob = get_transient(self::TRANSIENT_LAST_JOB_OUTCOME);
        if (!empty($lastFinishedJob['jobId']) && hash_equals((string) $lastFinishedJob['jobId'], $jobId)) {
            return $lastFinishedJob;
        }

        return null;
    }




    public function update()
    {
        $jobData = $this->getJob();
        $jobData['updatedAt'] = time();

        delete_transient(self::TRANSIENT_CURRENT_JOB);
        set_transient(self::TRANSIENT_CURRENT_JOB, $jobData, self::JOB_TRANSIENT_EXPIRY);
    }







    private function stopJob(string $status, string $title = '', string $message = '', string $severity = '')
    {
        $jobData = $this->getJob();
        $jobData['status']    = $status;
        $jobData['updatedAt'] = time();
        if (!empty($title)) {
            $jobData['title'] = $title;
        }

        if (!empty($message)) {
            $jobData['message'] = $message;
        }

 
 
 
 
        if ($severity !== '') {
            $jobData['severity'] = $severity;
        }

 
        delete_transient(self::TRANSIENT_CURRENT_JOB);
        set_transient(self::TRANSIENT_CURRENT_JOB, $jobData, self::JOB_TRANSIENT_EXPIRY_ON_COMPLETE);

        if (empty($jobData['jobId'])) {
            return;
        }

        set_transient(self::TRANSIENT_LAST_JOB_OUTCOME, $jobData, self::LAST_JOB_OUTCOME_EXPIRY);
    }
}
