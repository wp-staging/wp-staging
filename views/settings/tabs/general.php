<?php

use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Settings\Settings;
use WPStaging\Framework\Facades\UI\Checkbox;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Notifications\Notifications;

?>

<!-- General Settings -->
<div id="wpstg-tab-container" class="tab_container">
    <form class="wpstg-settings-panel" method="post" action="options.php">
        <?php
        settings_fields("wpstg_settings");

        foreach ($tabs as $id => $name) :
            if ($id === 'mail-settings' || $id === 'remote-storages' || $id === 'temporary-login') {
                continue;
            }

            /** @var WPStaging\Core\Forms\Form */
            $form = \WPStaging\Core\WPStaging::getInstance()->get("forms")->get($id);

            if ($form === null) {
                continue;
            }
            ?>
            <div id="<?php echo esc_attr($id) ?>__wpstg_header">
                <table class="wpstg-form-table">
                    <thead>
                    <tr class="wpstg-settings-row">
                        <th class="wpstg-settings-row th" colspan="2">
                            <div class="col-title">
                                <strong><?php echo esc_html($name); ?></strong>
                                <span class="description"></span>
                            </div>
                        </th>
                    </tr>
                    </thead>

                    <tbody>
                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php $form->renderLabel("wpstg_settings[queryLimit]"); ?>
                                <span class="description">
                                        <?php
                                        esc_html_e(
                                            "Number of DB rows, that are queried within one request.
                                        The higher the value the faster the database copy process.
                                        To find out the highest possible values try a high value like 10.000 or more. If you get timeout issues, lower it
                                        until you get no more errors during copying process.",
                                            "wp-staging"
                                        ); ?>
                                        <br>
                                        <strong> Default: 10000 </strong>
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php $form->renderInput("wpstg_settings[queryLimit]"); ?>
                        </td>
                    </tr>
                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php $form->renderLabel("wpstg_settings[querySRLimit]"); ?>
                                <span class="description">
                                        <?php
                                        esc_html_e(
                                            "Number of DB rows, that are processed within one request.
                                        The higher the value the faster the database search & replace process.
                                        This is a high memory consumptive process. If you get timeouts lower this value!",
                                            "wp-staging"
                                        ); ?>
                                        <br>
                                        <strong> Default: 5000 </strong>
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php $form->renderInput("wpstg_settings[querySRLimit]"); ?>
                        </td>
                    </tr>

                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php $form->renderLabel("wpstg_settings[fileLimit]"); ?>
                                <span class="description">
                                        <?php
                                        esc_html_e(
                                            "Number of files to be copied within one request. The higher the value, the faster files are copied. To find out the highest possible values, try a high value like 500 or more. If you get timeout problems, decrease the value until no more errors occur during the copy process.",
                                            "wp-staging"
                                        ); ?>
                                        <br>
                                        <br>
                                        <strong><?php esc_html_e("Important:", "wp-staging") ?></strong>
                                        <?php
                                        esc_html_e(
                                            "If CPU Load Priority is LOW, set the file copy limit to 50 to copy files as fast as possible.",
                                            "wp-staging"
                                        ); ?>
                                        <br>
                                        <br>
                                        <strong> Default: 50 </strong>
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php $form->renderInput("wpstg_settings[fileLimit]"); ?>
                        </td>
                    </tr>

                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php $form->renderLabel("wpstg_settings[maxFileSize]"); ?>
                                <span class="description">
                                        <?php
                                        esc_html_e(
                                            "Maximum size of the files that may be copied. All files larger than this will be skipped. Note: Increase this option only if you have a good reason to do so. Files larger than a few megabytes are 99% of the time log and backup files that are not needed on a staging site.",
                                            "wp-staging"
                                        ); ?>
                                        <br>
                                        <strong>Default:</strong> 8 MB
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php $form->renderInput("wpstg_settings[maxFileSize]"); ?>
                        </td>
                    </tr>
                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php $form->renderLabel("wpstg_settings[batchSize]"); ?>
                                <span class="description">
                                        <?php
                                        esc_html_e(
                                            "Buffer size for the file copy process in megabyte.
                                        The higher the value the faster large files are copied.
                                        To find out the highest possible values try a high one and lower it until
                                        you get no more errors during file copy process. Usually this value correlates directly
                                        with the memory consumption of PHP so make sure that
                                        it does not exceed any php.ini max_memory limits.",
                                            "wp-staging"
                                        ); ?>
                                        <br>
                                        <strong>Default:</strong> 2 MB
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php $form->renderInput("wpstg_settings[batchSize]"); ?>
                        </td>
                    </tr>

                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php $form->renderLabel("wpstg_settings[cpuLoad]"); ?>
                                <span class="description">
                                        <?php
                                        echo sprintf(esc_html__(
                                            "Using HIGH will result in fast as possible processing but the cpu load
                                        increases. Using a lower value results in lower cpu load on your server but also slower staging site creation.",
                                            "wp-staging"
                                        ), "<strong>Authorization error 403</strong>"); ?>
                                        <br>
                                        <strong>Default: </strong> Low
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php $form->renderInput("wpstg_settings[cpuLoad]"); ?>
                        </td>
                    </tr>
                    <?php
                    if (!defined('WPSTGPRO_VERSION')) {
                        ?>
                        <tr class="wpstg-settings-row">
                            <td class="wpstg-settings-row th">
                                <div class="col-title">
                                    <?php $form->renderLabel("wpstg_settings[disableAdminLogin]"); ?>
                                    <span class="description">
                                        Remove the requirement to login to the staging site.
                                        <strong>Note:</strong> The staging site always discourages search engines from indexing the site by setting the 'noindex' tag into header of the staging site.
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php $form->renderInput("wpstg_settings[disableAdminLogin]"); ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php $form->renderLabel("wpstg_settings[debugMode]"); ?>
                                <span class="description">
                                        <?php
                                        esc_html_e(
                                            "Enable a debug mode that creates log entries in wp-content/uploads/wp-staging/logs/logfile.log.",
                                            "wp-staging"
                                        );
                                        ?>
                                        <strong>
                                            <?php esc_attr_e('It\'s not recommended to activate this until we ask you to do so!', 'wp-staging') ?>
                                        </strong>
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php $form->renderInput("wpstg_settings[debugMode]"); ?>
                        </td>
                    </tr>
                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php $form->renderLabel("wpstg_settings[optimizer]"); ?>
                                <span class="description">
                                        <?php
                                        esc_html_e(
                                            "The Optimizer is a mu-plugin that disables all other plugins during staging and backup operations.
                                            This lowers memory consumption and speeds up processing. It should always be enabled!",
                                            "wp-staging"
                                        ); ?>
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php $form->renderInput("wpstg_settings[optimizer]"); ?>
                        </td>
                    </tr>
                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php $form->renderLabel("wpstg_settings[unInstallOnDelete]"); ?>
                                <span class="description">
                                        <?php
                                        esc_html_e(
                                            "Activate this if you like to remove all data when the plugin is deleted.
                                        This will not remove staging sites files or database tables.",
                                            "wp-staging"
                                        ); ?>
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php $form->renderInput("wpstg_settings[unInstallOnDelete]"); ?>
                        </td>
                    </tr>

                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php $form->renderLabel("wpstg_settings[checkDirectorySize]"); ?>
                                <span class="description">
                                        <?php
                                        echo sprintf(esc_html__(
                                            "Activate this to check sizes of each directory on scanning process.
                                        %s
                                        Warning this may cause timeout problems in big directory / file structures.",
                                            "wp-staging"
                                        ), "<br>"); ?>
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php $form->renderInput("wpstg_settings[checkDirectorySize]"); ?>
                        </td>
                    </tr>
                    <?php if (defined('WPSTGPRO_VERSION')) : ?>
                        <?php
                        Hooks::callInternalHook(Settings::ACTION_WPSTG_PRO_SETTINGS, [$form]);
                        // show this option only on the staging site
                        if ($this->siteInfo->isStagingSite()) :
                            ?>
                            <tr>
                                <td>
                                    <b class="wpstg-settings-title"><?php esc_html_e('Allow Cloning (Staging Site Only)', 'wp-staging') ?></b>
                                    <p class="wpstg-settings-message">
                                        <?php esc_html_e('Check this box to make this staging site cloneable.', 'wp-staging') ?>
                                        <?php echo sprintf(__("If you would like to know more about cloning staging sites check out <a href='%s' target='_new'>this article</a>.", 'wp-staging'), 'https://wp-staging.com/docs/cloning-a-staging-site-testing-push-method/'); ?>
                                    </p>
                                </td>
                                <td>
                                    <?php Checkbox::render('wpstg-is-staging-cloneable', 'wpstg_settings[isStagingSiteCloneable]', 'true', $this->siteInfo->isCloneable(), ['classes' => 'wpstg-settings-field']); ?>
                                </td>
                            </tr>

                            <?php
                        endif;
                    endif;

                    if (!defined('WPSTGPRO_VERSION')) :
                        ?>
                        <tr class="wpstg-settings-row">
                            <td class="wpstg-settings-row th">
                                <div class="col-title">
                                    <label for="wpstg-compress-backups"><?php esc_html_e('Compress Backups', 'wp-staging') ?></label>
                                    <span class="description">
                                        <?php echo esc_html__('This reduces backup size by up to 60%, making it especially useful for large databases.', 'wp-staging'); ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php
                                Checkbox::render('wpstg-compress-backups', 'wpstg_settings[enableCompression]', 'false', false, ['classes' => 'wpstg-settings-field', 'isDisabled' => true]);
                                ?>
                                <a href="https://wp-staging.com" target="_blank" rel="noopener" class="wpstg-pro-feature-link wpstg-ml-8px">
                                    <span class="wpstg-pro-feature"><?php esc_html_e('Pro Feature', 'wp-staging');?></span>
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                        <tr class="wpstg-settings-row">
                            <td class="wpstg-settings-row th">
                                <b class="wpstg-settings-title"><?php esc_html_e('Email Notifications', 'wp-staging') ?></b>
                                <p class="wpstg-settings-message">
                                    <?php esc_html_e('Send email notifications for backup failures, staging site reminders and other alerts!', 'wp-staging') ?>
                                </p>
                            </td>
                            <td>
                                <?php
                                $isCheckboxChecked = get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_ERROR_REPORT) === 'true';
                                Checkbox::render('wpstg-send-schedules-error-report', 'wpstg_settings[schedulesErrorReport]', 'true', $isCheckboxChecked, ['classes' => 'wpstg-settings-field']);
                                ?>
                            </td>
                        </tr>
                        <tr class="wpstg-settings-row <?php echo $isCheckboxChecked ? '' : 'hidden';?>" id="wpstg-send-schedules-error-report-input">
                            <td>
                                <b class="wpstg-settings-title"><?php esc_html_e('Email Address', 'wp-staging') ?></b>
                                <p class="wpstg-settings-message">
                                    <?php esc_html_e('Send emails to this address', 'wp-staging') ?>
                                </p>
                            </td>
                            <td>
                                <input type="text" id="wpstg-send-schedules-report-email" name="wpstg_settings[schedulesReportEmail]" class="wpstg-settings-field" value="<?php echo esc_attr(get_option(Notifications::OPTION_BACKUP_SCHEDULE_REPORT_EMAIL)) ?>"/>
                            </td>
                        </tr>

                    <tr class="wpstg-settings-row">
                        <td>
                            <b class="wpstg-settings-title"><?php esc_html_e('Email as HTML', 'wp-staging') ?></b>
                            <p class="wpstg-settings-message">
                                <?php esc_html_e('Send emails as HTML', 'wp-staging') ?>
                            </p>
                        </td>
                        <td>
                            <?php
                            $isCheckboxChecked = get_option(\WPStaging\Notifications\Notifications::OPTION_SEND_EMAIL_AS_HTML) === 'true';
                            Checkbox::render('wpstg-send-email-as-html', 'wpstg_settings[emailAsHTML]', 'true', $isCheckboxChecked, ['classes' => 'wpstg-settings-field']);
                            ?>
                        </td>
                    </tr>

                        <?php
                            $attrDisabled = defined('WPSTGPRO_VERSION') ? '' : ' disabled';
                        ?>
                        <tr class="wpstg-settings-row">
                            <td class="wpstg-settings-row th">
                                <b class="wpstg-settings-title"><?php esc_html_e('Slack Notifications', 'wp-staging') ?></b>
                                <p class="wpstg-settings-message">
                                    <?php esc_html_e('If a scheduled backup fails, send a report to the Slack channel.', 'wp-staging') ?>
                                </p>
                            </td>
                            <td>
                                <?php
                                $isCheckboxChecked = get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_SLACK_ERROR_REPORT) === 'true';
                                Checkbox::render('wpstg-send-schedules-slack-error-report', 'wpstg_settings[schedulesSlackErrorReport]', 'true', $isCheckboxChecked, ['classes' => 'wpstg-settings-field', 'isDisabled' => !empty($attrDisabled)]);
                                ?>
                                <?php if (!empty($attrDisabled)) : ?>
                                <a href="https://wp-staging.com" target="_blank" rel="noopener" class="wpstg-pro-feature-link wpstg-ml-8px">
                                    <span class="wpstg-pro-feature"><?php esc_html_e('Pro Feature', 'wp-staging');?></span>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr class="wpstg-settings-row <?php echo $isCheckboxChecked && defined('WPSTGPRO_VERSION') ? '' : 'hidden';?>"  id="wpstg-send-schedules-slack-error-report-input">
                            <td>
                                <b class="wpstg-settings-title"><?php esc_html_e('Slack Webhook URL', 'wp-staging') ?></b>
                                <p class="wpstg-settings-message">
                                    <?php
                                    echo Escape::escapeHtml(__('Send Slack notifications by using a Webhook URL. Read <a href="https://api.slack.com/messaging/webhooks" target=_blank rel="noopener">this article</a> to learn how to create one.', 'wp-staging'));
                                    ?>
                                </p>
                            </td>
                            <td>
                                <input type="text" id="wpstg-send-schedules-report-slack-webhook" name="wpstg_settings[schedulesReportSlackWebhook]" class="wpstg-settings-field" value="<?php echo esc_attr(get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_REPORT_SLACK_WEBHOOK)) ?>"<?php echo esc_attr($attrDisabled);?>/>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
            <?php
        endforeach;
        ?>
        <input type="submit" name="submit" id="submit" class="wpstg-button wpstg-blue-primary" value="<?php esc_html_e('Save Changes', 'wp-staging'); ?>">
        <?php
        unset($tabs);
        ?>
    </form>
</div>
