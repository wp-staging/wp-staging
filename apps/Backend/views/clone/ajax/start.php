<div class=successfullying-section">
    <?php echo __("Copy Database Tables", "wpstg")?>
    <div class="wpstg-progress-bar">
        <div class="wpstg-progress" id="wpstg-db-progress" style="width:0"></div>
    </div>
</div>

<div class="wpstg-cloning-section">
    <?php echo __("Prepare Directories", "wpstg")?>
    <div class="wpstg-progress-bar">
        <div class="wpstg-progress" id="wpstg-directories-progress" style="width:0"></div>
    </div>
</div>

<div class="wpstg-cloning-section">
    <?php echo __("Copy Files", "wpstg")?>
    <div class="wpstg-progress-bar">
        <div class="wpstg-progress" id="wpstg-files-progress" style="width:0"></div>
    </div>
</div>

<div class="wpstg-cloning-section">
    <?php echo __("Replace Data", "wpstg")?>
    <div class="wpstg-progress-bar">
        <div class="wpstg-progress" id="wpstg-links-progress" style="width:0"></div>
    </div>
</div>

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
    //echo ABSPATH . '<br>';
    //echo get_home_path();
    $subDirectory = str_replace( get_home_path(), '', ABSPATH ); 
    $url = get_home_url() . str_replace('/', '', $subDirectory);
    echo sprintf( __( 'WP Staging successfully created a staging site in a sub-directory of your main site in:<br><strong><a href="%1$s" target="_blank" id="wpstg-clone-url-1">%1$s</a></strong>', 'wpstg' ), $url );
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
                <strong>1. Permalinks on your <span style="font-style:italic;">staging site</span> will be disabled for technical reasons! </strong>
                <br>
                Usually this is no problem for a staging website and you do not need to use permalinks!
                <br>
                <p>
                    If you really want permalinks on your staging site you need to do several modifications to your .htaccess (Apache) or *.conf (Nginx).
                    <br>
                    WP Staging can not do this modification automatically.
                </p>
                <p>
                    <strong>Read more:</strong>
                    <a href="http://stackoverflow.com/questions/5564881/htaccess-to-rewrite-wordpress-subdirectory-with-permalinks-to-root" target="_blank">
                        Changes .htaccess
                    </a> |
                    <a href="http://robido.com/nginx/nginx-wordpress-subdirectory-configuration-example/" target="_blank">
                        Changes nginx conf
                    </a>
                </p>
            </li>
            <li>
                <strong>2. Verify that you are REALLY working on your staging site and NOT on your production site if you are uncertain! </strong>
                <br>
                Your main and your staging site are both reachable under the same domain so
                <br>
                it´s easy to get confused.
                <p>
                    To assist you we changed the name of the dashboard link to
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