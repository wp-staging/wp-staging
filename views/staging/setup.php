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

include WPSTG_VIEWS_DIR . 'job/modal/success.php';
include WPSTG_VIEWS_DIR . 'job/modal/process.php';
?>
<label id="wpstg-clone-label" for="wpstg-new-clone-id">
    <input type="text" id="wpstg-new-clone-id" class="wpstg-input wpstg-input-lg"
        placeholder="<?php esc_html_e('Enter Site Name (Optional)', 'wp-staging') ?>"
        data-clone="<?php echo esc_attr($stagingSiteDto->getCloneId()); ?>"
        <?php if (!$stagingSetup->isNewStagingSite()) {
            echo ' value="' . esc_attr($stagingSiteDto->getCloneName()) . '"';
            echo " disabled='disabled'";
        } ?> />
</label>

<div id="wpstg-clone-id-error" class="wpstg-callout wpstg-callout-warning wpstg-mt-2" style="display:none;">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
    <div class="wpstg-text-sm" id="wpstg-clone-id-error-msg"></div>
</div>
<?php require_once WPSTG_VIEWS_DIR . 'staging/_partials/file-size-notice.php';?>
<section class="wpstg-card wpstg-card-body wpstg-mt-4">
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
            $stagingSetup->renderAdvanceSettings('wpstg_enable_cron', esc_html__('Enable Cron Jobs', 'wp-staging'), esc_html__('Will enable WordPress cron on the staging site.', 'wp-staging'), true);
        }

        $stagingSetup->renderAdvanceSettings('wpstg_allow_emails', esc_html__('Allow Emails Sending', 'wp-staging'), esc_html__('Allow emails sending for this staging site.', 'wp-staging') . '<br /> <br /> <b>' . esc_html__('Note', 'wp-staging') . ': </b>' . sprintf(esc_html__('Even if email sending is disabled, some plugins might still be able to send out mails if they don\'t depend upon %s.', 'wp-staging'), '<code>wp_mail()</code>'), true);
        $stagingSetup->renderAdvanceSettings('wpstg_reminder_emails', esc_html__('Get Reminder Email', 'wp-staging'), esc_html__('You will receive an email reminder every two weeks about your active staging site. This helps you manage and delete unused staging sites, ensuring safety and preventing multiple unnecessary test environments.', 'wp-staging'));
        $stagingSetup->renderAdvanceSettings('wpstg_auto_update_plugins', esc_html__('Auto Update Plugins', 'wp-staging'), esc_html__('Automatically updates all plugins on the staging site to the latest versions immediately after you have created the staging site. You can then test the staging site to find out if all plugins work as expected and if your site does not trigger any errors.', 'wp-staging') . '<br /> <br /> <b>' . esc_html__('You will receive an e-mail and slack notification as soon as the staging site has been updated. (If activated in WP Staging settings)', 'wp-staging') . '</b>');
        $stagingSetup->renderEnableWooSchedulerSettings();

        if ($stagingSetup->isUpdateJob()) {
            $stagingSetup->renderAdvanceSettings('wpstg-clean-plugins-themes', esc_html__('Clean Plugins/Themes', 'wp-staging'), esc_html__('Delete all plugins & themes on staging site before starting update process.', 'wp-staging'));
            $stagingSetup->renderAdvanceSettings('wpstg-clean-uploads', esc_html__('Clean Uploads', 'wp-staging'), esc_html__('Delete entire folder wp-content/uploads on staging site including all images before starting update process.', 'wp-staging') . ($stagingSiteDto->getUploadsSymlinked() ? "<br/><br/><b>" . esc_html__("Note: This option is disabled as uploads directory is symlinked", "wp-staging") . "</b>" : ''));
        }
        ?>
    </div>
</div>
</section>

<div class="wpstg-mb-6"></div>

<div class="wpstg-flex wpstg-items-center wpstg-gap-3 wpstg-flex-wrap">
    <button type="button" class="wpstg-prev-step-link wpstg-btn wpstg-btn-md wpstg-btn-secondary">
        <svg class="wpstg-btn-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        <?php esc_html_e("Back", "wp-staging") ?>
    </button>

    <?php
    $label    = esc_html__("Start Cloning", "wp-staging");
    $btnClass = 'wpstg--create--staging-site';
    if ($stagingSetup->isUpdateJob()) {
        $label    = esc_html__("Update Staging Site", "wp-staging");
        $btnClass = 'wpstg--update--staging-site';
    }
    ?>

    <button type="button" class="wpstg-btn wpstg-btn-md wpstg-btn-primary <?php echo esc_attr($btnClass); ?>" data-url="<?php echo esc_attr($stagingSiteDto->getUrl()); ?>"><?php echo esc_html($label); ?></button>

    <a href="#" id="wpstg-check-space" class="wpstg-btn wpstg-btn-ghost"><?php esc_html_e('Check required disk space', 'wp-staging'); ?></a>
    <div id="wpstg-disk-space-result" class="wpstg-callout wpstg-callout-warning wpstg-mt-2" style="display:none;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>
        </svg>
        <div class="wpstg-text-sm" id="wpstg-disk-space-result-msg"></div>
    </div>
</div>
