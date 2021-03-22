<div id="wpstg-clonepage-wrapper">
    <?php
    require_once($this->path . 'views/_main/header.php');
    require_once($this->path . 'views/_main/report-issue.php');
    do_action('wpstg_notifications');

    $display = '';
    if (!defined('WPSTGPRO_VERSION')) {
        $display = 'display:none;';
    }
    ?>
    <div class="wpstg--tab--wrapper">
        <div class="wpstg--tab--header">
            <ul>
                <li style="<?php echo $display ?>">
                    <a class="wpstg--tab--content wpstg--tab--active wpstg-button" data-target="#wpstg--tab--staging">
                        <?php _e('Staging', 'wp-staging') ?>
                    </a>
                </li>
                <?php if(class_exists('\WPStaging\Pro\Backup\BackupServiceProvider') && \WPStaging\Pro\Backup\BackupServiceProvider::isEnabled()): ?>
                <li style="<?php echo $display ?>">
                    <a class="wpstg-button" data-target="#wpstg--tab--backup">
                        <?php _e('Backup & Migrate', 'wp-staging') ?>
                    </a>
                </li>
                <?php else: ?>
                <li style="<?php echo $display ?>">
                    <a class="wpstg-button" data-target="#wpstg--tab--database-backups">
                        <?php _e('Backups', 'wp-staging') ?>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <span class="wpstg-loader"></span>
                </li>
            </ul>
        </div>
        <div class="wpstg--tab--contents">
            <div id="wpstg--tab--staging" class="wpstg--tab--content wpstg--tab--active">
                <?php
                if (!$this->siteInfo->isCloneable()) {
                    // Staging site but not cloneable
                    require_once($this->path . "views/clone/staging-site/index.php");
                } elseif (!defined('WPSTGPRO_VERSION') && is_multisite()) {
                    require_once($this->path . "views/clone/multi-site/index.php");
                } // Single site
                else {
                    require_once($this->path . "views/clone/single-site/index.php");
                }
                ?>
            </div>
            <div id="wpstg--tab--backup" class="wpstg--tab--content">
                <?php _e('Loading...', 'wp-staging') ?>
            </div>
            <div id="wpstg--tab--database-backups" class="wpstg--tab--content">
                <?php _e('Loading...', 'wp-staging') ?>
            </div>
        </div>
    </div>
    <?php require_once($this->path . 'views/_main/footer.php') ?>
</div>
