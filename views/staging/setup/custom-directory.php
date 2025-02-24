<?php

use WPStaging\Staging\Service\AbstractStagingSetup;

/**
 * This file is currently used in only FREE version.
 * @var AbstractStagingSetup $stagingSetup
 */

$stagingSetup->renderAdvanceSettings(
    'wpstg-change-dest',
    esc_html__('Change Destination', 'wp-staging'),
    $stagingSetup->getCustomDirectoryDescription(),
    false,
    'wpstg-toggle-advance-settings-section',
    'wpstg-clone-directory'
);
?>

<div id="wpstg-clone-directory" <?php echo $stagingSetup->getIsOpenDisabledSettingsSectionByDefault() ? '' : 'style="display: none;"' ?>>
    <?php $stagingSetup->renderCustomDirectorySettings() ?>
    <hr/>
</div>
