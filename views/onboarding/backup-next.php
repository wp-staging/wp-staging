<?php

/**
 * The deferred backup offer, rendered hidden and injected into the process modal
 * by js/src/modules/onboarding.js once a staging site starts being created.
 * Carries no id: it is cloned while the template stays in the page.
 *
 * @see \WPStaging\Framework\Onboarding\QueuedBackup
 *
 * @var string $queuedBackupStatus Current state of the request, empty when none.
 * @var bool   $isFirstRunOffer    Whether the first run is making this offer.
 */

use WPStaging\Framework\Onboarding\OnboardingJourney;
use WPStaging\Framework\Onboarding\QueuedBackup;

$isQueued = in_array($queuedBackupStatus, [QueuedBackup::STATUS_QUEUED, QueuedBackup::STATUS_RUNNING], true);

$offerText = empty($isFirstRunOffer)
    ? __('Optional. WP STAGING can make a complete backup of your live site, to run after this.', 'wp-staging')
    : __('Optional. WP STAGING can also make a complete backup of your live site, to run after this.', 'wp-staging');
?>
<div class="wpstg-backup-next" data-state="<?php echo $isQueued ? 'queued' : 'idle'; ?>">
    <div class="wpstg-icon-box wpstg-icon-box-green wpstg-backup-next__icon">
        <?php
        $capability = OnboardingJourney::CAPABILITY_BACKUP;
        $iconSize   = 20;
        include WPSTG_VIEWS_DIR . 'onboarding/capability-icon.php';
        ?>
    </div>

    <div class="wpstg-backup-next__body">
        <div class="wpstg-backup-next__offer">
            <p class="wpstg-backup-next__title"><?php esc_html_e('Protect your live site while you\'re here', 'wp-staging'); ?></p>
            <p class="wpstg-backup-next__text"><?php echo esc_html($offerText); ?></p>
            <button
                type="button"
                class="wpstg-btn wpstg-btn-md wpstg-btn-tint wpstg-backup-next__cta"
                data-wpstg-onboarding-queue-backup
                data-busy-label="<?php esc_attr_e('Queueing…', 'wp-staging'); ?>"
            >
                <?php esc_html_e('Create Backup After This', 'wp-staging'); ?>
            </button>
            <p class="wpstg-backup-next__hint"><?php esc_html_e('Nothing starts until you click. Your backup then runs on its own once the staging site is finished.', 'wp-staging'); ?></p>
        </div>

        <div class="wpstg-backup-next__confirmed">
            <p class="wpstg-backup-next__title">
                <svg class="wpstg-backup-next__check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M20 6 9 17l-5-5"></path>
                </svg>
                <?php esc_html_e('Backup will be created after this', 'wp-staging'); ?>
            </p>
            <p class="wpstg-backup-next__hint"><?php esc_html_e('It will start automatically when your staging site is ready.', 'wp-staging'); ?></p>
        </div>
    </div>
</div>
