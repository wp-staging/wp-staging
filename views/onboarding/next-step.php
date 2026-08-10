<?php

/**
 * The state that follows a successful capability: what just happened, where it
 * put it, the one capability worth doing next, and a way out.
 *
 * Replaces WP STAGING's normal success modal for the duration of the first run,
 * so it carries the new staging site's address and leaves out the review ask,
 * which would land on the exact moment the first run is trying to use. Rendered
 * hidden while the job still runs, so success lands on a screen rather than a gap.
 *
 * @see \WPStaging\Framework\Onboarding\OnboardingJourney
 *
 * @var string $completedCapability  The capability whose success this announces.
 * @var string $nextCapability       The capability being offered.
 * @var bool   $isNextStepOffered    False once the next capability is already under way.
 * @var bool   $isBackupInBackground The offered backup is the one parked behind the staging site.
 * @var string $stagingSiteUrl       Empty unless a staging site exists.
 * @var array  $backupDetails        `name` and `size` of the backup this run created, if any.
 * @var string $adminUrl
 */

use WPStaging\Framework\Onboarding\OnboardingJourney;

$confirmations = [
    OnboardingJourney::CAPABILITY_BACKUP  => [
        'title' => __('Your backup is ready', 'wp-staging'),
        'text'  => __('A complete backup of your website has been created successfully.', 'wp-staging'),
    ],
    OnboardingJourney::CAPABILITY_STAGING => [
        'title' => __('Your staging site is ready', 'wp-staging'),
        'text'  => __('Test updates and changes safely without affecting your live website.', 'wp-staging'),
    ],
];

$offers = [
    OnboardingJourney::CAPABILITY_STAGING => [
        'title'    => __('Test changes safely next', 'wp-staging'),
        'text'     => __('Create a staging copy of this site where you can test updates and changes without touching your live website.', 'wp-staging'),
        'cta'      => __('Create a Staging Site', 'wp-staging'),
        'busy'     => __('Starting…', 'wp-staging'),
        'underway' => __('Creating your staging site', 'wp-staging'),
        'note'     => __('It is being built now. You can keep working while it finishes.', 'wp-staging'),
    ],
    OnboardingJourney::CAPABILITY_BACKUP  => [
        'title'    => __('Protect your live site too', 'wp-staging'),
        'text'     => __('Create a complete backup of this website. It runs on the server, so you can keep working.', 'wp-staging'),
        'cta'      => __('Create Backup Now', 'wp-staging'),
        'busy'     => __('Starting backup…', 'wp-staging'),
        'underway' => __('Creating your backup', 'wp-staging'),
        'note'     => __('It runs on the server, so you can start testing your staging site.', 'wp-staging'),
    ],
];

if (!isset($confirmations[$completedCapability])) {
    return;
}

// Nothing left to offer: this capability is the second one, and finishing it
// finishes the first run. It still gets a state of its own rather than handing
// the moment back to the success modal the rest of the journey replaced.
$isFinale = $nextCapability === '';
$offer    = $isFinale ? [] : $offers[$nextCapability];

$confirmation = $confirmations[$completedCapability];
$hasStagingSite = $completedCapability === OnboardingJourney::CAPABILITY_STAGING;

// A backup parked behind the staging site is already running on the server, so
// this state confirms it and releases the user instead of describing a wait.
$isBackgroundBackup = !$isNextStepOffered && $isBackupInBackground;
$backupDone         = $confirmations[OnboardingJourney::CAPABILITY_BACKUP];
$background         = [
    'title'   => __('Your backup has started', 'wp-staging'),
    'text'    => __('It will continue in the background. You can open your staging site or return to WP STAGING now — there’s no need to wait here.', 'wp-staging'),
    // Shown once the wait stops looking momentary. A server that cannot call
    // itself leaves the request queued for a while before anything picks it up,
    // and a bar with no explanation reads as a stall rather than a queue.
    'waiting' => __('Waiting for the server to pick it up…', 'wp-staging'),
];

// Opening the new site is the thing to do the moment it exists, so it takes the
// primary action and the capability offer steps down to secondary. Without a
// site to open there is nothing to outrank the offer.
$offerButtonClass = $hasStagingSite ? 'wpstg-btn-outline' : 'wpstg-btn-primary';
?>
<div
    class="wpstg-onboarding-next"
    data-wpstg-onboarding
    data-completed-capability="<?php echo esc_attr($completedCapability); ?>"
    data-staging-url="<?php echo esc_url($adminUrl . 'wpstg_clone'); ?>"
    data-backup-url="<?php echo esc_url($adminUrl . 'wpstg_backup'); ?>"
>
    <div class="wpstg-onboarding-next__done">
        <svg class="wpstg-onboarding-next__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M20 6 9 17l-5-5"></path>
        </svg>
    </div>

    <?php if ($isFinale) : ?>
        <h2 class="wpstg-onboarding-next__title"><?php esc_html_e('You\'re all set', 'wp-staging'); ?></h2>
        <p class="wpstg-onboarding-next__text"><?php esc_html_e('Your website is backed up and you have a staging copy ready for safe testing.', 'wp-staging'); ?></p>
    <?php else : ?>
        <h2 class="wpstg-onboarding-next__title"><?php echo esc_html($confirmation['title']); ?></h2>
        <p class="wpstg-onboarding-next__text"><?php echo esc_html($confirmation['text']); ?></p>
    <?php endif; ?>

    <?php if (!$hasStagingSite && !empty($backupDetails['name'])) : ?>
        <p class="wpstg-onboarding-next__where">
            <span class="wpstg-onboarding-next__where-label"><?php esc_html_e('Saved as', 'wp-staging'); ?></span>
            <span class="wpstg-onboarding-next__file"><?php echo esc_html($backupDetails['name']); ?></span>
            <?php if (!empty($backupDetails['size'])) : ?>
                <span class="wpstg-onboarding-next__size"><?php echo esc_html(size_format($backupDetails['size'], 1)); ?></span>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if ($hasStagingSite || $isFinale) : ?>
        <p class="wpstg-onboarding-next__where">
            <span class="wpstg-onboarding-next__where-label"><?php esc_html_e('Installed at', 'wp-staging'); ?></span>
            <a
                class="wpstg-onboarding-next__url"
                data-wpstg-onboarding-site-url
                data-wpstg-onboarding-site-url-text
                target="_blank"
                rel="noopener noreferrer"
                <?php echo $stagingSiteUrl === '' ? 'hidden' : 'href="' . esc_url($stagingSiteUrl) . '"'; ?>
            ><?php echo esc_html($stagingSiteUrl); ?></a>
        </p>

        <?php include WPSTG_VIEWS_DIR . 'onboarding/open-staging-site.php'; ?>
    <?php endif; ?>

    <?php if (!$isFinale) : ?>
        <div
            class="wpstg-onboarding-next__offer"
            <?php if ($isBackgroundBackup) : ?>
            data-wpstg-onboarding-backup="started"
            data-ready-title="<?php echo esc_attr($backupDone['title']); ?>"
            data-ready-text="<?php echo esc_attr($backupDone['text']); ?>"
            role="status"
            aria-live="polite"
            <?php endif; ?>
        >
            <?php if ($isBackgroundBackup) : ?>
                <h3 class="wpstg-onboarding-next__offer-title wpstg-onboarding-next__running">
                    <span class="wpstg-onboarding-next__running-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                            <path d="M20 6 9 17l-5-5"></path>
                        </svg>
                    </span>
                    <span data-wpstg-onboarding-backup-title><?php echo esc_html($background['title']); ?></span>
                </h3>
                <p class="wpstg-onboarding-next__offer-text" data-wpstg-onboarding-backup-text><?php echo esc_html($background['text']); ?></p>

                <?php
                // Stands in until there is a job to report on. A released backup
                // waits for background processing to pick it up, which on a server
                // whose loopback is slow is most of the wait — and the whole point
                // of this state is that the wait is never silent.
                ?>
                <span class="wpstg-onboarding-progress__bar wpstg-onboarding-next__waiting" data-wpstg-onboarding-backup-bar aria-hidden="true"></span>
                <p class="wpstg-onboarding-next__waiting-note" data-wpstg-onboarding-backup-waiting hidden><?php echo esc_html($background['waiting']); ?></p>

                <?php
                // The panel the rest of WP STAGING shows for a job it watches
                // rather than drives. It renders itself only once one is running.
                require WPSTG_VIEWS_DIR . 'job/locked.php';
                ?>
            <?php elseif (!$isNextStepOffered) : ?>
                <h3 class="wpstg-onboarding-next__offer-title"><?php echo esc_html($offer['underway']); ?></h3>
                <p class="wpstg-onboarding-next__offer-text"><?php echo esc_html($offer['note']); ?></p>
            <?php else : ?>
                <h3 class="wpstg-onboarding-next__offer-title"><?php echo esc_html($offer['title']); ?></h3>
                <p class="wpstg-onboarding-next__offer-text"><?php echo esc_html($offer['text']); ?></p>

                <button
                    type="button"
                    class="wpstg-btn wpstg-btn-lg <?php echo esc_attr($offerButtonClass); ?> wpstg-onboarding-next__cta"
                    data-wpstg-onboarding-action="<?php echo esc_attr($nextCapability); ?>"
                    data-busy-label="<?php echo esc_attr($offer['busy']); ?>"
                >
                    <?php echo esc_html($offer['cta']); ?>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <button type="button" class="wpstg-onboarding-next__finish" data-wpstg-onboarding-finish>
        <?php esc_html_e('Continue to WP STAGING', 'wp-staging'); ?>
    </button>
</div>
