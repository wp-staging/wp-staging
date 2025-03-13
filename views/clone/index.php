<?php

/**
 * @see \WPStaging\Backend\Administrator::getClonePage()
 * @see \WPStaging\Backend\Administrator::getBackupPage()
 * @var bool $isBackupPage
 * @var bool $isStagingPage
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Notices\BackupPluginsNotice;
use WPStaging\Framework\Notices\Notices;
use WPStaging\Framework\Facades\Escape;

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
        // Show ad for pro version
        if (!WPStaging::isPro()) {
            require $this->viewsPath . 'ads/advert-pro-version.php';
        }
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

        <div class="wpstg--tab--contents">
            <?php
                $numberOfLoadingBars = 9;
                include(WPSTG_VIEWS_DIR . '_main/loading-placeholder.php');
            ?>
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
                do_action('wpstg.views.ajax_clone.multi_site_clone_option');
            } else {
                require $this->viewsPath . 'staging/index.php';
            }
            ?>
            </div>
            <div id="wpstg--tab--backup" class="wpstg--tab--content <?php echo esc_attr($classBackupPageActive); ?>">
            </div>
            <div class="wpstg-did-you-know-footer">
                <?php echo sprintf(
                    Escape::escapeHtml(__('Note: You can upload backup files to another site to transfer a website. <a href="%s" target="_blank">Read more</a>', 'wp-staging')),
                    'https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/'
                ); ?>
            </div>
        </div>
    </div>
    <?php require_once($this->viewsPath . '_main/footer.php'); ?>
</div>
