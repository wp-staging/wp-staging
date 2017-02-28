<label id="wpstg-clone-label" for="wpstg-new-clone">
    <?php echo __("Name your new site, e.g. staging, dev (keep it short):", "wpstg")?>
    <input type="text" id="wpstg-new-clone-id" value="<?php echo $options->current; ?>"<?php if (null !== $options->current) echo " disabled='disabled'"?>>
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
        <div>
            <a href="#" class="wpstg-button-unselect">
                Un-check All
            </a>
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
            <?php echo __("Extra Directories / Files", "wpstg")?>
        </h4>

        <textarea id="wpstg_extraDirectories" name="wpstg_extraDirectories" style="width:100%;height:250px;"></textarea>
        <p>
            <span>
                <?php
                echo __(
                    "Add additional extra directories you'd like to clone to your staging site<br>".
                    "You must write full path of the directory / file<br>".
                    "Full path must start with " . $options->root,
                    "wpstg"
                )
                ?>
            </span>
        </p>

        <p>
            <span>
                <?php
                echo __("Files will be copied into subfolder of: ", "wpstg") . $options->root
                ?>
            </span>
        </p>
    </div>

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-advanced-settings">
        <span class="wpstg-tab-triangle">&#9658;</span>
        <?php echo __("Advanced Options", "wpstg")?>
    </a>

    <div class="wpstg-tab-section" id="wpstg-advanced-settings">
        Coming Soon...
    </div>

</div>

<button type="button" class="wpstg-prev-step-link wpstg-link-btn button-primary">
    <?php _e("Back", "wpstg")?>
</button>

<button type="button" id="wpstg-start-cloning" class="wpstg-next-step-link wpstg-link-btn button-primary" data-action="wpstg_cloning">
    <?php
    if (null !== $options->current)
    {
        echo __("Update Clone", "wpstg");
    }
    else
    {
        echo __("Start Cloning", "wpstg");
    }
    ?>
</button>