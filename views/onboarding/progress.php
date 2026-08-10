<?php

/**
 * What the first run shows while it is waiting on a job. It exists for the
 * reload: the browser that started the job has the process modal, but a page
 * loaded fresh mid-job would otherwise fall back to the normal WP STAGING page,
 * promotions and all, in the middle of a guided first run.
 *
 * @var string $activeCapability  The capability the journey is on.
 * @var string $runningCapability Same, once its job has reported a start.
 * @var string $queuedBackupStatus One of the QueuedBackup STATUS_* values, or empty.
 */

use WPStaging\Framework\Onboarding\OnboardingJourney;
use WPStaging\Framework\Onboarding\QueuedBackup;

$states = [
    OnboardingJourney::CAPABILITY_STAGING => [
        'title'    => __('Creating your staging site', 'wp-staging'),
        'text'     => __('This can take a few minutes. Leave this page open while it finishes.', 'wp-staging'),
        'starting' => __('Starting your staging site', 'wp-staging'),
        'icon'     => 'wpstg-icon-box-blue',
    ],
    OnboardingJourney::CAPABILITY_BACKUP  => [
        'title'    => __('Creating your backup', 'wp-staging'),
        'text'     => __('This runs on the server, so you can keep working while it finishes.', 'wp-staging'),
        'starting' => __('Starting your backup', 'wp-staging'),
        'icon'     => 'wpstg-icon-box-green',
    ],
];

if (!isset($states[$activeCapability])) {
    return;
}

$state = $states[$activeCapability];

// Until the job reports a start there is nothing to describe the progress of,
// so the state says what it is doing rather than how long it will take.
$isStarted = $runningCapability !== '';
$title     = $isStarted ? $state['title'] : $state['starting'];
$text      = $isStarted ? $state['text'] : __('This only takes a moment.', 'wp-staging');
?>
<div class="wpstg-onboarding-next wpstg-onboarding-progress" role="status" aria-live="polite">
    <div class="wpstg-onboarding-next__offer">
        <div class="wpstg-icon-box <?php echo esc_attr($state['icon']); ?> wpstg-onboarding-next__icon">
            <?php
            $capability = $activeCapability;
            include WPSTG_VIEWS_DIR . 'onboarding/capability-icon.php';
            ?>
        </div>

        <h2 class="wpstg-onboarding-next__title"><?php echo esc_html($title); ?></h2>
        <p class="wpstg-onboarding-next__text"><?php echo esc_html($text); ?></p>

        <span class="wpstg-onboarding-progress__bar" aria-hidden="true"></span>

        <?php if ($activeCapability === OnboardingJourney::CAPABILITY_STAGING && in_array($queuedBackupStatus, [QueuedBackup::STATUS_QUEUED, QueuedBackup::STATUS_RUNNING], true)) : ?>
            <p class="wpstg-onboarding-progress__queued">
                <?php esc_html_e('Your backup will start automatically when this finishes.', 'wp-staging'); ?>
            </p>
        <?php endif; ?>

        <button type="button" class="wpstg-onboarding-next__finish" data-wpstg-onboarding-finish>
            <?php esc_html_e('Skip for now', 'wp-staging'); ?>
        </button>
    </div>
</div>
