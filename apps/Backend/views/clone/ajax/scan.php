<label id="wpstg-clone-label" for="wpstg-new-clone">
    <?php echo __("Name your new site, e.g. staging, dev (keep it short):", "wpstg")?>
    <input type="text" id="wpstg-new-clone-id" value="<?php echo $clone; ?>" <?php echo $disabled; ?>>
</label>

<?php if (false === $options->hasEnoughDiskSpace):?>
    <span class="wpstg-error-msg" id="wpstg-clone-id-error">
            <?php echo __(
                "<br>Probably not enough free disk space to create a staging site. ".
                "<br> You can continue but its likely that the copying process will fail.",
                "wpstg"
            )?>
    </span>
<?php endif?>

<div class="wpstg-tabs-wrapper">
    <a href="#" class="wpstg-tab-header active" data-id="#wpstg-scanning-db">
        <span class="wpstg-tab-triangle">&#9658;</span>
        <?php echo __("DB Tables", "wpstg")?>
    </a>

    <div class="wpstg-tab-section" id="wpstg-scanning-db">
        <?php do_action("wpstg_scanning_db")?>
        <h4 style="margin:0">
            <?php echo __(
                "Uncheck the tables you do not want to copy. (If the copy process was previously interrupted, ".
                "successfully copied tables are greyed out and copy process will skip these ones)",
                "wpstg"
            )?>
        </h4>
        <?php
        foreach ($options->tables as $table):
            $attributes = in_array($table->Name, $options->uncheckedTables) ? '' : "checked";
            $attributes .= in_array($table->Name, $options->clonedTables) ? " disabled" : '';
            ?>
            <div class="wpstg-db-table">
                <label>
                    <input class="wpstg-db-table-checkboxes" type="checkbox" name="<?php echo $table->Name?>" <?php echo $attributes?>>
                    <?php echo $table->Name?>
                </label>
                <span class="wpstg-size-info">
				<?php echo $scan->formatSize($table->Data_length + $table->Index_length)?>
			</span>
            </div>
        <?php endforeach ?>
        <div>
            <a href="#" class="wpstg-button-unselect">
                Unselect all tables
            </a>
        </div>
    </div>

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-scanning-files">
        <span class="wpstg-tab-triangle">&#9658;</span>
        <?php echo __("Files", "wpstg")?>
    </a>

    <div class="wpstg-tab-section" id="wpstg-scanning-files">
        <?php
        echo '<h4 style="margin:0px;">' . __('Uncheck the folders you do not want to copy. Click on them for expanding!', 'wpstg') . '<h4>';
        wpstg_directory_structure($folders, null, false, false, $excluded_folders);
        wpstg_show_large_files();
        echo '<p><span id=wpstg-file-summary>' . __('Files will be copied into subfolder of: ','wpstg') . wpstg_get_clone_root_path() . '</span>';
        ?>
    </div>

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-advanced-settings">
        <span class="wpstg-tab-triangle">&#9658;</span>
        <?php echo __("Advanced Options", "wpstg")?>
    </a>

    <div class="wpstg-tab-section" id="wpstg-advanced-settings">
        <?php echo wpstg_advanced_settings()?>
    </div>

</div>

<button type="button" class="wpstg-prev-step-link wpstg-link-btn button-primary">
    <?php _e("Back", "wpstg")?>
</button>

<button type="button" id="wpstg-start-cloning" class="wpstg-next-step-link wpstg-link-btn button-primary" data-action="wpstg_cloning">
    <?php  echo wpstg_return_button_title();?>
</button>