<div class="wpstg-notice-alert wpstg-failed">
    <h4 style="margin:0">
        <?php
        _e("Note: The selected tables below will be deleted. <br/>Verify that before you delete them.", "wp-staging")
        ?>
    </h4>

    <p>
        <?php _e('Clone Name:', 'wp-staging'); ?> 
        <span style="background-color:#5b9dd9;color:#fff;padding: 2px;border-radius: 3px;">
        <?php 
        echo $clone->directoryName; 
        ?>
        </span>
    </p>
    <p>
        <?php _e('Database Name:', 'wp-staging'); ?> 
        <span style="background-color:#5b9dd9;color:#fff;padding: 2px;border-radius: 3px;">
        <?php 
        $database = empty($clone->databaseDatabase) ? "{$dbname} / Main Database)" : $clone->databaseDatabase;
        echo $database; 
        ?>
        </span>
    </p>

    <p>
        <?php
        _e(
            'Usually the preselected data can be deleted without any risk '.
            'but in case something is going wrong you better verify it.',
            'wp-staging'
        );
        ?>
    </p>
</div>

<div class="wpstg-tabs-wrapper">

    <a href="#" class="wpstg-tab-header active" data-id="#wpstg-scanning-db">
        <span class="wpstg-tab-triangle">&#9658;</span>
        <?php echo __("Database tables to remove", "wp-staging")?>
    </a>

    <!-- Database -->
    <div class="wpstg-tab-section" id="wpstg-scanning-db">
        <h4 style="margin:0;">
            <?php _e("Unselect all database tables you do not want to delete:", "wp-staging")?>
        </h4>
        <div style="margin-bottom:6px;margin-top:6px;">
            <a href="#" class="wpstg-button-unselect">
                Unselect All
            </a>
        </div>

        <?php foreach ($delete->getTables() as $table):?>
            <div class="wpstg-db-table">
                <label>
                    <input class="wpstg-db-table-checkboxes" type="checkbox" name="<?php echo $table->name?>" checked>
                    <?php echo $table->name?>
                </label>
                <span class="wpstg-size-info">
				<?php echo isset($table->size) ? $table->size : '';?>
			</span>
            </div>
        <?php endforeach ?>
        <div style="margin-bottom:6px;margin-top:6px;">
            <a href="#" class="wpstg-button-unselect">
                Unselect All
            </a>
        </div>
    </div>
    <!-- /Database -->

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-scanning-files">
        <span class="wpstg-tab-triangle">&#9658;</span>
        <?php echo __("Files to remove", "wp-staging")?>
    </a>

    <!-- Files -->
    <div class="wpstg-tab-section" id="wpstg-scanning-files">
        <h4 style="margin:0;margin-bottom:10px;">
            <?php _e("Selected folder and all of its subfolders and files will be deleted. <br/>Unselect it if you want to keep the staging site file data.", "wp-staging") ?>
        </h4>

        <div class="wpstg-dir">
            <label>
                <input id="deleteDirectory" type="checkbox" class="wpstg-check-dir" name="deleteDirectory" value="1" checked data-deletepath="<?php echo urlencode($clone->path);?>">
                <?php echo $clone->path;?>
                <span class="wpstg-size-info"><?php echo isset($clone->size) ? $clone->size : ''; ?></span>
            </label>
        </div>
    </div>
    <!-- /Files -->
</div>

<a href="#" class="wpstg-link-btn button-primary" id="wpstg-cancel-removing">
    <?php _e("Cancel", "wp-staging")?>
</a>

<a href="#" class="wpstg-link-btn button-primary" id="wpstg-remove-clone" data-clone="<?php echo $clone->name?>">
    <?php echo __("Remove", "wp-staging")?>
</a>