<?php if ($isDatabaseConnected) { ?>
<div class="wpstg-notice-alert">
    <h3 class="wpstg-m-0 wpstg-pb-5px">
        <?php
        _e("This staging site will be deleted:", "wp-staging")
        ?>
    </h3>

    <p>
        <?php _e('Clone Name:', 'wp-staging'); ?> 
        <span class="wpstg-confirmation-label">
        <?php 
        echo $clone->directoryName; 
        ?>
        </span>
    </p>
    <p>
        <?php _e('Database Name:', 'wp-staging'); ?> 
        <span class="wpstg-confirmation-label">
        <?php 
        $database = empty($clone->databaseDatabase) ? "{$dbname} / Main Database)" : $clone->databaseDatabase;
        echo $database; 
        ?>
        </span>
    </p>
</div>
<?php } ?>

<?php if (!$isDatabaseConnected) { ?>
<div class="wpstg-notice-alert wpstg-failed">
    <h4 class="wpstg-mb-0"><?php _e('Error: Can not connect to external database: ', 'wp-staging'); echo $clone->databaseDatabase; ?></h4>
    <ul class="wpstg-mb-0">
        <li><?php _e('This can happen if the password of the external database has been changed or if the database was deleted', 'wp-staging') ?></li>
        <li><?php _e('You can still delete this staging site but deleting this site will not delete any table or database. You will have to delete them manually if they exist.', 'wp-staging') ?></li>
    </ul>
</div>
<?php } ?>

<div class="wpstg-tabs-wrapper">

    <?php if ($isDatabaseConnected) { ?>
    <a href="#" class="wpstg-tab-header active" data-id="#wpstg-scanning-db">
        <span class="wpstg-tab-triangle">&#9658;</span>
        <?php echo __("Database tables to remove", "wp-staging")?>
    </a>

    <!-- Database -->
    <div class="wpstg-tab-section" id="wpstg-scanning-db">
        <h4 class="wpstg-m-0">
            <?php _e("Unselect all database tables you do not want to delete:", "wp-staging")?>
        </h4>
        <div class="wpstg-my-6px">
            <a href="#" class="wpstg-button-unselect">
            <?php _e("Unselect All", "wp-staging") ?>
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
        <div class="wpstg-my-6px">
            <a href="#" class="wpstg-button-unselect">
            <?php _e("Unselect All", "wp-staging") ?>
            </a>
        </div>
    </div>
    <?php } ?>
    <!-- /Database -->

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-scanning-files">
        <span class="wpstg-tab-triangle">&#9658;</span>
        <?php echo __("Files to remove", "wp-staging")?>
    </a>

    <!-- Files -->
    <div class="wpstg-tab-section" id="wpstg-scanning-files">
        <h4 class="wpstg-m-0 wpstg-mb-10px">
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