<?php

/**
 * @see \WPStaging\Backend\Administrator::ajaxDeleteConfirmation()
 * @var object $delete
 * @var string $dbname
 * @var object $clone
 * @var bool $isDatabaseConnected
 *
 */
require_once(WPSTG_PLUGIN_DIR . 'Backend/views/backup/modal/progress.php');

if ($isDatabaseConnected) { ?>
    <div class="wpstg-display-flex">
        <div class="wpstg-confirm-container">
            <div>
                <div class="wpstg-confirm-inner-content">
                    <strong><?php esc_html_e('Database Name:', 'wp-staging'); ?>
                        <span><?php
                            echo empty($clone->databaseDatabase) ? esc_html($dbname) : esc_html($clone->databaseDatabase);
                            echo empty($clone->databaseDatabase) ? " (Production Database)" : " (Separate Database)"; ?></span>
                    </strong>
                </div>
                <div class="wpstg-confirm-inner-content wpstg-pointer" id="wpstg-show-database-tables">
                    <strong class="wpstg-display-content-left"><?php echo esc_html__("Selected database tables will be deleted:", "wp-staging") ?></strong>
                </div>
                <div>
                    <div class="wpstg-show-tables-inner-wrapper wpstg-confirm-inner-content-text wpstg-margin-l7">
                        <label>
                            <input id="wpstg-unselect-all-tables" type="checkbox" class="wpstg-checkbox wpstg-button-unselect wpstg-unselect-all-tables" value="1" checked>
                            <span id="wpstg-unselect-all-tables-id"><?php echo esc_html__("Unselect All", "wp-staging"); ?></span>
                        </label>
                        <div class="wpstg-confirm-inner-content-text">
                            <?php foreach ($delete->getTables() as $table) : ?>
                                <div class="wpstg-db-table">
                                    <label>
                                        <?php $checkedProperty = (strpos($table->name, $clone->prefix) === 0) ? 'checked' : ''; ?>
                                        <input class="wpstg-checkbox wpstg-db-table-checkboxes" type="checkbox" name="<?php echo esc_attr($table->name); ?>" <?php echo esc_attr($checkedProperty) ?>>
                                        <?php echo esc_html($table->name) ?>
                                    </label>
                                    <span class="wpstg-size-info wpstg-ml-8px">
                                <?php echo isset($table->size) ? esc_html($table->size) : ''; ?>
                        </span>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="wpstg-confirm-container wpstg-confirm-container-overflow-hidden">
            <div>
                <div id="wpstg-show-files-container">
                    <div id="wpstg-show-files-inner-container">
                        <div class="wpstg-display-content-left wpstg-confirm-inner-content-text wpstg-margin-l7">
                            <strong>
                                <?php echo wp_kses_post(__("Selected folder will be deleted:", "wp-staging")) ?>
                            </strong>
                            <div class="wpstg-confirm-inner-content-text wpstg-mt-10px">
                                <label>
                                    <input id="deleteDirectory" type="checkbox" class="wpstg-checkbox" name="deleteDirectory" value="1" checked data-deletepath="<?php echo urlencode($clone->path); ?>">
                                    <?php echo esc_html($clone->path); ?>
                                    <span class="wpstg-size-info"><?php echo isset($clone->size) ? esc_html($clone->size) : ''; ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<?php if (!$isDatabaseConnected) { ?>
    <div id="wpstg-confirm-error-container" class="wpstg-failed">
        <h4 class="wpstg-mb-0"><?php esc_html_e('Error: Can not connect to database! ', 'wp-staging');
            echo esc_html($clone->databaseDatabase); ?></h4>
        <ul class="wpstg-mb-0">
            <li><?php esc_html_e('This can happen if the password of the database changed or if the staging site database or tables were deleted', 'wp-staging') ?></li>
            <li><?php esc_html_e('You can still delete this staging site but deleting will not delete any database table. You will have to delete them manually if they exist.', 'wp-staging') ?></li>
        </ul>
    </div>
<?php } ?>
