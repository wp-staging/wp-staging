<?php

use WPStaging\Staging\Service\AbstractStagingSetup;

/**
 * This file is currently used in both FREE and PRO.
 * @var AbstractStagingSetup $stagingSetup
 */

$stagingSetup->renderAdvanceSettings(
    'wpstg-new-admin-user',
    esc_html__('New Admin Account', 'wp-staging'),
    esc_html__('Create a new admin user account for this staging site!', 'wp-staging') . '<br/><br/><span class="wpstg--red wpstg-mt-10px">' . esc_html__('If the account already exists, the password will be updated.', 'wp-staging') . '</span>',
    false,
    'wpstg-toggle-advance-settings-section',
    'wpstg-new-admin-user-section'
);

?>

<div id="wpstg-new-admin-user-section" <?php echo $stagingSetup->getIsOpenDisabledSettingsSectionByDefault() ? '' : 'style="display: none;"' ?>>
    <?php $stagingSetup->renderNewAdminSettings() ?>
    <hr />
</div>
