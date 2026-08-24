<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\BackgroundProcessing\Backup\PrepareBackup;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsGenericEvent;
use WPStaging\Framework\BackgroundProcessing\QueueProcessor;
use WPStaging\Framework\Job\Ajax\PrepareCancel;
use WPStaging\Framework\Job\JobTransientCache;

use function WPStaging\functions\debug_log;











abstract class AbstractBackgroundBackupRequest
{
 
    const STATUS_QUEUED = 'queued';

 
    const STATUS_RUNNING = 'running';

    const STATUS_COMPLETED = 'completed';

 
    const STATUS_FAILED = 'failed';






    const MAX_QUEUED_AGE_IN_SECONDS = 6 * HOUR_IN_SECONDS;





    const MAX_RUNNING_AGE_IN_SECONDS = DAY_IN_SECONDS;





    const MAX_TERMINAL_AGE_IN_SECONDS = HOUR_IN_SECONDS;

 
    const NUDGE_LOCK_IN_SECONDS = 10;

 
    private $state = null;




    abstract protected function getOptionName(): string;




    abstract protected function getNudgeTransientName(): string;




    abstract protected function getEventNames(): array;




    abstract protected function getAnalyticsGroup(): string;




    abstract protected function getLogContext(): string;





    public function getStatus(): string
    {
        $state = $this->read();

        return isset($state['status']) ? $state['status'] : '';
    }




    public function isPending(): bool
    {
        return in_array($this->getStatus(), [self::STATUS_QUEUED, self::STATUS_RUNNING], true);
    }

















    public function runWaitingWork()
    {
 
 
        if (!$this->isPending() || get_transient($this->getNudgeTransientName()) !== false) {
            return;
        }

        set_transient($this->getNudgeTransientName(), time(), self::NUDGE_LOCK_IN_SECONDS);

        try {
            WPStaging::make(QueueProcessor::class)->process();
        } catch (\Throwable $e) {
            debug_log($this->getLogContext() . ': could not run the queued backup. ' . $e->getMessage(), 'debug', false);
        }
    }




    public function markCompleted()
    {
        if ($this->getStatus() !== self::STATUS_RUNNING || !$this->isOwnBackupJob()) {
            return;
        }

        $this->transition(self::STATUS_COMPLETED, 'completed_at', $this->getEventName('completed'));
    }









    public function onBackgroundJobFailure($failure = [])
    {
        $reporter = is_array($failure) && isset($failure['jobTransientCache']) ? $failure['jobTransientCache'] : null;

        if ($this->getStatus() !== self::STATUS_RUNNING || !$this->isOwnBackupJob($reporter)) {
            return;
        }

        $this->markFailed(is_array($failure) && isset($failure['errorMessage']) ? (string)$failure['errorMessage'] : '');
    }





    public function markFailed(string $reason = '')
    {
        if (!$this->isPending()) {
            return;
        }

        if ($reason !== '') {
            debug_log($this->getLogContext() . ': queued backup failed. ' . $reason, 'debug', false);
        }

        $this->transition(self::STATUS_FAILED, 'failed_at', $this->getEventName('failed'), [], $this->getFailureReport($reason));
        $this->afterFailed($reason);
    }










    protected function cancelRunningBackup(): bool
    {
        if ($this->getStatus() !== self::STATUS_RUNNING || !$this->isOwnBackupJob()) {
            return false;
        }

        $response = WPStaging::make(PrepareCancel::class)->prepare([]);

        if ($response instanceof \WP_Error) {
            debug_log($this->getLogContext() . ': could not cancel the running backup. ' . $response->get_error_message(), 'debug', false);

            return false;
        }

        return true;
    }




    public function clear()
    {
        $this->state = [];
        delete_option($this->getOptionName());
    }







    protected function start(array $backupData): bool
    {
        $jobId = WPStaging::make(PrepareBackup::class)->prepare($backupData);

        if (is_wp_error($jobId)) {
            $this->markFailed($jobId->get_error_message());

            return false;
        }

        $this->transition(self::STATUS_RUNNING, 'started_at', $this->getEventName('started'), [
            'backup_job_id' => (string)$jobId,
        ]);

        $this->afterStarted();

        return true;
    }




    protected function afterStarted()
    {
    }





    protected function afterFailed(string $reason = '')
    {
    }










    protected function getFailureReport(string $reason): array
    {
        return [];
    }










    protected function read(): array
    {
        if ($this->state !== null) {
            return $this->state;
        }

        $state       = get_option($this->getOptionName(), []);
        $this->state = (is_array($state) && !empty($state['status'])) ? $state : [];

        if ($this->state !== [] && $this->isStale($this->state)) {
            $this->clear();
        }

        return $this->state;
    }





    protected function write(array $state)
    {
        $this->state = $state;
        update_option($this->getOptionName(), $state, false);
    }









    protected function transition(string $status, string $timeKey, string $event, array $extra = [], array $eventData = [])
    {
        $this->write(array_merge($this->read(), $extra, [
            'status' => $status,
            $timeKey => time(),
        ]));

        AnalyticsGenericEvent::logEvent($event, $this->getAnalyticsGroup(), $eventData);
    }





    private function getEventName(string $transition): string
    {
        $events = $this->getEventNames();

        return isset($events[$transition]) ? $events[$transition] : '';
    }





    private function isStale(array $state): bool
    {
        if ($state['status'] === self::STATUS_QUEUED) {
            return $this->isOlderThan($state, ['queued_at'], self::MAX_QUEUED_AGE_IN_SECONDS);
        }

        if ($state['status'] === self::STATUS_RUNNING) {
            return $this->isOlderThan($state, ['started_at', 'queued_at'], self::MAX_RUNNING_AGE_IN_SECONDS);
        }

        return $this->isOlderThan($state, ['completed_at', 'failed_at', 'started_at', 'queued_at'], self::MAX_TERMINAL_AGE_IN_SECONDS);
    }







    private function isOlderThan(array $state, array $timeKeys, int $maxAgeInSeconds): bool
    {
        foreach ($timeKeys as $timeKey) {
            if (!empty($state[$timeKey])) {
                return (int)$state[$timeKey] < time() - $maxAgeInSeconds;
            }
        }

        return true;
    }













    protected function isOwnBackupJob($reporter = null): bool
    {
        $state = $this->read();
        if (empty($state['backup_job_id'])) {
            return false;
        }

        $cache = $reporter instanceof JobTransientCache ? $reporter : WPStaging::make(JobTransientCache::class);
        $job   = $cache->getJob();

        return is_array($job) && isset($job['queueId']) && $job['queueId'] === $state['backup_job_id'];
    }
}
