<?php

namespace WPStaging\Framework\Onboarding;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\Strings;

/**
 * Renders the state that follows a completed capability.
 *
 * The page used to render every card the journey might reach, hidden, and let
 * the browser unhide the right one. That made the card a snapshot of the moment
 * the page loaded: it offered a capability the user had since asked for, named
 * no staging site because none existed yet, and left the previous card on screen
 * beside the new one. Rendering it when the capability actually completes means
 * it always describes the journey as it is.
 */
class NextStepRenderer
{
    /** @var OnboardingJourney */
    private $journey;

    /** @var FreeOnboarding */
    private $onboarding;

    /** @var QueuedBackup */
    private $queuedBackup;

    /** @var Strings */
    private $strings;

    public function __construct(FreeOnboarding $onboarding, OnboardingJourney $journey, QueuedBackup $queuedBackup, Strings $strings)
    {
        $this->onboarding   = $onboarding;
        $this->journey      = $journey;
        $this->queuedBackup = $queuedBackup;
        $this->strings      = $strings;
    }

    /**
     * @return string The card's markup, or an empty string when the journey has
     *                no completed capability to report.
     */
    public function render(): string
    {
        // Finishing the journey deletes its state, so the last card is built from
        // the completion reason — the only thing that outlives it.
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

    /**
     * A backup is named `host_date-time_jobId.wpstg`, and that job id is what
     * keeps its download URL from being guessed: the directory is not indexable,
     * but a known name is a working link. This is a screen people photograph, so
     * the name is masked the same way the backup listings mask it.
     *
     * @see \WPStaging\Backup\Service\Archiver::getDestinationPath()
     *
     * @return array The backup this run created, safe to show.
     */
    private function describeBackup(): array
    {
        $details = $this->journey->getCompletedBackupDetails();

        if (empty($details['name'])) {
            return $details;
        }

        $details['name'] = $this->strings->maskBackupFilename($details['name']);

        return $details;
    }

    /**
     * @param string $completedCapability
     * @param string $nextCapability       Empty for the end of the first run.
     * @param bool   $isNextStepOffered
     * @param array  $backupDetails
     * @param bool   $isBackupInBackground
     */
    private function renderCard(string $completedCapability, string $nextCapability, bool $isNextStepOffered, array $backupDetails, bool $isBackupInBackground = false): string
    {
        $stagingSiteUrl = $this->onboarding->getLatestStagingSiteUrl();
        $adminUrl       = admin_url('admin.php?page=');

        ob_start();
        include WPSTG_VIEWS_DIR . 'onboarding/next-step.php';

        return (string)ob_get_clean();
    }

    /**
     * Resolved defensively: this renders on the plugin's main admin screen, and
     * a first-run detail must not be able to take it down.
     *
     * @return NextStepRenderer|null
     */
    public static function resolve()
    {
        try {
            return WPStaging::make(self::class);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
