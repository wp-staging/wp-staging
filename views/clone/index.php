<?php

/**
 * @see \WPStaging\Backend\Administrator::getClonePage()
 * @see \WPStaging\Backend\Administrator::getBackupPage()
 * @var bool $isBackupPage
 * @var bool $isStagingPage
 */

use WPStaging\Backup\BackupNextOffer;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Notices\CliIntegrationNotice;
use WPStaging\Framework\Notices\Notices;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Language\Language;
use WPStaging\Framework\Onboarding\FreeOnboarding;
use WPStaging\Framework\Onboarding\NextStepRenderer;
use WPStaging\Framework\Onboarding\OnboardingJourney;
use WPStaging\Framework\Onboarding\QueuedBackup;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

$notice = WPStaging::make(Notices::class);

$onboarding = FreeOnboarding::resolve();

if ($onboarding !== null) {
    $onboarding->recordExposure();
}

$journey        = $onboarding === null ? null : $onboarding->getJourney();
$journeyStep    = $onboarding === null ? '' : $onboarding->getJourneyStep();
$isPreConsent   = $onboarding !== null && $onboarding->isPreConsent();
$showBackupNext = $onboarding !== null && $onboarding->isTaskSelector();

$showSelector        = $journeyStep === OnboardingJourney::STEP_SELECT;
$completedCapability = $journey === null ? '' : $journey->getAction(OnboardingJourney::POSITION_FIRST);
$nextCapability      = $journey === null ? '' : $journey->getNextCapability();
$hasFirstSuccess     = $journey !== null && $journey->isFirstCapabilityCompleted();

$secondAction = $journey === null ? '' : $journey->getAction(OnboardingJourney::POSITION_SECOND);

$nextStepRenderer  = $hasFirstSuccess ? NextStepRenderer::resolve() : null;
$nextStepMarkup    = $nextStepRenderer === null ? '' : $nextStepRenderer->render();
$isNextStepVisible = $nextStepMarkup !== '';

$isFocusMode = $isPreConsent || $journeyStep !== '';

$isFirstRunOffer = $showBackupNext && $journey !== null && $journey->getNextCapability() !== OnboardingJourney::CAPABILITY_STAGING;
 
 
 
$backupNextOffer = (!$isFocusMode && class_exists(BackupNextOffer::class)) ? BackupNextOffer::resolve() : null;
$offerBackupNext = $isFirstRunOffer || ($backupNextOffer !== null && $backupNextOffer->isEligible());

$adminUrl           = admin_url('admin.php?page=');
$queuedBackupStatus = $offerBackupNext || $showBackupNext ? WPStaging::make(QueuedBackup::class)->getStatus() : '';
$runningCapability  = $journeyStep === OnboardingJourney::STEP_RUNNING ? $journey->getRunningCapability() : '';

$activeCapability = $journeyStep === OnboardingJourney::STEP_RUNNING ? $journey->getActiveCapability() : '';
$showProgress     = $activeCapability !== '' && !$isNextStepVisible;

 
$onboardingHiddenClass = ($isPreConsent || $showSelector || $isNextStepVisible || $showProgress) ? ' wpstg-onboarding-hidden' : '';
if ($isNextStepVisible && $secondAction === '') {
    $journey->recordNextOfferShown();
}

$isCalledFromIndex = true;
?>

<div id="wpstg-clonepage-wrapper" class="<?php echo $isFocusMode ? 'wpstg-onboarding-focus' : ''; ?>">
    <?php
    if (!$isFocusMode) {
        require_once($this->viewsPath . (WPStaging::isPro() ? 'pro/_main/header.php' : '_main/header.php'));

        do_action('wpstg_notifications');
    }

    if (empty($isStagingPage)) {
        echo "<script>window.addEventListener('DOMContentLoaded', function() {window.dispatchEvent(new Event('backups-tab'));});</script>";
        $classStagingPageActive = '';
        $classBackupPageActive  = 'wpstg--tab--active';
    } else {
        $classStagingPageActive = 'wpstg--tab--active';
        $classBackupPageActive  = '';
    }

    ?>
    <div class="wpstg--tab--wrapper">
        <?php
        if (!$isFocusMode) {
            require_once(WPSTG_VIEWS_DIR . 'navigation/web-template.php');
        }
        ?>

        <div class="wpstg-header">
            <?php
            if (!$isFocusMode && !WPStaging::isBasic()) {
                require_once($this->viewsPath . 'pro/notices/update-notification.php');
            }
            ?>
        </div>

        <div class="wpstg-loading-bar-container">
            <div class="wpstg-loading-bar"></div>
        </div>

        <div id="wpstg-error-wrapper">
            <div id="wpstg-error-details"></div>
        </div>

        <div class="wpstg--tab--contents <?php echo $isStagingPage ? 'min-h-152' : 'min-h-375'; ?>">
            <?php
 
 
            if ($isFocusMode) : ?>
                <div class="wpstg-onboarding__logo">
                    <?php require WPSTG_VIEWS_DIR . 'notices/_partial/wp-staging-logo-svg.php'; ?>
                </div>
            <?php endif;

            if ($isPreConsent) {
                require WPSTG_VIEWS_DIR . 'onboarding/pre-consent.php';
            } elseif ($showSelector) {
                require WPSTG_VIEWS_DIR . 'onboarding/first-run.php';
            }

            if ($showProgress) {
                require WPSTG_VIEWS_DIR . 'onboarding/progress.php';
            }

 
 
            if ($isFocusMode) : ?>
                <div class="wpstg-onboarding-job" data-wpstg-inline-progress hidden></div>
            <?php endif;

            echo $nextStepMarkup; // phpcs:ignore WPStagingCS.Security.EscapeOutput.OutputNotEscaped -- Markup from NextStepRenderer; every value is escaped in onboarding/next-step.php.
            ?>
            <div id="wpstg--tab--staging" class="wpstg--tab--content wpstg-onboarding-pane <?php echo esc_attr($classStagingPageActive) . esc_attr($onboardingHiddenClass); ?>">
            <?php
            $notice->maybeShowElementorCloudNotice();
            if ($this->siteInfo->isHostedOnWordPressCom()) {
                require $this->viewsPath . 'staging/wordpress-com/index.php';
            } elseif (!defined('WPSTGPRO_VERSION') && is_multisite()) {
                require $this->viewsPath . 'staging/free-version.php';
            } elseif (!$this->siteInfo->isCloneable()) {
                require $this->viewsPath . 'staging/staging-site/index.php';
            } elseif (defined('WPSTGPRO_VERSION') && is_multisite()) {
                do_action(TemplateEngine::ACTION_MULTI_SITE_CLONE_OPTION);
            } else {
                require $this->viewsPath . 'staging/index.php';
            }
            ?>
            </div>
            <div id="wpstg--tab--backup" class="wpstg--tab--content wpstg-onboarding-pane <?php echo esc_attr($classBackupPageActive) . esc_attr($onboardingHiddenClass); ?>">
                <?php
                $cliNotice = WPStaging::make(CliIntegrationNotice::class);
                $cliNotice->maybeShowCliNotice();
 
                $cliNotice->maybeRenderCliModalForDockCta();
                ?>
                <div id="wpstg-backup-content">
                    <div class="wpstg-backup-listing-skeleton wpstg-animate-pulse wpstg-py-4">
                        <div class="wpstg-space-y-3">
                            <div class="wpstg-h-4 wpstg-bg-gray-200 wpstg-rounded wpstg-w-1/4 dark:wpstg-bg-gray-700"></div>
                            <div class="wpstg-h-3 wpstg-bg-gray-200 wpstg-rounded wpstg-w-full dark:wpstg-bg-gray-700"></div>
                            <div class="wpstg-h-3 wpstg-bg-gray-200 wpstg-rounded wpstg-w-5/6 dark:wpstg-bg-gray-700"></div>
                            <div class="wpstg-h-3 wpstg-bg-gray-200 wpstg-rounded wpstg-w-4/5 dark:wpstg-bg-gray-700"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wpstg-did-you-know-footer wpstg-onboarding-pane<?php echo esc_attr($onboardingHiddenClass); ?>">
                <?php echo sprintf(
                    Escape::escapeHtml(__('Need to move this site elsewhere? You can also use backup files for transfers. <a href="%s" target="_blank">Read more</a>', 'wp-staging')),
                    Language::localizeDocsUrl('https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/')
                ); ?>
            </div>
        </div>
    </div>
    <?php
    if (!$isFocusMode) {
        require_once($this->viewsPath . '_main/footer.php');
    }
    ?>
    <script>
      // Dismiss handler for the compact general "Upgrade to Pro" card. The staging
      // listing is injected via innerHTML (scripts inside it do not execute), so
      // this is delegated from the document to work on injected content.
      (function () {
        document.addEventListener('click', function (event) {
          var trigger = event.target.closest('.wpstg-pro-card-dismiss');
          if (!trigger) {
            return;
          }

          event.preventDefault();

          var body = new URLSearchParams();
          body.append('action', 'wpstg_dismiss_notice');
          body.append('nonce', wpstg.nonce);
          body.append('wpstg_notice', 'general_pro_card');

          fetch(ajaxurl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body.toString()
          }).then(function (response) {
            return response.json();
          }).then(function (success) {
            // Only hide once the snooze was persisted; otherwise the card would
            // vanish on a failed request and reappear on the next reload.
            if (success !== true) {
              return;
            }

            var card = document.querySelector('.wpstg-general-pro-card');
            if (card) {
              card.style.display = 'none';
            }
          }).catch(function () {});
        });
      })();
    </script>
    <?php
 
 
 
    ?>
    <div id="wpstg-staging-review-content" style="display:none;">
        <?php include WPSTG_VIEWS_DIR . 'notices/review-prompt-modal.php'; ?>
    </div>
    <?php require_once(WPSTG_VIEWS_DIR . 'notices/review-prompt-handlers.php'); ?>

    <?php if ($offerBackupNext) : ?>
        <div id="wpstg-backup-next-content" style="display:none;">
            <?php include WPSTG_VIEWS_DIR . 'onboarding/backup-next.php'; ?>
        </div>
    <?php endif; ?>
</div>
