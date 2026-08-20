<?php

namespace WPStaging\Framework\Onboarding;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsGenericEvent;
use WPStaging\Framework\Analytics\AnalyticsConsent;
use WPStaging\Framework\SiteInfo;
use WPStaging\Staging\Sites;









class FreeOnboarding
{
 
    const OPTION_EXPOSURE = 'wpstg_onboarding_exposure';

 
    const STAGE_PRE_CONSENT = 'pre_consent';

 
    const STAGE_TASK_SELECTOR = 'task_selector';

 
    const STAGE_NONE = '';

    const CONSENT_BEFORE_EXPOSURE        = 'before_exposure';
    const CONSENT_UNRESOLVED_AT_EXPOSURE = 'unresolved_at_exposure';

    const ANALYTICS_GROUP = 'onboarding';

 
    const EVENT_IMPRESSION = 'onboarding_impression';

    const EVENT_ACTION_SELECTED     = 'onboarding_action_selected';
    const EVENT_BACKUP_NEXT_CLICKED = 'backup_next_clicked';

 
    const ACTION_DESKTOP = 'desktop';

    const ACTIONS = ['staging', 'backup', self::ACTION_DESKTOP];

 
    const FIRST_RUN_PAGES = ['wpstg_clone', 'wpstg_backup'];

 
    private $firstInstall;

 
    private $backupPluginsDetector;

 
    private $siteInfo;

 
    private $analyticsConsent;

 
    private $journey;

 
    private $sites;

 
    private $isEligible = null;

    public function __construct(
        FirstInstall $firstInstall,
        BackupPluginsDetector $backupPluginsDetector,
        SiteInfo $siteInfo,
        AnalyticsConsent $analyticsConsent,
        OnboardingJourney $journey,
        Sites $sites
    ) {
        $this->firstInstall          = $firstInstall;
        $this->backupPluginsDetector = $backupPluginsDetector;
        $this->siteInfo              = $siteInfo;
        $this->analyticsConsent      = $analyticsConsent;
        $this->journey               = $journey;
        $this->sites                 = $sites;
    }







    public static function resolve()
    {
        try {
            return WPStaging::make(self::class);
        } catch (\Throwable $e) {
            return null;
        }
    }









    public function getStage(): string
    {
        if (!$this->isEligible()) {
            return self::STAGE_NONE;
        }

        return $this->isConsentResolved() ? self::STAGE_TASK_SELECTOR : self::STAGE_PRE_CONSENT;
    }









    public function isConsentResolved(): bool
    {
        if ($this->analyticsConsent->hasUserConsent() !== null) {
            return true;
        }

        return (bool)get_option(AnalyticsConsent::OPTION_NAME_ANALYTICS_MODAL_DISMISSED);
    }




    public function isEligible(): bool
    {
        if ($this->isEligible === null) {
            $this->isEligible = $this->checkEligibility();
        }

        return $this->isEligible;
    }




    public function isTaskSelector(): bool
    {
        return $this->getStage() === self::STAGE_TASK_SELECTOR;
    }




    public function isPreConsent(): bool
    {
        return $this->getStage() === self::STAGE_PRE_CONSENT;
    }









    public function ownsCurrentScreen(): bool
    {
        return $this->isTaskSelector() && $this->isFirstRunPage();
    }




    public function isPreConsentScreen(): bool
    {
        return $this->isPreConsent() && $this->isFirstRunPage();
    }

    private function isFirstRunPage(): bool
    {
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : ''; // phpcs:ignore WPStaging.Security.FirstArgNotAString

        return in_array($page, self::FIRST_RUN_PAGES, true);
    }

    public function getBackupPluginsDetector(): BackupPluginsDetector
    {
        return $this->backupPluginsDetector;
    }





    public function isHostedOnWordPressCom(): bool
    {
        return $this->siteInfo->isHostedOnWordPressCom();
    }









    public function recordExposure()
    {
        if (!$this->isTaskSelector() || $this->hasBeenExposed()) {
            return;
        }

        $consentTiming = $this->analyticsConsent->hasUserConsent() === '1'
            ? self::CONSENT_BEFORE_EXPOSURE
            : self::CONSENT_UNRESOLVED_AT_EXPOSURE;

        update_option(self::OPTION_EXPOSURE, [
            'at'      => time(),
            'consent' => $consentTiming,
        ], false);

        AnalyticsGenericEvent::logEvent(self::EVENT_IMPRESSION, self::ANALYTICS_GROUP, [
            'competitor'     => $this->backupPluginsDetector->getCompetitorId(),
            'consent_timing' => $consentTiming,
        ]);
    }




    public function hasBeenExposed(): bool
    {
        $exposure = get_option(self::OPTION_EXPOSURE);

        return is_array($exposure) && !empty($exposure['at']);
    }









    public function selectAction(string $action): bool
    {
        if (!in_array($action, self::ACTIONS, true)) {
            return false;
        }

        AnalyticsGenericEvent::logEvent(self::EVENT_ACTION_SELECTED, self::ANALYTICS_GROUP, [
            'action'     => $action,
            'position'   => $this->journey->selectAction($action),
            'competitor' => $this->backupPluginsDetector->getCompetitorId(),
        ]);

        return true;
    }




    public function finish()
    {
        $this->journey->completeOnExplicitExit();
        $this->isEligible = false;
    }





    public function recordBackupNextClicked()
    {
        AnalyticsGenericEvent::logEvent(self::EVENT_BACKUP_NEXT_CLICKED, self::ANALYTICS_GROUP);
    }

    public function getJourney(): OnboardingJourney
    {
        return $this->journey;
    }









    public function canRestart(): bool
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG || WPStaging::isPro()) {
            return false;
        }

        return current_user_can('manage_options') && !$this->siteInfo->isStagingSite();
    }

    public function restart()
    {
        $this->journey->restart();
        $this->isEligible = null;
    }





    public function getJourneyStep(): string
    {
        return $this->isTaskSelector() ? $this->journey->getStep() : '';
    }








    public function getLatestStagingSiteUrl(): string
    {
        try {
            $sites = $this->sites->getSortedStagingSites();
        } catch (\Throwable $e) {
            return '';
        }

        $latest = reset($sites);

        if (!is_array($latest) || empty($latest['url'])) {
            return '';
        }

 
 
 
        return trailingslashit((string)$latest['url']);
    }








    public function addFocusModeBodyClass($classes): string
    {
 
        if (!$this->isFirstRunPage() || (!$this->isPreConsent() && !$this->isTaskSelector())) {
            return (string)$classes;
        }

        return trim($classes . ' wpstg-onboarding-focus-mode');
    }






    private function checkEligibility(): bool
    {
 
        if (WPStaging::isPro() || !is_admin() || !current_user_can('manage_options') || $this->siteInfo->isStagingSite() || is_multisite()) {
            return false;
        }

        if ($this->journey->isCompleted()) {
            return false;
        }







        if ($this->journey->getStep() !== OnboardingJourney::STEP_SELECT || $this->journey->wasRestarted()) {
            return true;
        }

        return $this->firstInstall->isFirstInstall() && empty(get_option(Sites::STAGING_SITES_OPTION, []));
    }
}
