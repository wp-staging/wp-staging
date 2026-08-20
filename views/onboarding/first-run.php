<?php

/**
 * The three-card first-run action selector.
 *
 * @see \WPStaging\Framework\Onboarding\FreeOnboarding
 *
 * @var \WPStaging\Framework\Onboarding\FreeOnboarding $onboarding
 */

use WPStaging\Framework\Language\Language;
use WPStaging\Framework\Onboarding\OnboardingJourney;

$competitorName  = $onboarding->getBackupPluginsDetector()->getCompetitorName();
$isHostedOnWpCom = $onboarding->isHostedOnWordPressCom();
?>
<div
    id="wpstg-onboarding"
    class="wpstg-onboarding"
    data-wpstg-onboarding
    data-staging-url="<?php echo esc_url($adminUrl . 'wpstg_clone'); ?>"
    data-backup-url="<?php echo esc_url($adminUrl . 'wpstg_backup'); ?>"
>
    <h1 class="wpstg-onboarding__headline"><?php esc_html_e('What would you like to do first?', 'wp-staging'); ?></h1>
    <p class="wpstg-onboarding__lede"><?php esc_html_e('Start with one. You can use all three anytime.', 'wp-staging'); ?></p>

    <?php require WPSTG_VIEWS_DIR . 'onboarding/update-protection.php'; ?>

    <div class="wpstg-onboarding__cards">

        <div class="wpstg-onboarding-card wpstg-onboarding-card--staging <?php echo $isHostedOnWpCom ? 'wpstg-hidden' : ''; ?>">
            <div class="wpstg-icon-box wpstg-icon-box-blue wpstg-onboarding-card__icon">
                <?php
                $capability = OnboardingJourney::CAPABILITY_STAGING;
                include WPSTG_VIEWS_DIR . 'onboarding/capability-icon.php';
                ?>
            </div>
            <h2 class="wpstg-onboarding-card__title"><?php esc_html_e('Create a Staging Site', 'wp-staging'); ?></h2>
            <p class="wpstg-onboarding-card__text"><?php esc_html_e('Test updates and changes safely before touching your live site.', 'wp-staging'); ?></p>
            <button
                type="button"
                class="wpstg-btn wpstg-btn-lg wpstg-btn-primary wpstg-onboarding-card__cta"
                data-wpstg-onboarding-action="staging"
                data-busy-label="<?php esc_attr_e('Starting…', 'wp-staging'); ?>"
            >
                <?php esc_html_e('Create Staging Site', 'wp-staging'); ?>
            </button>

            <?php
 
 
            ?>
            <button
                type="button"
                class="wpstg-onboarding-card__customize"
                data-wpstg-onboarding-action="staging"
                data-wpstg-onboarding-customize
            >
                <?php esc_html_e('Customize settings', 'wp-staging'); ?>
            </button>
        </div>

        <div class="wpstg-onboarding-card wpstg-onboarding-card--backup">
            <div class="wpstg-icon-box wpstg-icon-box-green wpstg-onboarding-card__icon">
                <?php
                $capability = OnboardingJourney::CAPABILITY_BACKUP;
                include WPSTG_VIEWS_DIR . 'onboarding/capability-icon.php';
                ?>
            </div>
            <h2 class="wpstg-onboarding-card__title">
                <?php esc_html_e('Back Up This Website', 'wp-staging'); ?>
                <span class="wpstg-badge wpstg-badge-green wpstg-onboarding-card__badge"><?php esc_html_e('Quick start', 'wp-staging'); ?></span>
            </h2>
            <p class="wpstg-onboarding-card__text"><?php esc_html_e('Create a complete backup of your files and database.', 'wp-staging'); ?></p>

            <?php if ($competitorName !== '') : ?>
                <div class="wpstg-onboarding-card__aside">
                    <p class="wpstg-onboarding-card__aside-lead">
                        <?php
                        printf(
                            /* translators: %s: name of the backup plugin already active on the site, e.g. UpdraftPlus. */
                            esc_html__('Already using %s for backups?', 'wp-staging'),
                            esc_html($competitorName)
                        );
                        ?>
                    </p>
                    <p class="wpstg-onboarding-card__aside-text">
                        <?php esc_html_e('Try a WP STAGING backup and see how quickly it completes on your own website.', 'wp-staging'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <button
                type="button"
                class="wpstg-btn wpstg-btn-lg wpstg-btn-primary wpstg-onboarding-card__cta"
                data-wpstg-onboarding-action="backup"
                data-busy-label="<?php esc_attr_e('Starting backup…', 'wp-staging'); ?>"
            >
                <?php esc_html_e('Create Backup Now', 'wp-staging'); ?>
            </button>
        </div>

        <div class="wpstg-onboarding-card wpstg-onboarding-card--desktop">
            <div class="wpstg-icon-box wpstg-icon-box-slate wpstg-onboarding-card__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <rect x="3" y="4" width="18" height="12" rx="2.5"></rect>
                    <path d="M2 20h20"></path>
                </svg>
            </div>
            <h2 class="wpstg-onboarding-card__title"><?php esc_html_e('Work Locally', 'wp-staging'); ?></h2>
            <p class="wpstg-onboarding-card__text"><?php esc_html_e('Create a local copy of this WordPress site on macOS, Windows or Linux.', 'wp-staging'); ?></p>
            <a
                href="<?php echo esc_url(Language::getDesktopUrl('onboarding_desktop')); ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="wpstg-btn wpstg-btn-lg wpstg-btn-outline wpstg-onboarding-card__cta"
                data-wpstg-onboarding-action="desktop"
            >
                <?php esc_html_e('Get WP STAGING Desktop', 'wp-staging'); ?>
                <svg class="wpstg-btn-icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M15 3h6v6"></path>
                    <path d="M10 14 21 3"></path>
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                </svg>
            </a>
        </div>

    </div>

    <button type="button" class="wpstg-onboarding__skip" data-wpstg-onboarding-finish>
        <?php esc_html_e('Skip for now', 'wp-staging'); ?>
    </button>
</div>
