<div id="wpstg-clonepage-wrapper">
    <?php
    require_once($this->path . 'views/_main/header.php');
    require_once($this->path . 'views/_main/report-issue.php');
    do_action('wpstg_notifications');

    $display = '';
    if (!defined('WPSTGPRO_VERSION')){
        $display = 'display:none;';
    }
    ?>
    <div class="wpstg--tab--wrapper">
        <div class="wpstg--tab--header">
            <ul>
                <li style="<?php echo $display ?>">
                    <a class="wpstg--tab--content wpstg--tab--active wpstg-button" data-target="#wpstg--tab--staging">
                        <?php _e('Staging Sites', 'wp-staging') ?>
                    </a>
                </li>
                <li style="<?php echo $display ?>">
                    <a class="wpstg-button" data-target="#wpstg--tab--snapshot">
                        <?php _e('Backups', 'wp-staging') ?>
                    </a>
                </li>
                <li>
                    <span class="wpstg-loader"></span>
                </li>
            </ul>
        </div>
        <div class="wpstg--tab--contents">
            <div id="wpstg--tab--staging" class="wpstg--tab--content wpstg--tab--active">
                <?php
                if (wpstg_is_stagingsite()) {
                    // Staging site
                    require_once($this->path . "views/clone/staging-site/index.php");
                } elseif (!defined('WPSTGPRO_VERSION') && is_multisite()) {
                    require_once($this->path . "views/clone/multi-site/index.php");
                } // Single site
                else {
                    require_once($this->path . "views/clone/single-site/index.php");
                }
                ?>
            </div>
            <div id="wpstg--tab--snapshot" class="wpstg--tab--content">
                Snapshots
            </div>
        </div>
    </div>
    <?php require_once($this->path . 'views/_main/footer.php') ?>
</div>
