<?php

namespace WPStaging\Framework\Onboarding;

use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Utils\Sanitize;





class OnboardingAjax
{
 
    private $auth;

 
    private $sanitize;

 
    private $onboarding;

 
    private $queuedBackup;

 
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







    public function ajaxNextStep()
    {
        if (!$this->isAuthorized()) {
            return;
        }

        $renderer = NextStepRenderer::resolve();

        wp_send_json_success(['html' => $renderer === null ? '' : $renderer->render()]);
    }







    public function ajaxQueuedBackupStatus()
    {
        if (!$this->isAuthorized()) {
            return;
        }

 
 
 
        $this->queuedBackup->runWaitingWork();

        $status = $this->queuedBackup->getReportedStatus();

        wp_send_json_success([
            'status' => $status,
            'panel'  => $this->shouldRenderJobPanel($status) ? $this->renderJobPanel() : '',
        ]);
    }







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




    public function ajaxCancelAction()
    {
        if (!$this->isAuthorized()) {
            return;
        }

        $this->journey->cancelAction();

        wp_send_json_success();
    }






    public function ajaxFinish()
    {
        if (!$this->isAuthorized()) {
            return;
        }

        $this->onboarding->finish();

        wp_send_json_success();
    }







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
