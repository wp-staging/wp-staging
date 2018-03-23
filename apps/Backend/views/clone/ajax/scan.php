<label id="wpstg-clone-label" for="wpstg-new-clone">
    <?php echo __('Staging Site Name:', 'wpstg')?>
    <input type="text" id="wpstg-new-clone-id" value="<?php echo $options->current; ?>"<?php if (null !== $options->current) echo " disabled='disabled'"?>>
</label>

<span class="wpstg-error-msg" id="wpstg-clone-id-error" style="display:none;">
        <?php echo __(
            "<br>Probably not enough free disk space to create a staging site. ".
            "<br> You can continue but its likely that the copying process will fail.",
            "wpstg"
        )?>
</span>

<div class="wpstg-tabs-wrapper">
    <a href="#" class="wpstg-tab-header active" data-id="#wpstg-scanning-db">
        <span class="wpstg-tab-triangle">&#9658;</span>
        <?php echo __("DB Tables", "wpstg")?>
    </a>

    <div class="wpstg-tab-section" id="wpstg-scanning-db">
        <?php do_action("wpstg_scanning_db")?>
        <h4 style="margin:0">
            <?php 

            echo __(
                "Uncheck the tables you do not want to copy. Usually you should select tables with prefix '{$scan->prefix}', only.",
                "wpstg"
            )?>
        </h4>
        <div style="margin-top:10px;margin-bottom:10px;">
            <a href="#" class="wpstg-button-unselect button"> None </a>
            <a href="#" class="wpstg-button-select button"> <?php _e(WPStaging\WPStaging::getTablePrefix(), 'wpstg'); ?> </a>
        </div>
        <?php
        //print_r( $options->excludedTables);
        foreach ($options->tables as $table):
            $attributes = in_array($table->name, $options->excludedTables) ? '' : "checked";
            $attributes .= in_array($table->name, $options->clonedTables) ? " disabled" : '';
            ?>
            <div class="wpstg-db-table">
                <label>
                    <input class="wpstg-db-table-checkboxes" type="checkbox" name="<?php echo $table->name?>" <?php echo $attributes?>>
                    <?php echo $table->name?>
                </label>
                <span class="wpstg-size-info">
				<?php echo $scan->formatSize($table->size)?>
			</span>
            </div>
        <?php endforeach ?>
        <div style="margin-top:10px;">
            <a href="#" class="wpstg-button-unselect button"> None </a>
            <a href="#" class="wpstg-button-select button"> <?php _e(WPStaging\WPStaging::getTablePrefix(), 'wpstg'); ?> </a>
        </div>
    </div>

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-scanning-files">
        <span class="wpstg-tab-triangle">&#9658;</span>
        <?php echo __("Files", "wpstg")?>
    </a>

    <div class="wpstg-tab-section" id="wpstg-scanning-files">
        <h4 style="margin:0">
            <?php echo __("Uncheck the folders you do not want to copy. Click on them for expanding!", "wpstg")?>
        </h4>

        <?php echo $scan->directoryListing()?>

        <h4 style="margin:10px 0 10px 0">
            <?php echo __("Extra directories to copy", "wpstg")?>
        </h4>

        <textarea id="wpstg_extraDirectories" name="wpstg_extraDirectories" style="width:100%;height:250px;"></textarea>
        <p>
            <span>
                <?php
                echo __(
                    "Enter one folder path per line.<br>".
                    "Folders must start with absolute path: " . $options->root,
                    "wpstg"
                )
                ?>
            </span>
        </p>

        <p>
            <span>
                <?php
                if (isset($options->clone)){
                echo __("All files will be copied to: ", "wpstg") . $options->root . $options->clone;
                }
                ?>
            </span>
        </p>
    </div>

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-advanced-settings">
        <span class="wpstg-tab-triangle">&#9658;</span>
        <?php echo __("Login Options", "wpstg")?>
    </a>

    <div class="wpstg-tab-section" id="wpstg-advanced-settings">
        <p>
                <?php
                  _e('<strong>Important:</strong> Are you using a custom login url?', 'wpstg');
                  echo '<br/>';
                  echo sprintf(__('Set up first <a href="%1$s"><strong>Login Custom Link</strong></a> if login to the admin dashboard is not reachable from the default url <pre>%2$s</pre>', 'wpstg'),
                        admin_url() . '/admin.php?page=wpstg-settings#wpstg_settings[loginSlug]',
                        admin_url()
                          );
                  _e('<strong>If you do not do that step, the staging site will not be accessable!</strong>', 'wpstg');
                     //$form = $this->di->get("forms")->get("general");
                     //echo $form->label("wpstg_settings['loginPostId']");
                     //echo $form->render("wpstg_settings['loginPostId']");
                ?>
        </p>
    </div>

</div>

<button type="button" class="wpstg-prev-step-link wpstg-link-btn button-primary">
    <?php _e("Back", "wpstg")?>
</button>

    <?php
    if (null !== $options->current)
    {
        $label =  __("Update Clone", "wpstg");
        $action = 'wpstg_update';
        
        echo '<button type="button" id="wpstg-start-updating" class="wpstg-next-step-link  wpstg-link-btn button-primary" data-action="'.$action.'">'.$label.'</button>';
    }
    else
    {
        $label =  __("Start Cloning", "wpstg");
        $action = 'wpstg_cloning';
       
        echo '<button type="button" id="wpstg-start-cloning" class="wpstg-next-step-link wpstg-link-btn button-primary" data-action="'.$action.'">'.$label.'</button>';

    }
    ?>

<a href="#" id="wpstg-check-space"><?php _e('Check Disk Space', 'wpstg'); ?></a>