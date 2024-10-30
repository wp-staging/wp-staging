<?php

/**
 * @see \WPStaging\Staging\Ajax\Delete\ConfirmDelete::ajaxConfirma()
 * @var WPStaging\Staging\Dto\StagingSiteDto     $stagingSite
 * @var WPStaging\Framework\Database\TableDto[]  $tables
 * @var bool                                     $isDatabaseConnected
 * @var string                                   $stagingSiteSize
 */

use WPStaging\Framework\Facades\UI\Checkbox;

require_once(WPSTG_VIEWS_DIR . 'job/modal/process.php');

if ($isDatabaseConnected) { ?>
    <div class="wpstg-delete-confirm-modal">
        <div class="wpstg-delete-confirm-container">
            <div class="wpstg-delete-confirm-header">
                    <?php esc_html_e('Database Name:', 'wp-staging');
                    echo esc_html($stagingSite->getDatabaseName());
                    echo $stagingSite->getIsExternalDatabase() ? " (Separate Database)" : " (Production Database)";
                    ?>
            </div>
            <div class="wpstg-delete-confirm-inner-content wpstg-pointer wpstg-mt-10px">
                <span class="wpstg-content-left-column"><?php echo esc_html__("Selected database tables will be deleted:", "wp-staging") ?></span>
            </div>
                <div class="wpstg-show-tables-inner-wrapper wpstg-delete-confirm-inner-content-checkboxes wpstg-mt-10px">
                    <label>
                        <?php Checkbox::render('wpstg-unselect-all-tables', 'wpstg-unselect-all-tables', '1', true, ['classes' => 'wpstg-button-unselect wpstg-unselect-all-tables']); ?>
                        <span id="wpstg-unselect-all-tables-id"><?php echo esc_html__("Unselect All", "wp-staging"); ?></span>
                    </label>
                    <div class="wpstg-delete-confirm-inner-content-checkboxes">
                        <?php foreach ($tables as $table) : ?>
                            <div class="wpstg-db-table">
                                <label>
                                    <?php Checkbox::render('', $table->getName(), '', (strpos($table->getName(), $stagingSite->getUsedPrefix()) === 0), ['classes' => 'wpstg-db-table-checkboxes']); ?>
                                    <?php echo esc_html($table->getName()) ?>
                                </label>
                                <span class="wpstg-size-info wpstg-ml-8px"><?php echo esc_html($table->getHumanReadableSize()); ?></span>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
        </div>
        <div class="wpstg-delete-confirm-container wpstg-display-content-right-side">
            <div class="wpstg-display-content-right-side">
                <div class="wpstg-delete-confirm-header">
                    <?php esc_html_e('Files', 'wp-staging') ?>
                </div>
                <div class="wpstg-delete-confirm-inner-content wpstg-pointer wpstg-mt-10px">
                    <span><?php echo wp_kses_post(__("Selected folder and the contained files are deleted:", "wp-staging")) ?></span>
                </div>
                <div class="wpstg-delete-confirm-inner-content-checkboxes wpstg-pointer wpstg-mt-10px">
                    <label>
                        <?php Checkbox::render('deleteDirectory', 'deleteDirectory', '1', true, [], ['deletePath' => urlencode($stagingSite->getPath())]); ?>
                        <?php echo esc_html($stagingSite->getPath()); ?>
                        <span class="wpstg-size-info"><?php echo empty($stagingSiteSize) ? '' : esc_html($stagingSiteSize); ?></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
<?php }

if (!$isDatabaseConnected) { ?>
    <div id="wpstg-delete-confirm-error-container" class="wpstg-failed">
        <h4 class="wpstg-mb-0"><?php esc_html_e('Error: Can not connect to database! ', 'wp-staging');
            echo esc_html($stagingSite->getDatabaseDatabase()); ?></h4>
        <ul class="wpstg-mb-0">
            <li><?php esc_html_e('This can happen if the password of the database changed or if the staging site database or tables were deleted', 'wp-staging') ?></li>
            <li><?php esc_html_e('You can still delete this staging site but deleting will not delete any database table. You will have to delete them manually if they exist.', 'wp-staging') ?></li>
        </ul>
    </div>
<?php } ?>
