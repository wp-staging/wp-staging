<div id="wpstg-step-1">
    <button id="wpstg-new-clone" class="wpstg-next-step-link wpstg-link-btn button-primary" data-action="wpstg_scanning">
        <?php echo __("Create new staging site", "wpstg")?>
    </button>
</div>

<?php if (isset($availableClones) && !empty($availableClones)):?>
    <!-- Existing Clones -->
    <div id="wpstg-existing-clones">
        <h3>
            <?php _e("Available Staging Sites:", "wpstg")?>
        </h3>

        <?php foreach ($availableClones as $name => $data):?>
            <div id="<?php echo $data["directoryName"]?>" class="wpstg-clone">

                <?php $urlLogin = $data["url"] . "/wp-login.php"?>

                <a href="<?php echo $urlLogin?>" class="wpstg-clone-title" target="_blank">
                    <?php echo $name?>
                </a>

                <?php echo apply_filters("wpstg_before_stage_buttons", $html = '', $name, $data)?>

                <a href="<?php echo $urlLogin?>" class="wpstg-open-clone wpstg-clone-action" target="_blank">
                    <?php _e("Open", "wpstg")?>
                </a>

                <a href="#" class="wpstg-execute-clone wpstg-clone-action" data-clone="<?php echo $name?>">
                    <?php _e("Edit", "wpstg")?>
                </a>

                <a href="#" class="wpstg-remove-clone wpstg-clone-action" data-clone="<?php echo $name?>">
                    <?php _e("Delete", "wpstg")?>
                </a>

                <?php echo apply_filters("wpstg_after_stage_buttons", $html = '', $name, $data)?>
            </div>
        <?php endforeach?>
    </div>
    <!-- /Existing Clones -->
<?php endif?>

<div id="wpstg-finished-result" style="display:none">
    <h3>Congratulations:</h3>

    <?php
    echo __(
        sprintf(
            "WP Staging successfully created a staging site in a sub-directory of your main site in: ".
            "<strong><a href=\"%s\" target=\"_blank\">%s/<span id='wpstg_staging_name'></span></strong>",
            get_home_url()
        ),
        "wpstg"
    )
    ?>
    <br><br>
    <?php echo __("Now, you have several options: ", "wpstg")?>
    <br>
    <a href="<?php echo get_home_url()?>" id="wpstg-clone-url" target="_blank" class="wpstg-link-btn button-primary">
        Open staging site
        <span style="font-size: 10px;">
            (login with your admin credentials)
        </span>
    </a>
<!--
    <a href="#" class="wpstg-link-btn button-primary" id="wpstg-remove-clone" data-clone="">
        <?php //echo __("Remove", "wpstg")?>
    </a>
//-->
    <a href="" class="wpstg-link-btn button-primary" id="wpstg-home-link">
        <?php echo __("Start again", "wpstg")?>
    </a>

    <div id="wpstg-success-notice">
        <h3 style="margin-top:0px;">
            <?php _e("Important notes:", "wpstg")?>
        </h3>

        <ul>
            <li>
                <strong>
                    1. Permalinks on your <span style="font-style:italic;">staging site</span> will be disabled for technical reasons!
                </strong>
                <br>
                Usually this is no problem for a staging website and you do not have to use permalinks!
                <br>
                <p>
                    If you really need permalinks on your staging site you have to do several modifications to your
                    .htaccess (Apache) or *.conf (Nginx).
                    <br>
                    WP Staging can not do this automatically.
                </p>
                <p>
                    <strong>Read more:</strong>
                    <a href="http://stackoverflow.com/questions/5564881/htaccess-to-rewrite-wordpress-subdirectory-with-permalinks-to-root" target="_blank">
                        Changes .htaccess
                    </a>
                    &nbsp;|&nbsp;
                    <a href="http://robido.com/nginx/nginx-wordpress-subdirectory-configuration-example/" target="_blank">
                        Changes nginx conf
                    </a>
                </p>
            </li>
            <li>
                <strong>
                    2. Verify that you are REALLY working on your staging site and NOT on your production site if you are uncertain!
                </strong>
                <br>
                Your main and your staging site are both reachable under the same domain so
                <br>
                it´s easy to become confused.

                <p>
                    To assist you we changed the name of the dashboard link to
                    <strong style="font-style:italic;">
                        "Staging - <span class="wpstg-clone-name"><?php echo get_bloginfo("name")?></span>"
                    </strong>.
                    <br>
                    You will notice this new name in the admin bar:
                    <br><br>
                    <img src="<?php echo $this->url . "img/admin_dashboard.png"?>">
                </p>
            </li>
        </ul>
    </div>
</div>

<!-- Remove Clone -->
<div id="wpstg-removing-clone">

</div>
<!-- /Remove Clone -->