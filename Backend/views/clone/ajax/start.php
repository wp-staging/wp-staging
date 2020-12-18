<div class=successfullying-section">
    <h2 id="wpstg-processing-header"><?php echo __("Processing, please wait...", "wp-staging")?></h2>
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

<button type="button" id="wpstg-cancel-cloning" class="wpstg-button wpstg-link-btn wpstg-blue-primary">
    <?php echo __("Cancel", "wp-staging")?>
</button>

<button type="button" id="wpstg-resume-cloning" class="wpstg-link-btn button-primary">
    <?php echo __("Resume", "wp-staging")?>
</button>

<button type="button" id="wpstg-show-log-button" class="button" data-clone="<?php echo $cloning->getOptions()->clone?>" style="margin-top: 5px;display:none;">
    <?php _e('Display working log', 'wp-staging')?>
</button>

<div>
    <span id="wpstg-cloning-result"></span>
</div>

<div id="wpstg-finished-result">
    <h3>Congratulations
    </h3>
    <?php
    $subDirectory = str_replace( get_home_path(), '', ABSPATH ); 
    $helper = new \WPStaging\Core\Utils\Helper();
    $url = $helper->getHomeUrl() . str_replace('/', '', $subDirectory);
    echo sprintf( __( 'WP Staging successfully created a staging site in a sub-directory of your main site accessable from:<br><strong><a href="%1$s" target="_blank" id="wpstg-clone-url-1">%1$s</a></strong>', 'wp-staging' ), $url );
    ?>
    <br>
    <br>
    <a href="" class="wpstg-link-btn wpstg-blue-primary" id="wpstg-home-link">
        <?php echo __("BACK", "wp-staging")?>
    </a>
    <a href="<?php echo $url; ?>" id="wpstg-clone-url" target="_blank" class="wpstg-link-btn wpstg-blue-primary">
        <?php _e('Open Staging Site', 'wp-staging') ?><span style="wpstg-fs-10px"><?php _e('(Login with your admin credentials)', 'wp-staging') ?></span>
    </a>
    <div id="wpstg-success-notice">
        <h3>
            <?php _e("Please read this first:", "wp-staging")?>
        </h3>
        <ul>
            <li>
                <strong>1. Post name permalinks on your <span class="wpstg-font-italic">staging site</span> have been disabled for technical reasons. </strong>
                <br>
                Usually this will not affect your staging website. In 99% of all cases you do not need to activate permalinks.
                <br>
                <p>
                    If Apache is the webserver there is a good chance that permalinks can be activated without further modifications. Try to activate them from <br/>
                    <br>
                    <strong>Staging Site > wp-admin > Settings > Permalinks</strong></a>
                    <br/><br/>
                    If this does not work or Nginx webserver is used there might be some modifications needed in the files .htaccess (Apache) or *.conf (Nginx).
                </p>
                <p>
                    <strong><a href="https://wp-staging.com/docs/activate-permalinks-staging-site/?utm_source=wpstg_admin&utm_medium=finish_screen&utm_campaign=tutorial" target="_blank">Read this tutorial</a> to learn how to enable permalinks on the staging site.</strong>
                </p>
            </li>
            <li>
                <strong>2. Verify that you are REALLY working on your staging site and NOT on your production site if you are not 100% sure! </strong>
                <br>
                Your main and your staging site are both reachable under the same domain so
                <br>
                this can be confusing.
                <p>
                    To make it more clear when you work on the staging site WP Staging changed the color of the admin bar:
                    <br><br>
                    <img src="<?php echo $this->url . "/img/admin_dashboard.png" ?>">
                    <br>
                    On the fronpage the site name also changed to <br>
                    <strong class="wpstg-font-italic">
                        "STAGING - <span class="wpstg-clone-name"><?php echo get_bloginfo("name")?></span>"
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
