<?php

/**
 * @see \WPStaging\Backend\Administrator::ajaxStartClone A place where this view is being called.
 * @var \WPStaging\Backend\Modules\Jobs\Cloning $cloning
 */

use WPStaging\Framework\Facades\Escape;

?>
<div class="successfullying-section">
    <h2 id="wpstg-processing-header"><?php echo esc_html__("Processing, please wait...", "wp-staging")?></h2>
    <div class="wpstg-progress-bar">
        <div class="wpstg-progress" id="wpstg-progress-db"></div>
        <div class="wpstg-progress" id="wpstg-progress-sr"></div>
        <div class="wpstg-progress" id="wpstg-progress-dirs"></div>
        <div class="wpstg-progress" id="wpstg-progress-files"></div>
    </div>
    <div class="wpstg-clear-both">
        <div id="wpstg-processing-status"></div>
        <div id="wpstg-processing-timer"></div>
    </div>
    <div class="wpstg-clear-both"></div>
</div>

<button type="button" id="wpstg-cancel-cloning" class="wpstg-button--primary wpstg-button--red">
    <?php echo esc_html__("Cancel", "wp-staging")?>
</button>

<button type="button" id="wpstg-resume-cloning" class="wpstg-link-btn wpstg-button--primary wpstg-button--blue">
    <?php echo esc_html__("Resume", "wp-staging")?>
</button>

<button type="button" id="wpstg-show-log-button" class="button" data-clone="<?php echo esc_attr($cloning->getOptions()->clone) ?>" style="margin-top: 5px;display:none;">
    <?php esc_html_e('Display working log', 'wp-staging')?>
</button>

<div>
    <span id="wpstg-cloning-result"></span>
</div>

<div id="wpstg-finished-result" class="wpstg--grey">
    <h3><?php esc_html_e('Congratulations', 'wp-staing') ?></h3>
    <?php
    $subDirectory = str_replace(get_home_path(), '', ABSPATH);
    $helper = new \WPStaging\Core\Utils\Helper();
    $url = $helper->getHomeUrl() . str_replace('/', '', $subDirectory);
    echo sprintf(
        Escape::escapeHtml(__('WP STAGING successfully created a staging site in a sub-directory of your main site accessable from:<br><strong><a href="%1$s" target="_blank" id="wpstg-clone-url-1">%1$s</a></strong>', 'wp-staging')),
        esc_url($url)
    );
    ?>
    <br>
    <br>
    <a href="" class="wpstg-button--primary" id="wpstg-home-link">
        <?php echo esc_html__("BACK", "wp-staging")?>
    </a>
    <a href="<?php echo esc_url($url); ?>" id="wpstg-clone-url" target="_blank" class="wpstg-link-btn wpstg-blue-primary">
        <?php esc_html_e('Open Staging Site', 'wp-staging') ?>
    </a>
    <div id="wpstg-success-notice">
        <h3>
            <?php esc_html_e("Please read this first:", "wp-staging")?>
        </h3>
        <ul>
            <li>
                <strong><?php echo sprintf(esc_html__('1. Post name permalinks on your %s have been disabled for technical reasons. ', 'wp-staging'), '<span class="wpstg-font-italic">' . esc_html__('staging site', 'wp-staging') . '</span>') ?></strong>
                <br>
                <?php esc_html_e('Usually this will not affect your staging website. In 99% of all cases you do not need to activate permalinks.', 'wp-staging') ?>
                <br>
                <p>
                    <?php esc_html_e('If Apache is the webserver there is a good chance that permalinks can be activated without further modifications. Try to activate them from', 'wp-staging') ?> <br/>
                    <br>
                    <strong>Staging Site > wp-admin > Settings > Permalinks</strong></a>
                    <br/><br/>
                    <?php esc_html_e('If this does not work or Nginx webserver is used there might be some modifications needed in the files .htaccess (Apache) or *.conf (Nginx).', 'wp-staging') ?>
                </p>
                <p>
                    <strong><?php echo sprintf(
                        Escape::escapeHtml(__('<a href="%s" target="_blank">Read this tutorial</a> to learn how to enable permalinks on the staging site.', 'wp-staging')),
                        'https://wp-staging.com/docs/activate-permalinks-staging-site/?utm_source=wpstg_admin&utm_medium=finish_screen&utm_campaign=tutorial'
                    ) ?></strong>
                </p>
            </li>
            <li>
                <strong><?php esc_html_e('2. Verify that you are REALLY working on your staging site and NOT on your production site if you are not 100% sure!', 'wp-staging') ?> </strong>
                <br>
                <?php esc_html_e('Your main and your staging site are both reachable under the same domain so <br> this can be confusing.', 'wp-staging') ?>
                <p>
                    <?php esc_html_e('To make it more clear when you work on the staging site WP Staging changed the color of the admin bar:', 'wp-staging') ?>
                    <br><br>
                    <img src="<?php echo esc_url($this->assets->getAssetsUrl("img/admin_dashboard.png")) ?>">
                    <br>
                    <?php esc_html_e('On the front page the site name also changed to', 'wp-staging') ?> <br>
                    <strong class="wpstg-font-italic">
                        "STAGING - <span class="wpstg-clone-name"><?php echo esc_html(get_bloginfo("name")) ?></span>"
                    </strong>.
                </p>
            </li>
        </ul>
    </div>
</div>

<div id="wpstg-error-wrapper">
    <div id="wpstg-error-details"></div>
</div>

<div class="wpstg-log-details"></div>
