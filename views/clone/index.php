<?php

/**
 * @see \WPStaging\Backend\Administrator::getClonePage()
 * @see \WPStaging\Backend\Administrator::getBackupPage()
 * @var bool $isBackupPage
 * @var bool $isStagingPage
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Notices\BackupPluginsNotice;
use WPStaging\Framework\Notices\CliIntegrationNotice;
use WPStaging\Framework\Notices\Notices;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Language\Language;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

$backupNotice = WPStaging::make(BackupPluginsNotice::class);
$notice       = WPStaging::make(Notices::class);

$isCalledFromIndex = true;
?>

<div id="wpstg-clonepage-wrapper">
    <?php
    if (WPStaging::isPro()) {
        require_once($this->viewsPath . 'pro/_main/header.php');
    } else {
        require_once($this->viewsPath . '_main/header.php');
    }

    do_action('wpstg_notifications');

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
        require_once(WPSTG_VIEWS_DIR . 'navigation/web-template.php');
        ?>

        <div class="wpstg-header">
            <?php
            if (!WPStaging::isBasic()) {
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
            <div id="wpstg--tab--staging" class="wpstg--tab--content <?php echo esc_attr($classStagingPageActive); ?>">
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
            <div id="wpstg--tab--backup" class="wpstg--tab--content <?php echo esc_attr($classBackupPageActive); ?>">
                <?php
                $cliNotice = WPStaging::make(CliIntegrationNotice::class);
                $cliNotice->maybeShowCliNotice();
                // When banner is dismissed but dock CTA should be shown, render modal separately
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
            <div class="wpstg-did-you-know-footer">
                <?php echo sprintf(
                    Escape::escapeHtml(__('Need to move this site elsewhere? You can also use backup files for transfers. <a href="%s" target="_blank">Read more</a>', 'wp-staging')),
                    Language::localizeDocsUrl('https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/')
                ); ?>
            </div>
        </div>
    </div>
    <?php require_once($this->viewsPath . '_main/footer.php'); ?>
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
    // Hidden host for the staging-created success modal's review block. Kept on
    // the persistent page (not the AJAX-injected listing) so it is available when
    // the success modal is built. Empty unless Free and review-eligible.
    ?>
    <div id="wpstg-staging-review-content" style="display:none;">
        <?php include WPSTG_VIEWS_DIR . 'notices/review-prompt-modal.php'; ?>
    </div>
    <?php require_once(WPSTG_VIEWS_DIR . 'notices/review-prompt-handlers.php'); ?>
</div>
