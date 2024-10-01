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
use WPStaging\Framework\Notices\OutdatedWpStagingNotice;
use WPStaging\Framework\Facades\Escape;

$backupNotice = WPStaging::make(BackupPluginsNotice::class);

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
            <?php if (isset($_GET['page']) && $_GET['page'] === 'wpstg_clone' || $_GET['page'] === 'wpstg_backup') { ?>
                <?php
                $latestReleasedVersion = get_option('wpstg_version_latest');
                $display               = 'none;';

                if (defined('WPSTGPRO_VERSION')) {
                    $outdatedVersionCheck  = new OutdatedWpStagingNotice();
                    $latestReleasedVersion = $outdatedVersionCheck->getLatestWpstgProVersion();
                    if ($outdatedVersionCheck->isOutdatedWpStagingProVersion()) {
                        $display = 'block;';
                    }
                }

                if (Notices::SHOW_ALL_NOTICES) {
                    $display = 'block;';
                }
                ?>

                <div id="wpstg-update-notify" style="display:<?php echo esc_attr($display); ?>">
                    <strong><?php echo sprintf(__("New: WP Staging Pro v. %s is available.", 'wp-staging'), esc_html($latestReleasedVersion)); ?></strong><br/>
                    <?php echo sprintf(__('Important: It\'s recommended to update the plugin before pushing a staging site to the live site. <a href="%s" target="_blank">What\'s New?</a>', 'wp-staging'), 'https://wp-staging.com/wp-staging-pro-changelog'); ?>
                </div>

            <?php } ?>
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
                if (!$this->siteInfo->isCloneable()) {
                    // Staging site but not cloneable
                    require_once($this->viewsPath . "clone/staging-site/index.php");
                } elseif (!defined('WPSTGPRO_VERSION') && is_multisite()) {
                    require_once($this->viewsPath . "clone/multi-site/index.php");
                } else {
                    require_once($this->viewsPath . "clone/single-site/index.php");
                }
                ?>
            </div>
            <div id="wpstg--tab--backup" class="wpstg--tab--content <?php echo esc_attr($classBackupPageActive); ?>">
                <?php
                if (WPStaging::isPro()) {
                    require_once($this->viewsPath . "backup/free-version.php");
                }
                ?>
            </div>
            <div class="wpstg-did-you-know-footer">
                <?php echo sprintf(
                    Escape::escapeHtml(__('Note: You can upload backup files to another site to transfer a website. <a href="%s" target="_blank">Read more</a>', 'wp-staging')),
                    'https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/'
                ); ?>
            </div>
        </div>
    </div>
    <?php require_once($this->viewsPath . '_main/footer.php') ?>
</div>
