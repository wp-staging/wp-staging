<?php

namespace WPStaging\Framework\Onboarding;

use WPStaging\Backup\BackgroundProcessing\Backup\PrepareBackup;
use WPStaging\Framework\BackgroundProcessing\QueueProcessor;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsGenericEvent;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Security\Auth;

use function WPStaging\functions\debug_log;

/**
 * A backup the user asked for while a staging site was still being created.
 *
 * The request is parked in an option and released to the ordinary background
 * processing pipeline once the staging site exists, which is what lets it
 * survive the user navigating away.
 */
class QueuedBackup
{
    /** Not autoloaded: read on WP STAGING screens and job requests only. */
    const OPTION_STATE = 'wpstg_onboarding_queued_backup';

    /** The user asked for it; the staging site is still being created. */
    const STATUS_QUEUED = 'queued';

    /** Handed to background processing. */
    const STATUS_RUNNING = 'running';

    const STATUS_COMPLETED = 'completed';

    /** Background processing could not run or the backup failed. */
    const STATUS_FAILED = 'failed';

    /**
     * A request says "back up right after this staging site", so it has no
     * business outliving the job it was parked against. Past this it is dropped
     * rather than fired — which also stops an abandoned request from attaching
     * itself to an unrelated staging site created later.
     */
    const MAX_QUEUED_AGE_IN_SECONDS = 6 * HOUR_IN_SECONDS;

    /**
     * Background processing is best effort: an action can be enqueued and never
     * dispatched, or die without reporting. Past this the request stops claiming
     * to be running rather than saying so forever.
     */
    const MAX_RUNNING_AGE_IN_SECONDS = DAY_IN_SECONDS;

    /** Held while a poll is running the queue, so the next one does not pile onto it. */
    const TRANSIENT_NUDGE_LOCK = 'wpstg_onboarding_backup_nudge';

    const NUDGE_LOCK_IN_SECONDS = 10;

    const EVENT_STARTED   = 'queued_backup_started';
    const EVENT_COMPLETED = 'queued_backup_completed';
    const EVENT_FAILED    = 'queued_backup_failed';

    /** @var array|null */
    private $state = null;

    /**
     * Parks a backup request against the staging job that is currently running.
     *
     * @param string $stagingJobId Identifier of the staging job being created.
     * @return bool Whether this call created the request. False means one was
     *              already parked or running, which is what makes a double
     *              click, or a reload that replays the request, a no-op.
     */
    public function queue(string $stagingJobId): bool
    {
        if ($this->isPending()) {
            return false;
        }

        $this->write([
            'status'         => self::STATUS_QUEUED,
            'staging_job_id' => $stagingJobId,
            'queued_at'      => time(),
            'backup_job_id'  => '',
        ]);

        return true;
    }

    /**
     * @return string One of the STATUS_* constants, or an empty string when
     *                nothing is parked.
     */
    public function getStatus(): string
    {
        $state = $this->read();

        return isset($state['status']) ? $state['status'] : '';
    }

    /**
     * Runs the queue now rather than when something else happens to.
     *
     * Background processing starts work by asking the server to call itself.
     * Where that request cannot be made — a host that will not resolve its own
     * address, a firewall, a port the site is not reachable on from inside —
     * nothing dispatches the parked backup, and it waits for the stall detector
     * to notice a minute later. The screen says the backup has started, and for
     * that minute nothing is running it.
     *
     * This is the same inline processing that recovery performs, on the request
     * that is already watching, and only while this run's own backup is the
     * thing waiting on it. The lock is because that screen polls every couple of
     * seconds and processing a slice takes longer than that.
     *
     * @return void
     */
    public function runWaitingWork()
    {
        if (!$this->isPending() || get_site_transient(self::TRANSIENT_NUDGE_LOCK) !== false) {
            return;
        }

        set_site_transient(self::TRANSIENT_NUDGE_LOCK, time(), self::NUDGE_LOCK_IN_SECONDS);

        try {
            WPStaging::make(QueueProcessor::class)->process();
        } catch (\Throwable $e) {
            debug_log('WP STAGING Onboarding: could not run the queued backup. ' . $e->getMessage(), 'debug', false);
        }
    }

    /**
     * The status as the screen that is waiting on it needs to see it.
     *
     * A request that completes the first run is deleted along with the journey
     * it completed, so its success has to be read from the reason the journey
     * ended. Queued and running are one state to a watcher: still going.
     *
     * @return string STATUS_RUNNING, STATUS_COMPLETED, STATUS_FAILED, or empty.
     */
    public function getReportedStatus(): string
    {
        if ($this->isPending()) {
            return self::STATUS_RUNNING;
        }

        $reason = WPStaging::make(OnboardingJourney::class)->getCompletionReason();

        return $reason === OnboardingJourney::REASON_TWO_CAPABILITIES ? self::STATUS_COMPLETED : $this->getStatus();
    }

    /**
     * @return bool Whether a request is waiting or currently being executed.
     */
    public function isPending(): bool
    {
        return in_array($this->getStatus(), [self::STATUS_QUEUED, self::STATUS_RUNNING], true);
    }

    /**
     * Releases the parked request now that a staging site exists.
     *
     * @action wpstg_staging_site_created
     */
    public function startAfterStagingCreated()
    {
        if ($this->getStatus() !== self::STATUS_QUEUED) {
            return;
        }

        $jobId = WPStaging::make(PrepareBackup::class)->prepare(['isAutomatedBackup' => false]);

        if (is_wp_error($jobId)) {
            $this->markFailed($jobId->get_error_message());

            return;
        }

        $this->transition(self::STATUS_RUNNING, 'started_at', self::EVENT_STARTED, [
            'backup_job_id' => (string)$jobId,
        ]);

        WPStaging::make(OnboardingJourney::class)->startAction();
    }

    /**
     * @action wpstg.backup.created
     */
    public function markCompleted()
    {
        if ($this->getStatus() !== self::STATUS_RUNNING || !$this->isOwnBackupJob()) {
            return;
        }

        $this->transition(self::STATUS_COMPLETED, 'completed_at', self::EVENT_COMPLETED);
    }

    /**
     * The hook fires for every background job. A request that is still queued belongs
     * to the staging job that failed, not to this backup, and marking it failed there
     * would strand it: discard() only clears a queued request.
     *
     * @action wpstg_background_job_failure
     * @param array $failure The hook payload, with an `errorMessage` key.
     */
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
            debug_log('WP STAGING Onboarding: queued backup failed. ' . $reason, 'debug', false);
        }

        $this->transition(self::STATUS_FAILED, 'failed_at', self::EVENT_FAILED);

        WPStaging::make(OnboardingJourney::class)->cancelSecondAction();
    }

    /**
     * The cancel and error endpoints this listens on are dispatched to every
     * logged-in user before their own handlers authorize the request, so this
     * has to authorize for itself.
     */
    public function discardOnStagingRequest()
    {
        if (!WPStaging::make(Auth::class)->isAuthenticatedRequest('', 'manage_options')) {
            return;
        }

        $this->discard();
    }

    /**
     * Drops a request whose staging job never succeeded, so a cancelled or failed
     * staging site does not silently produce a backup anyway.
     */
    public function discard()
    {
        if ($this->getStatus() !== self::STATUS_QUEUED) {
            return;
        }

        $this->clear();

        WPStaging::make(OnboardingJourney::class)->cancelSecondAction();
    }

    public function clear()
    {
        $this->state = [];
        delete_option(self::OPTION_STATE);
    }

    /**
     * Reads the parked request, dropping it when it has gone stale.
     *
     * Deliberately self-healing rather than pure: the request is only ever read
     * on a page render or a job hook, and neither has anywhere else to notice
     * that a record has expired. A caller that must not write should not use it.
     */
    private function read(): array
    {
        if ($this->state !== null) {
            return $this->state;
        }

        $state       = get_option(self::OPTION_STATE, []);
        $this->state = (is_array($state) && !empty($state['status'])) ? $state : [];

        if ($this->state !== [] && $this->isStale($this->state)) {
            $this->clear();
        }

        return $this->state;
    }

    private function transition(string $status, string $timeKey, string $event, array $extra = [])
    {
        $this->write(array_merge($this->read(), $extra, [
            'status' => $status,
            $timeKey => time(),
        ]));

        AnalyticsGenericEvent::logEvent($event, FreeOnboarding::ANALYTICS_GROUP);
    }

    private function write(array $state)
    {
        $this->state = $state;
        update_option(self::OPTION_STATE, $state, false);
    }

    private function isStale(array $state): bool
    {
        if ($state['status'] === self::STATUS_QUEUED) {
            return $this->isOlderThan($state, ['queued_at'], self::MAX_QUEUED_AGE_IN_SECONDS);
        }

        if ($state['status'] === self::STATUS_RUNNING) {
            return $this->isOlderThan($state, ['started_at', 'queued_at'], self::MAX_RUNNING_AGE_IN_SECONDS);
        }

        return false;
    }

    /**
     * @param string[] $timeKeys Checked in order; the first one present wins.
     * @return bool True when the record carries no usable timestamp at all.
     */
    private function isOlderThan(array $state, array $timeKeys, int $maxAgeInSeconds): bool
    {
        foreach ($timeKeys as $timeKey) {
            if (!empty($state[$timeKey])) {
                return (int)$state[$timeKey] < time() - $maxAgeInSeconds;
            }
        }

        return true;
    }

    /**
     * Whether the backup that just reported is the one this class handed over.
     *
     * The completion and failure hooks fire for every backup on the site —
     * scheduled, WP-CLI, Remote Sync, or one the user started by hand — so
     * without this a foreign backup finishing first would close this request.
     *
     * @param JobTransientCache|null $reporter The cache the reporting hook read
     *        from. Re-resolving it would ask the shared transient, which may
     *        already have moved on to another job by the time a failure lands.
     */
    private function isOwnBackupJob($reporter = null): bool
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
