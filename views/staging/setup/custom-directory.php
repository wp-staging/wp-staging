<?php

use WPStaging\Staging\Service\AbstractStagingSetup;

/**
 * This file is currently used in only FREE version.
 * @var AbstractStagingSetup $stagingSetup
 */

ob_start();
?>
<div id="wpstg-clone-directory" class="wpstg-advanced-settings-expanded-section" <?php echo $stagingSetup->getIsOpenDisabledSettingsSectionByDefault() ? '' : 'style="display: none;"' ?>>
    <div class="wpstg-advanced-settings-expanded-fields wpstg-py-1">
        <?php $stagingSetup->renderCustomDirectorySettings() ?>
    </div>
</div>
<?php
$content = ob_get_clean();

$stagingSetup->renderAdvanceSettings(
    'wpstg-change-dest',
    esc_html__('Change Destination', 'wp-staging'),
    $stagingSetup->getCustomDirectoryDescription(),
    false,
    'wpstg-toggle-advance-settings-section',
    'wpstg-clone-directory',
    esc_html__('Override the staging site path or hostname.', 'wp-staging'),
    $content
);
