<?php

namespace WPStaging\Framework\Onboarding;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsGenericEvent;
use WPStaging\Framework\Security\Auth;







class OnboardingJourney
{
    const OPTION_STATE = 'wpstg_onboarding_journey';

 
    const OPTION_COMPLETED = 'wpstg_onboarding_completed';







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

 
    const SURFACE_POST_SUCCESS = 'post_success';

 
    const SURFACE_STAGING_PROGRESS = 'staging_progress';

    const EVENT_ACTION_STARTED   = 'onboarding_action_started';
    const EVENT_ACTION_COMPLETED = 'onboarding_action_completed';
    const EVENT_ACTION_CANCELLED = 'onboarding_action_cancelled';
    const EVENT_NEXT_OFFER_SHOWN = 'onboarding_next_offer_shown';
    const EVENT_COMPLETED        = 'onboarding_completed';

 
    const MAX_RUNNING_AGE_IN_SECONDS = DAY_IN_SECONDS;

 
    private $state = null;

 
    private $completionReason = null;




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





    public function getAction(string $position): string
    {
        return $this->read($position, 'action');
    }




    public function getNextCapability(): string
    {
        $first = $this->getAction(self::POSITION_FIRST);

        if ($first === '') {
            return '';
        }

        return $first === self::CAPABILITY_BACKUP ? self::CAPABILITY_STAGING : self::CAPABILITY_BACKUP;
    }





    public function getActiveCapability(): string
    {
        return $this->getAction($this->activePosition());
    }





    public function getRunningCapability(): string
    {
        $position = $this->activePosition();

        return $this->read($position, 'started_at') === '' ? '' : $this->getAction($position);
    }






    public function isUnderWay(): bool
    {
        return !$this->isCompleted() && $this->getAction(self::POSITION_FIRST) !== '';
    }

    public function isFirstCapabilityCompleted(): bool
    {
        return $this->hasSucceeded(self::POSITION_FIRST);
    }





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




    public function completeStaging()
    {
        $this->completeCapability(self::CAPABILITY_STAGING);
    }









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





    public function cancelAction()
    {
        $position = $this->activePosition();

        if ($position === '' || $this->read($position, 'started_at') !== '') {
            return;
        }

        $this->forget($position);
    }





    public function cancelSecondAction()
    {
        if ($this->getAction(self::POSITION_SECOND) === '' || $this->hasSucceeded(self::POSITION_SECOND)) {
            return;
        }

        $this->forget(self::POSITION_SECOND);
    }








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




    public function completeOnExplicitExit()
    {
        $reasons = [
            self::STEP_SELECT => self::REASON_SKIPPED_INITIAL,
            self::STEP_NEXT   => self::REASON_DONE_AFTER_FIRST,
        ];

        $step = $this->getStep();

        $this->complete(isset($reasons[$step]) ? $reasons[$step] : self::REASON_OTHER_EXPLICIT_EXIT);
    }




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
        WPStaging::make(QueuedBackup::class)->clearUnlessPending();
        $this->state = null;
    }

    public function isCompleted(): bool
    {
        return $this->getCompletionReason() !== '';
    }




    public function restart()
    {
        delete_option(self::OPTION_COMPLETED);
        delete_option(self::OPTION_STATE);
        delete_option(FreeOnboarding::OPTION_EXPOSURE);
        WPStaging::make(QueuedBackup::class)->clearUnlessPending();
        update_option(self::OPTION_RESTARTED, (string)time(), false);

        $this->state            = null;
        $this->completionReason = null;
    }

    public function wasRestarted(): bool
    {
        return get_option(self::OPTION_RESTARTED) !== false;
    }





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




    private function activePosition(): string
    {
        foreach ([self::POSITION_FIRST, self::POSITION_SECOND] as $position) {
            if ($this->getAction($position) !== '' && !$this->hasSucceeded($position)) {
                return $position;
            }
        }

        return '';
    }





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




    private function read(string $position, string $key): string
    {
        $state = $this->readState();

        return empty($state[$position][$key]) ? '' : (string)$state[$position][$key];
    }




    private function readState(): array
    {
        if ($this->state !== null) {
            return $this->state;
        }

 
 
 
 
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
