<?php

namespace WPStaging\Framework\Onboarding;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsGenericEvent;
use WPStaging\Framework\Analytics\AnalyticsConsent;
use WPStaging\Framework\Experiments\ExperimentManager;
use WPStaging\Framework\Experiments\ExperimentsRegistry;
use WPStaging\Framework\SiteInfo;
use WPStaging\Staging\Sites;

/**
 * The first-run experience of WP STAGING Free and the surface of the
 * `free_onboarding_v1` experiment.
 *
 * @see ExperimentManager for how an installation is assigned to a variant.
 */
class FreeOnboarding
{
    /** When the assigned variant was first shown, and the consent decision at that moment. */
    const OPTION_EXPOSURE = 'wpstg_onboarding_exposure';

    /** Eligible, but the consent decision is still open. */
    const STAGE_PRE_CONSENT = 'pre_consent';

    /** Not eligible; the page renders exactly as it always did. */
    const STAGE_NONE = '';

    const CONSENT_BEFORE_EXPOSURE        = 'before_exposure';
    const CONSENT_UNRESOLVED_AT_EXPOSURE = 'unresolved_at_exposure';

    const ANALYTICS_GROUP = 'onboarding';

    /** The assigned variant became visible. This is the experiment's denominator. */
    const EVENT_IMPRESSION = 'onboarding_impression';

    const EVENT_ACTION_SELECTED     = 'onboarding_action_selected';
    const EVENT_BACKUP_NEXT_CLICKED = 'backup_next_clicked';

    /** Leaves WordPress for a download page, so it is not a capability WP STAGING can see through. */
    const ACTION_DESKTOP = 'desktop';

    const ACTIONS = ['staging', 'backup', self::ACTION_DESKTOP];

    /** The admin pages that render the first-run surface. */
    const FIRST_RUN_PAGES = ['wpstg_clone', 'wpstg_backup'];

    /** @var ExperimentManager */
    private $experimentManager;

    /** @var FirstInstall */
    private $firstInstall;

    /** @var BackupPluginsDetector */
    private $backupPluginsDetector;

    /** @var SiteInfo */
    private $siteInfo;

    /** @var AnalyticsConsent */
    private $analyticsConsent;

    /** @var OnboardingJourney */
    private $journey;

    /** @var Sites */
    private $sites;

    /** @var bool|null */
    private $isEligible = null;

    public function __construct(
        ExperimentManager $experimentManager,
        FirstInstall $firstInstall,
        BackupPluginsDetector $backupPluginsDetector,
        SiteInfo $siteInfo,
        AnalyticsConsent $analyticsConsent,
        OnboardingJourney $journey,
        Sites $sites
    ) {
        $this->experimentManager     = $experimentManager;
        $this->firstInstall          = $firstInstall;
        $this->backupPluginsDetector = $backupPluginsDetector;
        $this->siteInfo              = $siteInfo;
        $this->analyticsConsent      = $analyticsConsent;
        $this->journey               = $journey;
        $this->sites                 = $sites;
    }

    /**
     * Resolved defensively for callers that run on every admin page: a first-run
     * detail must never be able to take the admin down with it.
     *
     * @return FreeOnboarding|null Null when the container cannot build it.
     */
    public static function resolve()
    {
        try {
            return WPStaging::make(self::class);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Which first-run surface this request should render.
     *
     * @return string STAGE_NONE, STAGE_PRE_CONSENT, or a variant name. The
     *                variant is only ever returned once the consent decision is
     *                settled, so no assigned design can be seen behind the
     *                consent modal and influence the answer.
     */
    public function getStage(): string
    {
        $variant = $this->getVariant();
        if ($variant === '') {
            return self::STAGE_NONE;
        }

        return $this->isConsentResolved() ? $variant : self::STAGE_PRE_CONSENT;
    }

    /**
     * Whether the user has answered the analytics question one way or another.
     *
     * "Skip" leaves consent null but permanently dismisses the modal, so the
     * question will not be put again and the product must move on. Escape merely
     * hides the modal client side and persists nothing, which leaves the decision
     * genuinely open and keeps the user on the pre-consent shell.
     */
    public function isConsentResolved(): bool
    {
        if ($this->analyticsConsent->hasUserConsent() !== null) {
            return true;
        }

        return (bool)get_option(AnalyticsConsent::OPTION_NAME_ANALYTICS_MODAL_DISMISSED);
    }

    /**
     * Whether this installation should take part in the onboarding experiment.
     */
    public function isEligible(): bool
    {
        if ($this->isEligible === null) {
            $this->isEligible = $this->checkEligibility();
        }

        return $this->isEligible;
    }

    /**
     * The variant this installation sees, enrolling it on the first eligible visit.
     *
     * @return string One of the `free_onboarding_v1` variants, or an empty
     *                string when this installation is not part of the experiment.
     */
    public function getVariant(): string
    {
        if (!$this->isEligible()) {
            return '';
        }

        return $this->experimentManager->getVariant(ExperimentsRegistry::EXPERIMENT_FREE_ONBOARDING);
    }

    /**
     * @return bool Whether the redesigned first-action selector replaces the page content.
     */
    public function isTaskSelector(): bool
    {
        return $this->getStage() === ExperimentsRegistry::VARIANT_TASK_SELECTOR;
    }

    /**
     * @return bool Whether the neutral shell is on screen with the consent question still open.
     */
    public function isPreConsent(): bool
    {
        return $this->getStage() === self::STAGE_PRE_CONSENT;
    }

    /**
     * Whether the selector owns the screen this request is rendering.
     *
     * getStage() answers which surface this installation is on, not whether it
     * is on screen right now. The selector renders from views/clone/index.php
     * only, so anything that stands down for it — notices above all — must ask
     * this rather than the stage, or it stands down on Settings and Tools too.
     */
    public function ownsCurrentScreen(): bool
    {
        return $this->isTaskSelector() && $this->isFirstRunPage();
    }

    /**
     * @return bool Whether the neutral shell is the thing on screen right now.
     */
    public function isPreConsentScreen(): bool
    {
        return $this->isPreConsent() && $this->isFirstRunPage();
    }

    private function isFirstRunPage(): bool
    {
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : ''; // phpcs:ignore WPStaging.Security.FirstArgNotAString

        return in_array($page, self::FIRST_RUN_PAGES, true);
    }

    /**
     * Whether this installation belongs to the treatment arm, whether or not the
     * selector itself is still on screen — picking "Create a Staging Site" closes
     * the selector, but the deferred backup offer is still part of the treatment.
     */
    public function isInTreatment(): bool
    {
        if (WPStaging::isPro()) {
            return false;
        }

        return $this->experimentManager->isVariant(
            ExperimentsRegistry::EXPERIMENT_FREE_ONBOARDING,
            ExperimentsRegistry::VARIANT_TASK_SELECTOR
        );
    }

    /**
     * Gated on the consent decision as well as the assignment: the offer carries
     * backup wording, and shipping it before consent resolves would make the
     * treatment's markup differ from control's.
     */
    public function shouldOfferBackupNext(): bool
    {
        return $this->isInTreatment() && $this->isConsentResolved();
    }

    public function getBackupPluginsDetector(): BackupPluginsDetector
    {
        return $this->backupPluginsDetector;
    }

    /**
     * Records that the assigned variant has just become visible, once per
     * installation, so a reload does not inflate the denominator.
     *
     * The consent decision at this moment is stamped on the event: only
     * `before_exposure` participants form the clean experiment cohort, because
     * anyone who saw a variant first could have had their answer influenced by it.
     */
    public function recordExposure()
    {
        $variant = $this->getVariant();
        if ($variant === '' || !$this->isConsentResolved() || $this->hasBeenExposed()) {
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

    /**
     * @return bool Whether this installation has already seen its variant.
     */
    public function hasBeenExposed(): bool
    {
        $exposure = get_option(self::OPTION_EXPOSURE);

        return is_array($exposure) && !empty($exposure['at']);
    }

    /**
     * Records a chosen action. Choosing is not finishing: the journey keeps
     * running until the user has seen a capability succeed and answered the
     * offer that follows it.
     *
     * @param string $action One of self::ACTIONS.
     * @return bool Whether the action was recognised.
     */
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

    /**
     * Ends the first run because the user asked to leave it.
     */
    public function finish()
    {
        $this->journey->completeOnExplicitExit();
        $this->isEligible = false;
    }

    /**
     * Every click on the deferred backup offer, so repeat clicks stay visible
     * against the one request they produce.
     */
    public function recordBackupNextClicked()
    {
        AnalyticsGenericEvent::logEvent(self::EVENT_BACKUP_NEXT_CLICKED, self::ANALYTICS_GROUP);
    }

    public function getJourney(): OnboardingJourney
    {
        return $this->journey;
    }

    /**
     * Whether to offer the footer's "Restart first run" link.
     *
     * Debug builds only. Replaying the first run deletes the exposure record,
     * so the next render reports a second impression for the same installation
     * — harmless while developing, but it would double-count the experiment's
     * denominator on a production site.
     */
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

    /**
     * @return string One of the OnboardingJourney STEP_* constants, or an empty
     *                string when this screen shows no first-run surface at all.
     */
    public function getJourneyStep(): string
    {
        return $this->isTaskSelector() ? $this->journey->getStep() : '';
    }

    /**
     * The first run replaces WP STAGING's success modal, which is where a new
     * staging site's address used to be read, so its own state has to offer it.
     *
     * @return string The most recently created staging site's address, or an
     *                empty string when there is none to show.
     */
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

        // With the slash the browser gets the page; without it the server has to
        // redirect to add one, and a server that rebuilds the address from the
        // host name alone drops a non-standard port on the way.
        return trailingslashit((string)$latest['url']);
    }

    /**
     * Keeps WP STAGING's own submenu out of the way for as long as the first run
     * is asking its question: seven destinations next to three cards is the
     * choice the selector exists to avoid.
     *
     * @filter admin_body_class
     */
    public function addFocusModeBodyClass($classes): string
    {
        // Cheapest test first: this filter runs on every admin screen.
        if (!$this->isFirstRunPage() || (!$this->isPreConsent() && !$this->isTaskSelector())) {
            return (string)$classes;
        }

        return trim($classes . ' wpstg-onboarding-focus-mode');
    }

    /**
     * Deliberately narrow: only a WP STAGING Free installation that this site has
     * never had before, whose owner has not yet picked a first action.
     */
    private function checkEligibility(): bool
    {
        // A staging site inherits the production database, including our options.
        if (WPStaging::isPro() || !is_admin() || !current_user_can('manage_options') || $this->siteInfo->isStagingSite()) {
            return false;
        }

        if ($this->journey->isCompleted()) {
            return false;
        }

        /*
         * Who the first run is offered to is settled once, at the selector. A
         * journey already under way keeps it: the staging-first path creates the
         * very staging site the "no staging sites yet" test looks for, and would
         * otherwise drop the user out of their own first run halfway through.
         */
        if ($this->journey->getStep() !== OnboardingJourney::STEP_SELECT || $this->journey->wasRestarted()) {
            return true;
        }

        return $this->firstInstall->isFirstInstall() && empty(get_option(Sites::STAGING_SITES_OPTION, []));
    }
}
