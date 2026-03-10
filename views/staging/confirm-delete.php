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
    <div class="md:wpstg-flex-row wpstg-flex wpstg-flex-col wpstg-gap-6">
        <div class="wpstg-card wpstg-card-body md:wpstg-w-full wpstg-w-[-webkit-fill-available]">
            <div class="wpstg-mb-[5px] wpstg-text-left wpstg-font-bold">
                <?php esc_html_e('Database Name:', 'wp-staging');
                echo esc_html($stagingSite->getDatabaseName());
                echo $stagingSite->getIsExternalDatabase() ? " (Separate Database)" : " (Production Database)";
                ?>
            </div>
            <div class="wpstg-flex wpstg-mt-2.5">
                <span class="wpstg-text-left"><?php echo esc_html__("Selected database tables will be deleted:", "wp-staging") ?></span>
            </div>
            <div class="wpstg-text-left wpstg-mt-2.5">
                <label>
                    <?php Checkbox::render('wpstg-unselect-all-tables', 'wpstg-unselect-all-tables', '1', true, ['classes' => 'wpstg-button-unselect wpstg-unselect-all-tables']); ?>
                    <span id="wpstg-unselect-all-tables-id"><?php echo esc_html__("Unselect All", "wp-staging"); ?></span>
                </label>
                <div class="wpstg-text-left">
                    <?php foreach ($tables as $table) : ?>
                        <div class="wpstg-db-table">
                            <label class="wpstg-cursor-pointer">
                                <?php Checkbox::render('', $table->getName(), '', (strpos($table->getName(), $stagingSite->getUsedPrefix()) === 0), ['classes' => 'wpstg-db-table-checkboxes']); ?>
                                <?php echo esc_html($table->getName()) ?>
                            </label>
                            <span class="wpstg-size-info wpstg-ml-8px"><?php echo esc_html($table->getHumanReadableSize()); ?></span>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
        <div class="wpstg-card wpstg-card-body md:wpstg-w-full wpstg-w-[-webkit-fill-available]">
            <div class="wpstg-mr-0">
                <div class="wpstg-mb-[5px] wpstg-text-left wpstg-font-bold">
                    <?php esc_html_e('Files', 'wp-staging') ?>
                </div>
                <div class="wpstg-flex wpstg-text-left wpstg-mt-2.5">
                    <span><?php echo wp_kses_post(__("Selected folder and the contained files are deleted:", "wp-staging")) ?></span>
                </div>
                <div class="wpstg-text-left wpstg-cursor-pointer wpstg-mt-2.5">
                    <label class="wpstg-cursor-pointer">
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
<div id="wpstg-delete-confirm-error-container" class="wpstg-callout wpstg-callout-danger wpstg-block wpstg-mb-5">
    <h4 class="wpstg-m-0"><?php esc_html_e('Error: Can not connect to database! ', 'wp-staging');
        echo esc_html($stagingSite->getDatabaseDatabase()); ?>
    </h4>
    <ul class="wpstg-u-mb-0">
        <li><?php esc_html_e('This can happen if the password of the database changed or if the staging site database or tables were deleted', 'wp-staging') ?></li>
        <li><?php esc_html_e('You can still delete this staging site but deleting will not delete any database table. You will have to delete them manually if they exist.', 'wp-staging') ?></li>
    </ul>
</div>
<div class="wpstg-card wpstg-card-body wpstg-w-[-webkit-fill-available] wpstg-text-left">
    <div class="wpstg-mb-[5px] wpstg-font-bold">
        <?php esc_html_e('Files', 'wp-staging') ?>
    </div>
    <div class="wpstg-flex">
        <span><?php echo wp_kses_post(__("Selected folder and the contained files are deleted:", "wp-staging")) ?></span>
    </div>
    <div class="wpstg-cursor-pointer wpstg-mt-2.5">
        <label>
            <?php Checkbox::render('deleteDirectory', 'deleteDirectory', '1', true, [], ['deletePath' => urlencode($stagingSite->getPath())]); ?>
            <?php echo esc_html($stagingSite->getPath()); ?>
            <span class="wpstg-size-info"><?php echo empty($stagingSiteSize) ? '' : esc_html($stagingSiteSize); ?></span>
        </label>
    </div>
</div>
<?php } ?>
