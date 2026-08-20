<?php

namespace WPStaging\Framework\Onboarding;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\Strings;











class NextStepRenderer
{
 
    private $journey;

 
    private $onboarding;

 
    private $queuedBackup;

 
    private $strings;

    public function __construct(FreeOnboarding $onboarding, OnboardingJourney $journey, QueuedBackup $queuedBackup, Strings $strings)
    {
        $this->onboarding   = $onboarding;
        $this->journey      = $journey;
        $this->queuedBackup = $queuedBackup;
        $this->strings      = $strings;
    }





    public function render(): string
    {
 
 
        if ($this->journey->getCompletionReason() === OnboardingJourney::REASON_TWO_CAPABILITIES) {
            return $this->renderCard(OnboardingJourney::CAPABILITY_STAGING, '', true, []);
        }

        $completedCapability = $this->journey->getAction(OnboardingJourney::POSITION_FIRST);

        if ($completedCapability === '' || !$this->journey->isFirstCapabilityCompleted()) {
            return '';
        }

        $secondAction = $this->journey->getAction(OnboardingJourney::POSITION_SECOND);

        return $this->renderCard(
            $completedCapability,
            $this->journey->getNextCapability(),
            $secondAction === '',
            $this->describeBackup(),
            $this->queuedBackup->isPending()
        );
    }











    private function describeBackup(): array
    {
        $details = $this->journey->getCompletedBackupDetails();

        if (empty($details['name'])) {
            return $details;
        }

        $details['name'] = $this->strings->maskBackupFilename($details['name']);

        return $details;
    }








    private function renderCard(string $completedCapability, string $nextCapability, bool $isNextStepOffered, array $backupDetails, bool $isBackupInBackground = false): string
    {
        $stagingSiteUrl = $this->onboarding->getLatestStagingSiteUrl();
        $adminUrl       = admin_url('admin.php?page=');

 
 
        $isNextCapabilityAvailable = !($nextCapability === OnboardingJourney::CAPABILITY_STAGING && $this->onboarding->isHostedOnWordPressCom());

        ob_start();
        include WPSTG_VIEWS_DIR . 'onboarding/next-step.php';

        return (string)ob_get_clean();
    }







    public static function resolve()
    {
        try {
            return WPStaging::make(self::class);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
