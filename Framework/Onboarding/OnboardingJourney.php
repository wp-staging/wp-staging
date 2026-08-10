<?php

namespace WPStaging\Framework\Onboarding;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsGenericEvent;
use WPStaging\Framework\Security\Auth;

/**
 * The lifecycle of a first run, in options so it survives reloads and job hooks.
 *
 * Picking a card is not finishing: only completeOnExplicitExit() and a second
 * completed capability end it.
 */
class OnboardingJourney
{
    const OPTION_STATE = 'wpstg_onboarding_journey';

    /** Holds the completion reason; its presence ends the first run. */
    const OPTION_COMPLETED = 'wpstg_onboarding_completed';

    /**
     * Set by the footer's restart link. An admin asking for the first run back
     * has said what the eligibility rules are there to infer, so it overrides
     * them — otherwise a site that already has a staging site could never
     * replay the journey it just finished.
     */
    const OPTION_RESTARTED = 'wpstg_onboarding_restarted';

    const STEP_SELECT  = 'select';
    const STEP_RUNNING = 'running';
    const STEP_NEXT    = 'next';
    const STEP_DONE    = 'done';

    const POSITION_FIRST  = 'first';
    const POSITION_SECOND = 'second';

    const CAPABILITY_STAGING = 'staging';
    const CAPABILITY_BACKUP  = 'backup';
    const CAPABILITIES       = [self::CAPABILITY_STAGING, self::CAPABILITY_BACKUP];

    const REASON_SKIPPED_INITIAL     = 'skipped_initial';
    const REASON_DONE_AFTER_FIRST    = 'done_after_first_capability';
    const REASON_TWO_CAPABILITIES    = 'two_capabilities_completed';
    const REASON_OTHER_EXPLICIT_EXIT = 'other_explicit_exit';

    /** The state that follows the first success. */
    const SURFACE_POST_SUCCESS = 'post_success';

    /** The offer that rides along with the staging progress modal. */
    const SURFACE_STAGING_PROGRESS = 'staging_progress';

    const EVENT_ACTION_STARTED   = 'onboarding_action_started';
    const EVENT_ACTION_COMPLETED = 'onboarding_action_completed';
    const EVENT_ACTION_CANCELLED = 'onboarding_action_cancelled';
    const EVENT_NEXT_OFFER_SHOWN = 'onboarding_next_offer_shown';
    const EVENT_COMPLETED        = 'onboarding_completed';

    /** Past this, a job that stopped reporting no longer holds focus mode. */
    const MAX_RUNNING_AGE_IN_SECONDS = DAY_IN_SECONDS;

    /** @var array|null */
    private $state = null;

    /** @var string|null */
    private $completionReason = null;

    /**
     * @return string One of the STEP_* constants.
     */
    public function getStep(): string
    {
        if ($this->isCompleted()) {
            return self::STEP_DONE;
        }

        if ($this->getAction(self::POSITION_FIRST) === '') {
            return self::STEP_SELECT;
        }

        return $this->activePosition() === '' ? self::STEP_NEXT : self::STEP_RUNNING;
    }

    /**
     * @param string $position POSITION_FIRST or POSITION_SECOND.
     * @return string The capability chosen for that position, or an empty string.
     */
    public function getAction(string $position): string
    {
        return $this->read($position, 'action');
    }

    /**
     * @return string The capability the user did not start with, or empty.
     */
    public function getNextCapability(): string
    {
        $first = $this->getAction(self::POSITION_FIRST);

        if ($first === '') {
            return '';
        }

        return $first === self::CAPABILITY_BACKUP ? self::CAPABILITY_STAGING : self::CAPABILITY_BACKUP;
    }

    /**
     * @return string The capability the journey is on, started or not. What
     *                decides which surface to render.
     */
    public function getActiveCapability(): string
    {
        return $this->getAction($this->activePosition());
    }

    /**
     * @return string The capability with a job in flight. What decides the
     *                wording, so configuring one does not read as progress.
     */
    public function getRunningCapability(): string
    {
        $position = $this->activePosition();

        return $this->read($position, 'started_at') === '' ? '' : $this->getAction($position);
    }

    /**
     * Whether a first run is in progress. The deferred-backup queue is shared
     * with ordinary staging jobs, so its endpoint has to ask before writing any
     * journey state.
     */
    public function isUnderWay(): bool
    {
        return !$this->isCompleted() && $this->getAction(self::POSITION_FIRST) !== '';
    }

    public function isFirstCapabilityCompleted(): bool
    {
        return $this->hasSucceeded(self::POSITION_FIRST);
    }

    /**
     * @param string $capability CAPABILITY_STAGING or CAPABILITY_BACKUP.
     * @return string The position it was recorded as, or empty when untracked.
     */
    public function selectAction(string $capability): string
    {
        if (!in_array($capability, self::CAPABILITIES, true) || $this->isCompleted()) {
            return '';
        }

        $position = $this->positionFor($capability);

        if ($this->getAction($position) === '') {
            $this->stamp($position, 'selected_at', ['action' => $capability]);
        }

        return $position;
    }

    public function startAction()
    {
        $position = $this->activePosition();

        if ($position === '' || $this->read($position, 'started_at') !== '') {
            return;
        }

        $this->stamp($position, 'started_at');
        $this->logStep(self::EVENT_ACTION_STARTED, $position);
    }

    /**
     * @action wpstg_staging_site_created
     */
    public function completeStaging()
    {
        $this->completeCapability(self::CAPABILITY_STAGING);
    }

    /**
     * The hook hands over the job it finished, which is the only place the file
     * it wrote is known without asking for it again. Recorded on the journey so
     * the success state can name it, and still name it after a reload.
     *
     * @action wpstg.backup.created
     * @param mixed $jobDataDto
     */
    public function completeBackup($jobDataDto = null)
    {
        $position = $this->activePosition();

        $this->completeCapability(self::CAPABILITY_BACKUP);

        if ($position === '' || !is_object($jobDataDto) || !method_exists($jobDataDto, 'getBackupFilePath')) {
            return;
        }

        $path = (string)$jobDataDto->getBackupFilePath();

        if ($path === '' || !is_readable($path)) {
            return;
        }

        $state = $this->readState();
        $state[$position]['backup_file'] = basename($path);
        $state[$position]['backup_size'] = (int)filesize($path);

        $this->write($state);
    }

    /**
     * @return array `name` and `size` of the backup this run created, empty when
     *               none was recorded.
     */
    public function getCompletedBackupDetails(): array
    {
        foreach ([self::POSITION_FIRST, self::POSITION_SECOND] as $position) {
            $name = $this->read($position, 'backup_file');

            if ($name !== '') {
                return ['name' => $name, 'size' => (int)$this->read($position, 'backup_size')];
            }
        }

        return [];
    }

    /**
     * Counts a success against the outstanding capability. Not gated on the
     * start being reported: a dropped request must not lose a real completion.
     *
     * @param string $capability CAPABILITY_STAGING or CAPABILITY_BACKUP.
     */
    public function completeCapability(string $capability)
    {
        $position = $this->activePosition();

        if ($position === '' || $this->getAction($position) !== $capability) {
            return;
        }

        $this->stamp($position, 'completed_at');
        $this->logStep(self::EVENT_ACTION_COMPLETED, $position);

        if ($position === self::POSITION_SECOND) {
            $this->complete(self::REASON_TWO_CAPABILITIES);
        }
    }

    /**
     * Takes back the capability the user just cancelled, started or not.
     *
     * cancelAction() refuses once a job has begun, because a request that was
     * dropped in flight is not the same as one never made. A cancel is: the user
     * said no to this capability, and the run has to offer the choice again
     * rather than hold the screen with a job that is no longer running.
     *
     * @action wpstg_cancel_clone
     * @action wpstg--job--cancel
     *
     * @return void
     */
    public function abandonActionOnRequest()
    {
        if (!WPStaging::make(Auth::class)->isAuthenticatedRequest('', 'manage_options')) {
            return;
        }

        $this->abandonAction();
    }

    public function abandonAction()
    {
        $position = $this->activePosition();

        if ($position === '' || $this->isCompleted()) {
            return;
        }

        $this->logStep(self::EVENT_ACTION_CANCELLED, $position);
        $this->forget($position);
    }

    /**
     * Takes back a capability whose job never started, so backing out of the
     * settings dialog returns to the step the user chose it from.
     */
    public function cancelAction()
    {
        $position = $this->activePosition();

        if ($position === '' || $this->read($position, 'started_at') !== '') {
            return;
        }

        $this->forget($position);
    }

    /**
     * Puts a second capability that will not succeed back on offer. The first is
     * left alone: the user is in front of its own error and retry.
     */
    public function cancelSecondAction()
    {
        if ($this->getAction(self::POSITION_SECOND) === '' || $this->hasSucceeded(self::POSITION_SECOND)) {
            return;
        }

        $this->forget(self::POSITION_SECOND);
    }

    /**
     * Reports the second capability being put in front of the user, once per
     * surface. Both surfaces report through here so the event means the same
     * thing however it is counted.
     *
     * @param string $surface SURFACE_POST_SUCCESS or SURFACE_STAGING_PROGRESS.
     */
    public function recordNextOfferShown(string $surface = self::SURFACE_POST_SUCCESS)
    {
        $capability = $this->getNextCapability();
        $stamp      = $surface === self::SURFACE_STAGING_PROGRESS ? 'offered_progress_at' : 'offered_at';

        if ($capability === '' || $this->read(self::POSITION_SECOND, $stamp) !== '') {
            return;
        }

        $this->stamp(self::POSITION_SECOND, $stamp);

        AnalyticsGenericEvent::logEvent(self::EVENT_NEXT_OFFER_SHOWN, FreeOnboarding::ANALYTICS_GROUP, [
            'action'  => $capability,
            'surface' => $surface,
        ]);
    }

    /**
     * Ends the first run, naming the reason from where the user was standing.
     */
    public function completeOnExplicitExit()
    {
        $reasons = [
            self::STEP_SELECT => self::REASON_SKIPPED_INITIAL,
            self::STEP_NEXT   => self::REASON_DONE_AFTER_FIRST,
        ];

        $step = $this->getStep();

        $this->complete(isset($reasons[$step]) ? $reasons[$step] : self::REASON_OTHER_EXPLICIT_EXIT);
    }

    /**
     * @param string $reason One of the REASON_* constants.
     */
    public function complete(string $reason)
    {
        if ($this->isCompleted()) {
            return;
        }

        AnalyticsGenericEvent::logEvent(self::EVENT_COMPLETED, FreeOnboarding::ANALYTICS_GROUP, [
            'reason'        => $reason,
            'first_action'  => $this->getAction(self::POSITION_FIRST),
            'second_action' => $this->getAction(self::POSITION_SECOND),
        ]);

        update_option(self::OPTION_COMPLETED, $reason, false);
        $this->completionReason = $reason;

        delete_option(self::OPTION_STATE);
        delete_option(self::OPTION_RESTARTED);
        delete_option(QueuedBackup::OPTION_STATE);
        $this->state = null;
    }

    public function isCompleted(): bool
    {
        return $this->getCompletionReason() !== '';
    }

    /**
     * Puts the installation back on the three-card selector.
     */
    public function restart()
    {
        delete_option(self::OPTION_COMPLETED);
        delete_option(self::OPTION_STATE);
        delete_option(FreeOnboarding::OPTION_EXPOSURE);
        delete_option(QueuedBackup::OPTION_STATE);
        update_option(self::OPTION_RESTARTED, (string)time(), false);

        $this->state            = null;
        $this->completionReason = null;
    }

    public function wasRestarted(): bool
    {
        return get_option(self::OPTION_RESTARTED) !== false;
    }

    /**
     * @return string One of the REASON_* constants, or an empty string while the
     *                first run is still going.
     */
    public function getCompletionReason(): string
    {
        if ($this->completionReason === null) {
            $this->completionReason = (string)get_option(self::OPTION_COMPLETED, '');
        }

        return $this->completionReason;
    }

    private function hasSucceeded(string $position): bool
    {
        return $this->read($position, 'completed_at') !== '';
    }

    /**
     * @return string The position still waiting for its success, or an empty string.
     */
    private function activePosition(): string
    {
        foreach ([self::POSITION_FIRST, self::POSITION_SECOND] as $position) {
            if ($this->getAction($position) !== '' && !$this->hasSucceeded($position)) {
                return $position;
            }
        }

        return '';
    }

    /**
     * @return string Where this capability belongs, so that re-picking the one
     *                already in flight does not open a second slot.
     */
    private function positionFor(string $capability): string
    {
        $first = $this->getAction(self::POSITION_FIRST);

        return ($first === '' || $first === $capability) ? self::POSITION_FIRST : self::POSITION_SECOND;
    }

    private function logStep(string $event, string $position)
    {
        AnalyticsGenericEvent::logEvent($event, FreeOnboarding::ANALYTICS_GROUP, [
            'action'   => $this->getAction($position),
            'position' => $position,
        ]);
    }

    private function stamp(string $position, string $key, array $extra = [])
    {
        $state            = $this->readState();
        $state[$position] = array_merge($state[$position], [$key => time()], $extra);

        $this->write($state);
    }

    private function forget(string $position)
    {
        $state            = $this->readState();
        $state[$position] = [];

        $this->write($state);
    }

    private function write(array $state)
    {
        $this->state = $state;
        update_option(self::OPTION_STATE, $state, false);
    }

    /**
     * Self-healing rather than pure, like QueuedBackup: the state is only read
     * on a page render or a job hook, and neither has anywhere else to notice
     * that a job stopped reporting.
     */
    private function dropStaleAction()
    {
        foreach ([self::POSITION_FIRST, self::POSITION_SECOND] as $position) {
            $startedAt = empty($this->state[$position]['started_at']) ? 0 : (int)$this->state[$position]['started_at'];

            if ($startedAt === 0 || !empty($this->state[$position]['completed_at'])) {
                continue;
            }

            if ($startedAt < time() - self::MAX_RUNNING_AGE_IN_SECONDS) {
                $this->state[$position] = [];
                update_option(self::OPTION_STATE, $this->state, false);
            }
        }
    }

    /**
     * @return string Empty when unset, so callers can test presence with `!== ''`.
     */
    private function read(string $position, string $key): string
    {
        $state = $this->readState();

        return empty($state[$position][$key]) ? '' : (string)$state[$position][$key];
    }

    /**
     * @return array Both positions always present, so writers never guard for shape.
     */
    private function readState(): array
    {
        if ($this->state !== null) {
            return $this->state;
        }

        // A finished run has no state. Ending it deletes the record, but a delete
        // is not the only thing that decides the answer: a request still holding
        // the old state can write it back afterwards, and the run would then be
        // both over and half-way through — which is a screen nobody can leave.
        $stored      = $this->isCompleted() ? [] : get_option(self::OPTION_STATE, []);
        $stored      = is_array($stored) ? $stored : [];
        $this->state = [];

        foreach ([self::POSITION_FIRST, self::POSITION_SECOND] as $position) {
            $this->state[$position] = isset($stored[$position]) && is_array($stored[$position]) ? $stored[$position] : [];
        }

        $this->dropStaleAction();

        return $this->state;
    }
}
