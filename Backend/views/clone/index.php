<?php

/**
 * @see \WPStaging\Backend\Administrator::getClonePage()
 * @see \WPStaging\Backend\Administrator::getBackupPage()
 * @var bool $isBackupPage
 */

use WPStaging\Framework\Notices\Notices;

?>

<div id="wpstg-clonepage-wrapper">
    <?php
    require_once($this->path . 'views/_main/header.php');

    do_action('wpstg_notifications');

    if (isset($isBackupPage)) {
        echo "<script>window.addEventListener('DOMContentLoaded', function() {window.dispatchEvent(new Event('backups-tab'));});</script>";
        $classStagingPageActive = '';
        $classBackupPageActive  = 'wpstg--tab--active';
    } else {
        $classStagingPageActive = 'wpstg--tab--active';
        $classBackupPageActive  = '';
    }

    ?>
    <div class="wpstg--tab--wrapper">
        <div class="wpstg--tab--header">
            <ul>
                <li>
                    <a class="wpstg--tab--content <?php echo esc_attr($classStagingPageActive); ?> wpstg-button" data-target="#wpstg--tab--staging" id="wpstg--tab--toggle--staging">
                        <?php esc_html_e('Staging', 'wp-staging') ?>
                    </a>
                </li>
                <li>
                    <a class="wpstg-button <?php echo esc_attr($classBackupPageActive); ?>" data-target="#wpstg--tab--backup" id="wpstg--tab--toggle--backup">
                        <?php esc_html_e('Backup & Migration', 'wp-staging') ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url(get_admin_url()) . 'admin.php?page=wpstg-settings'; ?>" class="wpstg-button" data-target="" id="wpstg--tab--toggle--settigs">
                        <?php esc_html_e('Settings', 'wp-staging') ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url(get_admin_url()) . 'admin.php?page=wpstg-tools'; ?>" class="wpstg-button" data-target="" id="wpstg--tab--toggle--systeminfo">
                        <?php esc_html_e('System Info', 'wp-staging') ?>
                    </a>
                </li>
                <?php
                if (defined('WPSTGPRO_VERSION')) {
                    $licenseMessage = isset($license->license) && $license->license === 'valid' ? '' : __('(Unregistered)', 'wp-staging');
                    ?>
                    <li>
                        <a href="<?php echo esc_url(get_admin_url()) . 'admin.php?page=wpstg-license'; ?>" class="wpstg-button" data-target="" id="wpstg--tab--toggle--license">
                            <?php esc_html_e('License', 'wp-staging'); ?> <span class="wpstg--red-warning"><?php echo esc_html($licenseMessage); ?></span>
                        </a>
                    </li>
                    <?php
                } else {
                    ?>
                    <li>
                        <a href="https://wp-staging.com" target="_blank" class="wpstg-button" data-target="" id="wpstg--tab--toggle--license">
                            <span class="wpstg--red-warning" style=""><?php echo esc_html(__('Upgrade to Pro', 'wp-staging')); ?> </span>
                        </a>
                    </li>
                    <?php
                }
                ?>
                <li class="wpstg-tab-item--vert-center">
                    <span class="wpstg-loader"></span>
                </li>
            </ul>
        </div>
        <div class="wpstg-header">
            <?php if (isset($_GET['page']) && $_GET['page'] === 'wpstg_clone' || $_GET['page'] === 'wpstg_backup') { ?>
                <?php
                $latestReleasedVersion = get_option('wpstg_version_latest');
                $display               = 'none;';

                if (defined('WPSTGPRO_VERSION')) {
                    $outdatedVersionCheck  = new WPStaging\Framework\Notices\OutdatedWpStagingNotice();
                    $latestReleasedVersion = $outdatedVersionCheck->getLatestWpstgProVersion();
                    if ($outdatedVersionCheck->isOutdatedWpStagingProVersion()) {
                        $display = 'block;';
                    }
                }

                if (Notices::SHOW_ALL_NOTICES){
                    $display = 'block;';
                }
                ?>

                <div id="wpstg-update-notify" style="display:<?php echo esc_attr($display); ?>">
                    <strong><?php echo sprintf(__("New: WP Staging Pro v. %s is available.", 'wp-staging'), esc_html($latestReleasedVersion)); ?></strong><br/>
                    <?php echo sprintf(__('Important: It\'s recommended to update the plugin before pushing a staging site to the live site. <a href="%s" target="_blank">What\'s New?</a>', 'wp-staging'), 'https://wp-staging.com/wp-staging-pro-changelog'); ?>
                </div>

            <?php } ?>
        </div>
        <div class="wpstg--tab--contents">
            <div id="wpstg--tab--staging" class="wpstg--tab--content <?php echo esc_attr($classStagingPageActive); ?>">
                <?php
                if (!$this->siteInfo->isCloneable()) {
                    // Staging site but not cloneable
                    require_once($this->path . "views/clone/staging-site/index.php");
                } elseif (!defined('WPSTGPRO_VERSION') && is_multisite()) {
                    require_once($this->path . "views/clone/multi-site/index.php");
                } else {
                    require_once($this->path . "views/clone/single-site/index.php");
                }
                ?>
            </div>
            <div id="wpstg--tab--backup" class="wpstg--tab--content <?php echo esc_attr($classBackupPageActive); ?>">
                <?php
                if (defined('WPSTGPRO_VERSION')) {
                    esc_html_e('Loading...', 'wp-staging');
                } else {
                    require_once($this->path . "views/backup/free-version.php");
                }
                ?>
            </div>
        </div>
        <?php
        // Show ad for pro version
        if (!defined('WPSTGPRO_VERSION')) {
            echo '        <div class="wpstg--tab-contents">';
            require $this->path . 'views/ads/advert-pro-version.php';
            echo '</div>';
        }
        ?>
    </div>
    <?php require_once($this->path . 'views/_main/footer.php') ?>
</div>
