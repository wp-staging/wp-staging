<?php

use WPStaging\Framework\Facades\Escape;
use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Service\DirectoryScanner;
use WPStaging\Staging\Service\AbstractStagingSetup;
use WPStaging\Staging\Service\TableScanner;

/**
 * @var AbstractStagingSetup $stagingSetup
 * @var StagingSiteDto       $stagingSiteDto
 * @var DirectoryScanner     $directoryScanner
 * @var TableScanner         $tableScanner
 */
?>
<label id="wpstg-clone-label" for="wpstg-new-clone-id">
    <input type="text" id="wpstg-new-clone-id" class="wpstg-textbox"
        placeholder="<?php esc_html_e('Enter Site Name (Optional)', 'wp-staging') ?>"
        data-clone="<?php echo esc_attr($stagingSiteDto->getCloneId()); ?>"
        <?php if (!$stagingSetup->isNewStagingSite()) {
            echo ' value="' . esc_attr($stagingSiteDto->getCloneName()) . '"';
            echo " disabled='disabled'";
        } ?> />
</label>

<span class="wpstg-error-msg" id="wpstg-clone-id-error" style="display:none;">
    <?php
    echo Escape::escapeHtml(__(
        "<br>Probably not enough free disk space to create a staging site. " .
            "<br> You can continue but its likely that the copying process will fail.",
        "wp-staging"
    ))
    ?>
</span>

<div class="wpstg-tabs-wrapper">
    <p class="wpstg-tables-selection-note">
        <b class="wpstg--red"><?php esc_html_e("Note: ", "wp-staging") ?></b>
        <?php esc_html_e("The tables and folder selection will be saved and preselected for the next update or reset on this staging site.", "wp-staging") ?>
    </p>
    <?php $stagingSetup->renderNetworkCloneSettings(); ?>
    <a href="#" class="wpstg-tab-header active" data-id="#wpstg-setup-tables">
        <span class="wpstg-tab-triangle"></span>
        <?php esc_html_e("Database Tables", "wp-staging") ?>
        <span id="wpstg-tables-count" class="wpstg-selection-preview"></span>
    </a>

    <fieldset class="wpstg-tab-section" id="wpstg-setup-tables">
        <?php $tableScanner->renderTablesSelection() ?>
    </fieldset>

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-setup-files">
        <span class="wpstg-tab-triangle"></span>
        <?php esc_html_e("Files", "wp-staging") ?>
        <span id="wpstg-files-count" class="wpstg-selection-preview"></span>
    </a>

    <fieldset class="wpstg-tab-section" id="wpstg-setup-files">
        <?php $directoryScanner->renderFilesSelection() ?>
    </fieldset>

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-advanced-settings">
        <span class="wpstg-tab-triangle"></span>
        <?php echo esc_html($stagingSetup->getAdvanceSettingsTitle()); ?>
    </a>

    <div class="wpstg-tab-section" id="wpstg-advanced-settings">
        <?php
        $stagingSetup->renderAdvanceSettingsHeader();
        if (!$stagingSetup->isUpdateJob()) {
            require_once(WPSTG_VIEWS_DIR . 'staging/setup/login-data.php');
            require_once(WPSTG_VIEWS_DIR . 'staging/setup/external-database.php');
            require_once(WPSTG_VIEWS_DIR . 'staging/setup/custom-directory.php');
            $stagingSetup->renderAdvanceSettings('wpstg_symlink_upload', esc_html__('Symlink Uploads Folder', 'wp-staging'), $stagingSetup->getSymlinkUploadDescription());
        }

        if ($stagingSetup->isNewStagingSite()) {
            $stagingSetup->renderAdvanceSettings('wpstg_disable_cron', esc_html__('Disable Cron Jobs', 'wp-staging'), esc_html__('Will disable WordPress cron on the staging site.', 'wp-staging'));
        }

        $stagingSetup->renderAdvanceSettings('wpstg_allow_emails', esc_html__('Allow Emails Sending', 'wp-staging'), esc_html__('Allow emails sending for this staging site.', 'wp-staging') . '<br /> <br /> <b>' . esc_html__('Note', 'wp-staging') . ': </b>' . sprintf(esc_html__('Even if email sending is disabled, some plugins might still be able to send out mails if they don\'t depend upon %s.', 'wp-staging'), '<code>wp_mail()</code>'), true);
        $stagingSetup->renderAdvanceSettings('wpstg_reminder_emails', esc_html__('Get Reminder Email', 'wp-staging'), esc_html__('You will receive an email reminder every two weeks about your active staging site. This helps you manage and delete unused staging sites, ensuring safety and preventing multiple unnecessary test environments.', 'wp-staging'));
        $stagingSetup->renderDisableWooSchedulerSettings();
        ?>
    </div>
</div>

<?php if ($stagingSetup->isUpdateJob()) : ?>
<fieldset class="wpstg-fieldset" style="margin-left: 16px;">
    <?php
        $stagingSetup->renderSettings('wpstg-clean-plugins-themes', esc_html__('Clean Plugins/Themes', 'wp-staging'), esc_html__('Delete all plugins & themes on staging site before starting update process.', 'wp-staging'));
        $stagingSetup->renderSettings('wpstg-clean-uploads', esc_html__('Clean Uploads', 'wp-staging'), esc_html__('Delete entire folder wp-content/uploads on staging site including all images before starting update process.', 'wp-staging') . ($stagingSiteDto->getUploadsSymlinked() ? "<br/><br/><b>" . esc_html__("Note: This option is disabled as uploads directory is symlinked", "wp-staging") . "</b>" : ''));
    ?>
</fieldset>
<hr/>
<?php endif; ?>

<button type="button" class="wpstg-prev-step-link wpstg-button--primary wpstg-button-back-arrow">
    <i class="wpstg-back-arrow"></i>
    <?php esc_html_e("Back", "wp-staging") ?>
</button>

<?php
$label  = esc_html__("Start Cloning", "wp-staging");
$action = 'wpstg_cloning';
$btnId  = 'wpstg-start-cloning';
if ($stagingSetup->isUpdateJob()) {
    $label  = esc_html__("Update Staging Site", "wp-staging");
    $action = 'wpstg_update';
    $btnId  = 'wpstg-start-updating';
}
?>

<button type="button" id="<?php echo esc_attr($btnId); ?>" class="wpstg-next-step-link wpstg-button--primary wpstg-button--blue" data-action="<?php echo esc_attr($action); ?>" data-url="<?php echo esc_attr($stagingSiteDto->getUrl()); ?>"><?php echo esc_html($label); ?></button>

<a href="#" id="wpstg-check-space"><?php esc_html_e('Check required disk space', 'wp-staging'); ?></a>
