<?php

use WPStaging\Staging\Service\AbstractStagingSetup;

/**
 * This file is currently used in both FREE and PRO.
 * @var AbstractStagingSetup $stagingSetup
 */

ob_start();
?>
<div id="wpstg-external-db-section" class="wpstg-advanced-settings-expanded-section" <?php echo $stagingSetup->getIsOpenDisabledSettingsSectionByDefault() ? '' : 'style="display: none;"' ?>>
    <div class="wpstg-advanced-settings-expanded-fields wpstg-py-1">
        <?php $stagingSetup->renderExternalDatabaseSettings() ?>
        <div class="wpstg-form-group wpstg-text-field wpstg-advanced-settings-link-row wpstg-mt-1">
            <a href="#" id="wpstg-db-connect"><?php esc_html_e("Test Database Connection", "wp-staging"); ?></a>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();

$stagingSetup->renderAdvanceSettings(
    'wpstg-ext-db',
    esc_html__('Change Database', 'wp-staging'),
    wp_kses_post(__('You can clone the staging site into a separate database. The Database must be created manually in advance before starting the cloning proccess.<br/><br/><strong>Note:</strong> If there are already tables with the same database prefix and name in this database, the cloning process will be aborted without any further asking!', 'wp-staging')),
    false,
    'wpstg-toggle-advance-settings-section',
    'wpstg-external-db-section',
    esc_html__('Store the staging site in a different database.', 'wp-staging'),
    $content
);
