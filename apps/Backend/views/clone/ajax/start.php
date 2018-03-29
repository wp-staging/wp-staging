<div class=successfullying-section">
    <h2 id="wpstg-processing-header"><?php echo __("Processing, please wait...", "wpstg")?></h2>
    <div class="wpstg-progress-bar">
        <div class="wpstg-progress" id="wpstg-progress-db" style="width:0;overflow: hidden;"></div>
        <div class="wpstg-progress" id="wpstg-progress-sr" style="width:0;background-color:#3c9ee4;overflow: hidden;"></div>
        <div class="wpstg-progress" id="wpstg-progress-dirs" style="width:0;background-color:#3a96d7;overflow: hidden;"></div>
        <div class="wpstg-progress" id="wpstg-progress-files" style="width:0;background-color:#378cc9;overflow: hidden;"></div>
    </div>
    <div style="clear:both;">
        <div id="wpstg-processing-status"></div>
        <div id="wpstg-processing-timer"></div>
</div>
    <div style="clear: both;"></div>
</div>

<!--<div class="wpstg-cloning-section">
    <?php //echo __("Prepare Directories", "wpstg")?>
    <div class="wpstg-progress-bar">
        <div class="wpstg-progress" id="wpstg-directories-progress" style="width:0"></div>
    </div>
</div>-->

<!--<div class="wpstg-cloning-section">
    <?php //echo __("Copy Files", "wpstg")?>
    <div class="wpstg-progress-bar">
        <div class="wpstg-progress" id="wpstg-files-progress" style="width:0"></div>
    </div>
</div>

<div class="wpstg-cloning-section">
    <?php //echo __("Replace Data", "wpstg")?>
    <div class="wpstg-progress-bar">
        <div class="wpstg-progress" id="wpstg-links-progress" style="width:0"></div>
    </div>
</div>-->

<button type="button" id="wpstg-cancel-cloning" class="wpstg-link-btn button-primary">
    <?php echo __("Cancel", "wpstg")?>
</button>

<button type="button" id="wpstg-show-log-button" class="button" data-clone="<?php echo $cloning->getOptions()->clone?>" style="margin-top: 5px;display:none;">
    <?php _e('Display working log', 'wpstg')?>
</button>

<div>
    <span id="wpstg-cloning-result"></span>
</div>

<div id="wpstg-finished-result">
    <h3>Congratulations
    </h3>
    <?php
    $subDirectory = str_replace( get_home_path(), '', ABSPATH ); 
    $url = get_home_url() . str_replace('/', '', $subDirectory);
    echo sprintf( __( 'WP Staging successfully created a staging site in a sub-directory of your main site accessable from:<br><strong><a href="%1$s" target="_blank" id="wpstg-clone-url-1">%1$s</a></strong>', 'wpstg' ), $url );
    ?>
    <br>
    <?php //echo __('Open and access the staging site: ', 'wpstg')?>
    <br>
    <a href="<?php echo $url; ?>" id="wpstg-clone-url" target="_blank" class="wpstg-link-btn button-primary">
        Open staging site <span style="font-size: 10px;">(login with your admin credentials)</span>
    </a>
    <!--<a href="" class="wpstg-link-btn button-primary" id="wpstg-remove-cloning">
        <?php //echo __("Remove", "wpstg")?>
    </a>//-->
    <a href="" class="wpstg-link-btn button-primary" id="wpstg-home-link">
        <?php echo __("Start again", "wpstg")?>
    </a>
    <div id="wpstg-success-notice">
        <h3 style="margin-top:0px;">
            <?php _e("Important Notes:", "wpstg")?>
        </h3>
        <ul>
            <li>
                <strong>1. Permalinks on your <span style="font-style:italic;">staging site</span> are disabled for technical reasons. </strong>
                <br>
                Usually this is absolutely ok for a staging website and you do not need to use permalinks at all.
                <br>
                <p>
                    If your site is executed by the Apache webserver there is a good chance that permalinks are working without much efforts. 
                    In that case, try to activate the permalinks from <br/>
                    Staging Site > wp-admin > Settings > Permalinks</a>
                    <br/><br/>
                    If that does not work or you are using Nginx webserver you need to do a few modifications to the .htaccess (Apache) or *.conf (Nginx).
                    <br>
                    WP Staging can not do these modifications automatically.
                </p>
                <p>
                    <strong><a href="https://wp-staging.com/docs/activate-permalinks-staging-site/?utm_source=wpstg_admin&utm_medium=finish_screen&utm_campaign=tutorial" target="_blank">Read here</a> how to activate permalinks.</strong>
                </p>
            </li>
            <li>
                <strong>2. Verify that you are REALLY working on your staging site and NOT on your production site if you are uncertain! </strong>
                <br>
                Your main and your staging site are both reachable under the same domain so
                <br>
                it´s easy to get confused.
                <p>
                    To assist you we changed the color of the admin bar and the name of the dashboard link to
                    <strong style="font-style:italic;">
                        "STAGING - <span class="wpstg-clone-name"><?php echo get_bloginfo("name")?></span>"
                    </strong>.
                    <br>
                    You will notice this new name in the admin bar:
                    <br><br>
                    <img src="<?php echo $this->url . "/img/admin_dashboard.png" ?>">
                </p>
            </li>
        </ul>
    </div>
</div>

<div id="wpstg-error-wrapper">
    <div id="wpstg-error-details"></div>
</div>

<div id="wpstg-log-details"></div>