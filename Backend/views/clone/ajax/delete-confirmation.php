<?php

/**
 * @see \WPStaging\Backend\Administrator::ajaxDeleteConfirmation()
 * @var object $delete
 * @var string $dbname
 * @var object $clone
 * @var bool $isDatabaseConnected
 *
 */

if ($isDatabaseConnected) { ?>
<div class="wpstg-notice-alert">
    <h3 class="wpstg-m-0 wpstg-pb-5px">
        <?php
        esc_html_e("Do you want to delete the staging site?", "wp-staging")
        ?>
    </h3>

    <p>
        <?php esc_html_e('Staging Site Name:', 'wp-staging'); ?>
        <code>
        <?php
        echo esc_html($clone->directoryName);
        ?>
        </code>
    </p>
    <p>
        <?php esc_html_e('Database Location:', 'wp-staging'); ?>
        <code>
        <?php echo empty($clone->databaseDatabase) ? esc_html($dbname) : esc_html($clone->databaseDatabase); ?>
        </code>
        <?php echo empty($clone->databaseDatabase) ? "(Production Database)" : "(Separate Database)"; ?>

    </p>
</div>
<?php } ?>

<?php if (!$isDatabaseConnected) { ?>
<div class="wpstg-notice-alert wpstg-failed">
    <h4 class="wpstg-mb-0"><?php esc_html_e('Error: Can not connect to external database: ', 'wp-staging');
    echo esc_html($clone->databaseDatabase); ?></h4>
    <ul class="wpstg-mb-0">
        <li><?php esc_html_e('This can happen if the password of the external database has been changed or if the database was deleted', 'wp-staging') ?></li>
        <li><?php esc_html_e('You can still delete this staging site but deleting this site will not delete any table or database. You will have to delete them manually if they exist.', 'wp-staging') ?></li>
    </ul>
</div>
<?php } ?>

<div class="wpstg-tabs-wrapper">

    <?php if ($isDatabaseConnected) { ?>
    <a href="#" class="wpstg-tab-header active" data-id="#wpstg-scanning-db">
        <span class="wpstg-tab-triangle"></span>
        <?php echo esc_html__("Database tables to delete", "wp-staging")?>
    </a>

    <!-- Database -->
    <div class="wpstg-tab-section" id="wpstg-scanning-db">
        <h4 class="wpstg-m-0">
            <?php esc_html_e("Select all database tables you want to delete:", "wp-staging")?>
        </h4>
        <div class="wpstg-my-6px">
            <a href="#" class="wpstg-button-unselect">
            <?php esc_html_e("Unselect All", "wp-staging") ?>
            </a>
        </div>

        <?php foreach ($delete->getTables() as $table) :?>
            <div class="wpstg-db-table">
                <label>
                    <?php $checkedProperty = (strpos($table->name, $clone->prefix) === 0) ? 'checked' : ''; ?>
                    <input class="wpstg-db-table-checkboxes" type="checkbox" name="<?php echo esc_attr($table->name); ?>" <?php echo esc_attr($checkedProperty) ?>>
                    <?php echo esc_html($table->name) ?>
                </label>
                <span class="wpstg-size-info">
                <?php echo isset($table->size) ? esc_html($table->size) : '';?>
            </span>
            </div>
        <?php endforeach ?>
        <div class="wpstg-my-6px">
            <a href="#" class="wpstg-button-unselect">
            <?php esc_html_e("Unselect All", "wp-staging") ?>
            </a>
        </div>
    </div>
    <?php } ?>
    <!-- /Database -->

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-scanning-files">
        <span class="wpstg-tab-triangle"></span>
        <?php echo esc_html__("Files to delete", "wp-staging")?>
    </a>

    <!-- Files -->
    <div class="wpstg-tab-section" id="wpstg-scanning-files">
        <h4 class="wpstg-m-0 wpstg-mb-10px">
            <?php echo wp_kses_post(__("Selected folder and all of its subfolders and files will be deleted. <br/>Unselect it if you want to keep the staging site file data.", "wp-staging")) ?>
        </h4>

        <div class="wpstg-dir">
            <label>
                <input id="deleteDirectory" type="checkbox" class="wpstg-check-dir" name="deleteDirectory" value="1" checked data-deletepath="<?php echo urlencode($clone->path);?>">
                <?php echo esc_html($clone->path);?>
                <span class="wpstg-size-info"><?php echo isset($clone->size) ? esc_html($clone->size) : ''; ?></span>
            </label>
        </div>
    </div>
    <!-- /Files -->
</div>

<a href="#" class="wpstg-button--primary" id="wpstg-cancel-removing">
    <?php esc_html_e("Cancel", "wp-staging")?>
</a>

<a href="#" class="wpstg-button--primary wpstg-button--red" style="margin-left:5px;" id="wpstg-remove-clone" data-clone="<?php echo esc_attr($clone->name); ?>">
    <?php echo esc_html__("Delete", "wp-staging")?>
</a>