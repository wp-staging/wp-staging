<?php

namespace WPStaging\Framework\Onboarding;

use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Utils\Sanitize;

/**
 * Endpoints the first run calls as the user picks an action, watches it start,
 * parks a backup for after the staging site, or leaves the journey.
 */
class OnboardingAjax
{
    /** @var Auth */
    private $auth;

    /** @var Sanitize */
    private $sanitize;

    /** @var FreeOnboarding */
    private $onboarding;

    /** @var QueuedBackup */
    private $queuedBackup;

    /** @var OnboardingJourney */
    private $journey;

    public function __construct(
        Auth $auth,
        Sanitize $sanitize,
        FreeOnboarding $onboarding,
        QueuedBackup $queuedBackup,
        OnboardingJourney $journey
    ) {
        $this->auth         = $auth;
        $this->sanitize     = $sanitize;
        $this->onboarding   = $onboarding;
        $this->queuedBackup = $queuedBackup;
        $this->journey      = $journey;
    }

    public function ajaxSelectAction()
    {
        if (!$this->isAuthorized()) {
            return;
        }

        if (!$this->onboarding->selectAction($this->postValue('onboardingAction'))) {
            wp_send_json_error(null, 400);
            return;
        }

        wp_send_json_success();
    }

    /**
     * The job behind the chosen action has begun, which is what a later success
     * gets matched against.
     */
    public function ajaxActionStarted()
    {
        if (!$this->isAuthorized()) {
            return;
        }

        $this->journey->startAction();

        wp_send_json_success();
    }

    public function ajaxOfferShown()
    {
        if (!$this->isAuthorized()) {
            return;
        }

        $this->journey->recordNextOfferShown(OnboardingJourney::SURFACE_STAGING_PROGRESS);

        wp_send_json_success();
    }

    /**
     * Puts the first run back on screen, for the footer link in debug builds.
     *
     * @return void
     */
    public function ajaxRestart()
    {
        if (!$this->isAuthorized()) {
            return;
        }

        if (!$this->onboarding->canRestart()) {
            wp_send_json_error(null, 403);
            return;
        }

        $this->onboarding->restart();

        wp_send_json_success();
    }

    /**
     * The state that follows a completed capability, rendered now rather than
     * when the page loaded.
     *
     * @return void
     */
    public function ajaxNextStep()
    {
        if (!$this->isAuthorized()) {
            return;
        }

        $renderer = NextStepRenderer::resolve();

        wp_send_json_success(['html' => $renderer === null ? '' : $renderer->render()]);
    }

    /**
     * How the backup parked behind the staging site is doing, for a user who
     * stayed on the screen that started it.
     *
     * @return void
     */
    public function ajaxQueuedBackupStatus()
    {
        if (!$this->isAuthorized()) {
            return;
        }

        // Before answering: the request that asks how the backup is doing is also
        // the one that can get it moving, and on a server that cannot call itself
        // nothing else will for another minute.
        $this->queuedBackup->runWaitingWork();

        $status = $this->queuedBackup->getReportedStatus();

        wp_send_json_success([
            'status' => $status,
            'panel'  => $this->shouldRenderJobPanel($status) ? $this->renderJobPanel() : '',
        ]);
    }

    /**
     * The screen asks for the job panel because the card could not carry one: it
     * is rendered the moment the staging site finishes, and the backup released
     * behind it is queued rather than running until background processing picks
     * it up, which can be a while after.
     */
    private function shouldRenderJobPanel(string $status): bool
    {
        return $status === QueuedBackup::STATUS_RUNNING && $this->postValue('needPanel') === '1';
    }

    private function renderJobPanel(): string
    {
        ob_start();
        require WPSTG_VIEWS_DIR . 'job/locked.php';

        return (string)ob_get_clean();
    }

    /**
     * @return void
     */
    public function ajaxCancelAction()
    {
        if (!$this->isAuthorized()) {
            return;
        }

        $this->journey->cancelAction();

        wp_send_json_success();
    }

    /**
     * The one endpoint behind every explicit way out — "Skip for now", "Done",
     * and leaving a running job behind — with the reason derived from where the
     * user was standing rather than from what the browser claims.
     */
    public function ajaxFinish()
    {
        if (!$this->isAuthorized()) {
            return;
        }

        $this->onboarding->finish();

        wp_send_json_success();
    }

    /**
     * Parks a backup to run once the staging site currently being created is done.
     *
     * Answers with the resulting status either way, so a repeated click or a
     * replayed request renders the same acknowledgement instead of an error.
     */
    public function ajaxQueueBackup()
    {
        if (!$this->isAuthorized()) {
            return;
        }

        $isFirstRun = $this->journey->isUnderWay();

        if ($isFirstRun) {
            $this->onboarding->recordBackupNextClicked();
        }

        if (!$this->journey->isFirstCapabilityCompleted() && $this->queuedBackup->queue($this->postValue('stagingJobId')) && $isFirstRun) {
            $this->onboarding->selectAction(OnboardingJourney::CAPABILITY_BACKUP);
        }

        wp_send_json_success(['status' => $this->queuedBackup->getStatus()]);
    }

    private function isAuthorized(): bool
    {
        if ($this->auth->isAuthenticatedRequest('', 'manage_options')) {
            return true;
        }

        wp_send_json_error(null, 401);

        return false;
    }

    private function postValue(string $key): string
    {
        return isset($_POST[$key]) ? $this->sanitize->sanitizeString($_POST[$key]) : '';
    }
}
