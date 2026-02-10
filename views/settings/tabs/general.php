<?php

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Facades\UI\Toggle;
use WPStaging\Framework\Settings\Settings;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Notifications\Notifications;
use WPStaging\Framework\Adapter\Directory;

$directory = WPStaging::make(Directory::class);
?>

<!-- General Settings -->
<div class="wpstg-general-settings-wrapper">
    <div class="wpstg-settings-header">
        <div class="wpstg-settings-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915"/><circle cx="12" cy="12" r="3"/></svg>
        </div>
        <div class="wpstg-settings-header-content">
            <h1 class="wpstg-settings-title"><?php esc_html_e('WP Staging Settings', 'wp-staging'); ?></h1>
            <p class="wpstg-settings-subtitle"><?php esc_html_e('Configure your staging and backup preferences', 'wp-staging'); ?></p>
        </div>
    </div>
    <div class="wpstg-settings-container">
        <div class="wpstg-quick-access">
            <div class="wpstg-quick-card">
                <div class="wpstg-quick-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
                </div>
                <div class="wpstg-quick-content">
                    <h3><?php esc_html_e('Database Management', 'wp-staging'); ?></h3>
                    <p><?php esc_html_e('Optimize copy and search operations', 'wp-staging'); ?></p>
                </div>
            </div>

            <div class="wpstg-quick-card">
                <div class="wpstg-quick-icon">
                    <svg class="wpstg-icon-green" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <div class="wpstg-quick-content wpstg-icon-blue">
                    <h3><?php esc_html_e('Security & Access', 'wp-staging'); ?></h3>
                    <p><?php esc_html_e('Control user permissions and access', 'wp-staging'); ?></p>
                </div>
            </div>

            <div class="wpstg-quick-card">
                <div class="wpstg-quick-icon">
                    <svg class="wpstg-icon-blue" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </div>
                <div class="wpstg-quick-content">
                    <h3><?php esc_html_e('Advanced Options', 'wp-staging'); ?></h3>
                    <p><?php esc_html_e('Fine-tune performance settings', 'wp-staging'); ?></p>
                </div>
            </div>
        </div>
        <form class="wpstg-settings-form" method="post" action="options.php">
        <?php
        settings_fields("wpstg_settings");

        foreach ($tabs as $id => $name) :
            if ($id === 'mail-settings' || $id === 'remote-storages' || $id === 'temporary-login' || $id === 'remote-sync-settings') {
                continue;
            }

            /** @var \WPStaging\Core\Forms\Form */
            $form = \WPStaging\Core\WPStaging::getInstance()->get("forms")->get($id);

            if ($form === null) {
                continue;
            }
            ?>
            <div class="wpstg-settings-grid" id="<?php echo esc_attr($id) ?>__wpstg_header">
                <div class="wpstg-settings-column">
                    <div class="wpstg-settings-card wpstg-settings-card-first">
                        <div class="wpstg-settings-card-header">
                            <h3 class="wpstg-settings-card-title"><?php echo esc_html__("Staging: Database & performance", "wp-staging");?></h3>
                            <p class="wpstg-settings-card-description">
                                <?php echo esc_html__("Configure database operations and performance optimization settings", "wp-staging");?>
                            </p>
                        </div>
                        <div class="wpstg-settings-card-body">
                            <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                <div>
                                    <div class="wpstg-settings-field-header">
                                        <span class="wpstg-settings-field-label"><?php $form->renderLabel("wpstg_settings[queryLimit]"); ?></span>
                                        <span class="wpstg-settings-field-badge wpstg-recommended"><?php echo esc_html__('Recommended', 'wp-staging');?></span>
                                    </div>
                                    <div class="wpstg-settings-field-description">
                                        <?php
                                        esc_html_e(
                                            "Number of DB rows, that are queried within one request.
                                        The higher the value the faster the database copy process.
                                        To find out the highest possible values try a high value like 10.000 or more. If you get timeout issues, lower it
                                        until you get no more errors during copying process.",
                                            "wp-staging"
                                        ); ?>
                                    </div>
                                    <div class="wpstg-settings-default-value">
                                        <div>Default:</div>
                                        <div>10000</div>
                                    </div>
                                </div>
                                <div class="wpstg-settings-field-input">
                                    <?php $form->renderInput("wpstg_settings[queryLimit]"); ?>
                                </div>
                            </div>
                            <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                <div>
                                    <div class="wpstg-settings-field-header">
                                        <span class="wpstg-settings-field-label"><?php $form->renderLabel("wpstg_settings[querySRLimit]"); ?></span>
                                    </div>
                                    <div class="wpstg-settings-field-description">
                                        <?php esc_html_e("Number of DB rows processed within one request for search and replace operations. Memory intensive process - lower values for large databases.", "wp-staging"); ?>
                                    </div>
                                    <div class="wpstg-settings-default-value">
                                        <div>Default:</div>
                                        <div>5000</div>
                                    </div>
                                </div>
                                <div class="wpstg-settings-field-input">
                                    <?php $form->renderInput("wpstg_settings[querySRLimit]"); ?>
                                </div>
                            </div>
                            <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                <div>
                                    <div class="wpstg-settings-field-header">
                                        <span class="wpstg-settings-field-label"><?php $form->renderLabel("wpstg_settings[fileLimit]"); ?></span>
                                    </div>
                                    <div class="wpstg-settings-field-description">
                                        <?php esc_html_e("Number of files copied within one request. Higher values speed up file copying but may cause timeouts with large file sets.", "wp-staging"); ?>
                                    </div>
                                    <div class="wpstg-settings-default-value">
                                        <div>Default:</div>
                                        <div>50</div>
                                    </div>
                                </div>
                                <div class="wpstg-settings-field-input wpstg-select">
                                    <?php $form->renderInput("wpstg_settings[fileLimit]"); ?>
                                </div>
                            </div>
                            <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                <div>
                                    <div class="wpstg-settings-field-header">
                                        <span class="wpstg-settings-field-label"><?php $form->renderLabel("wpstg_settings[maxFileSize]"); ?></span>
                                    </div>
                                    <div class="wpstg-settings-field-description">
                                        <?php esc_html_e("Maximum size of files that can be copied. Files larger than this will be skipped to prevent memory issues during staging.", "wp-staging"); ?>
                                    </div>
                                    <div class="wpstg-settings-default-value">
                                        <div>Default:</div>
                                        <div>8 MB</div>
                                    </div>
                                </div>
                                <div class="wpstg-settings-field-input">
                                    <?php $form->renderInput("wpstg_settings[maxFileSize]"); ?>
                                </div>
                            </div>
                            <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                <div>
                                    <div class="wpstg-settings-field-header">
                                        <span class="wpstg-settings-field-label"><?php $form->renderLabel("wpstg_settings[batchSize]"); ?></span>
                                    </div>
                                    <div class="wpstg-settings-field-description">
                                        <?php
                                        esc_html_e(
                                            "Buffer size for the file copy process in megabytes.
                                        The higher the value the faster large files are copied.
                                        To find out the highest possible values try a high one and lower it until
                                        you get no more errors during file copy process. Usually this value correlates directly
                                        with the memory consumption of PHP so make sure that
                                        it does not exceed any php.ini max_memory limits.",
                                            "wp-staging"
                                        ); ?>
                                    </div>
                                    <div class="wpstg-settings-default-value">
                                        <div>Default:</div>
                                        <div>2 MB</div>
                                    </div>
                                </div>
                                <div class="wpstg-settings-field-input">
                                    <?php $form->renderInput("wpstg_settings[batchSize]"); ?>
                                </div>
                            </div>
                            <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                <div>
                                    <div class="wpstg-settings-field-header">
                                        <span class="wpstg-settings-field-label"><?php $form->renderLabel("wpstg_settings[cpuLoad]"); ?></span>
                                    </div>
                                    <div class="wpstg-settings-field-description">
                                        <?php esc_html_e("Using HIGH results in faster processing but higher CPU load. Using lower values reduces server load but slows staging site creation.", "wp-staging"); ?>
                                    </div>
                                    <div class="wpstg-settings-default-value">
                                        <div>Default:</div>
                                        <div>Low</div>
                                    </div>
                                </div>
                                <div class="wpstg-settings-field-input wpstg-select">
                                    <?php $form->renderInput("wpstg_settings[cpuLoad]"); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Backup & Notifications Settings -->
                    <div class="wpstg-settings-card wpstg-settings-card-first">
                        <div class="wpstg-settings-card-header">
                            <h3 class="wpstg-settings-card-title"><?php esc_html_e("Backup & Notifications", "wp-staging");?></h3>
                            <p class="wpstg-settings-card-description"><?php esc_html_e("Configure backup compression and notification preferences", "wp-staging");?></p>
                        </div>
                        <div class="wpstg-settings-card-body">
                            <?php if (!defined('WPSTGPRO_VERSION')) : ?>
                                <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                    <div>
                                        <div class="wpstg-settings-field-header">
                                            <span class="wpstg-settings-field-label"><?php esc_html_e('Compress Backups', 'wp-staging') ?></span>
                                            <a href="https://wp-staging.com/#pricing" target="_blank" rel="noopener" class="wpstg-button danger wpstg-banner-button"><?php esc_html_e('Upgrade Now', 'wp-staging');?></a>
                                        </div>
                                        <div class="wpstg-settings-field-description">
                                            <?php echo esc_html__('This reduces backup size by up to 60%, making it especially useful for large databases.', 'wp-staging'); ?>
                                        </div>
                                    </div>
                                    <div class="wpstg-settings-field-input">
                                        <?php
                                        Toggle::render('wpstg-compress-backups', 'wpstg_settings[enableCompression]', 'false', false, ['classes' => 'wpstg-settings-field', 'isDisabled' => true]);
                                        ?>
                                    </div>
                                </div>
                            <?php else : ?>
                                <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                    <div>
                                        <div class="wpstg-settings-field-header">
                                            <span class="wpstg-settings-field-label"><?php $form->renderLabel("wpstg_settings[enableCompression]"); ?></span>
                                            <span class="wpstg-settings-field-badge wpstg-recommended"><?php echo esc_html__('Recommended', 'wp-staging');?></span>
                                        </div>
                                        <div class="wpstg-settings-field-description">
                                            <?php echo esc_html__('This reduces backup size by up to 60%, making it especially useful for large databases.', 'wp-staging'); ?>
                                        </div>
                                    </div>
                                    <div class="wpstg-settings-field-input">
                                        <?php $form->renderInput("wpstg_settings[enableCompression]"); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <hr class="wpstg-settings-separator"/>
                            <div class="wpstg-settings-field wpstg-settings-has-toggle wpstg-notification-toggles">
                                <div class="wpstg-notification-toggles-header">
                                    <span class="wpstg-settings-field-label"><?php esc_html_e('Email Notifications', 'wp-staging'); ?></span>
                                </div>
                                <div class="wpstg-notification-toggles-input">
                                    <div class="wpstg-notification-toggle-row">
                                        <div class="wpstg-notification-toggle-label">
                                            <?php esc_html_e('Errors', 'wp-staging'); ?>
                                        </div>
                                        <div class="wpstg-notification-toggle-switch">
                                            <?php
                                            $isErrorCheckboxChecked = get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_ERROR_REPORT) === 'true';
                                            Toggle::render('wpstg-send-schedules-error-report', 'wpstg_settings[schedulesErrorReport]', 'true', $isErrorCheckboxChecked, ['classes' => 'wpstg-settings-field']);
                                            ?>
                                        </div>
                                    </div>
                                    <div class="wpstg-notification-toggle-row">
                                        <div class="wpstg-notification-toggle-label">
                                            <?php esc_html_e('Warnings', 'wp-staging'); ?>
                                        </div>
                                        <div class="wpstg-notification-toggle-switch">
                                            <?php
                                            $isWarningCheckboxChecked = get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_WARNING_REPORT) === 'true';
                                            Toggle::render('wpstg-send-schedules-warning-report', 'wpstg_settings[schedulesWarningReport]', 'true', $isWarningCheckboxChecked, ['classes' => 'wpstg-settings-field']);
                                            ?>
                                        </div>
                                    </div>
                                    <div class="wpstg-notification-toggle-row">
                                        <div class="wpstg-notification-toggle-label">
                                            <?php esc_html_e('General Backup Status', 'wp-staging'); ?>
                                        </div>
                                        <div class="wpstg-notification-toggle-switch">
                                            <?php
                                            $isGeneralCheckboxChecked = get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_GENERAL_REPORT) === 'true';
                                            Toggle::render('wpstg-send-schedules-general-report', 'wpstg_settings[schedulesGeneralReport]', 'true', $isGeneralCheckboxChecked, ['classes' => 'wpstg-settings-field']);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="wpstg-email-controls-container wpstg-two-column-settings <?php echo $isErrorCheckboxChecked || $isWarningCheckboxChecked || $isGeneralCheckboxChecked ? '' : 'hidden';?>" id="wpstg-send-schedules-email-notification-input">
                                <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                    <div>
                                        <div class="wpstg-settings-field-header">
                                            <span class="wpstg-settings-field-label"><?php esc_html_e('Email Address', 'wp-staging'); ?></span>
                                        </div>
                                        <div class="wpstg-settings-field-description">
                                            <?php esc_html_e('Send emails to this address', 'wp-staging'); ?>
                                        </div>
                                    </div>
                                    <div class="wpstg-settings-field-input">
                                        <input type="text" id="wpstg-send-schedules-report-email" name="wpstg_settings[schedulesReportEmail]" class="wpstg-settings-field" value="<?php echo esc_attr(get_option(Notifications::OPTION_BACKUP_SCHEDULE_REPORT_EMAIL)) ?>"/>
                                    </div>
                                    <div>
                                        <div class="wpstg-settings-field-header">
                                            <span class="wpstg-settings-field-label"><?php esc_html_e('Email as HTML', 'wp-staging') ?></span>
                                        </div>
                                        <div class="wpstg-settings-field-description">
                                            <?php esc_html_e('Send emails as HTML', 'wp-staging') ?>
                                        </div>
                                    </div>
                                    <div class="wpstg-settings-field-input">
                                        <?php
                                        $isCheckboxChecked = get_option(\WPStaging\Notifications\Notifications::OPTION_SEND_EMAIL_AS_HTML) === 'true';
                                        Toggle::render('wpstg-send-email-as-html', 'wpstg_settings[emailAsHTML]', 'true', $isCheckboxChecked, ['classes' => 'wpstg-settings-field']);
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $attrDisabled = defined('WPSTGPRO_VERSION') ? '' : ' disabled';
                            ?>
                            <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                <div>
                                    <div class="wpstg-settings-field-header">
                                        <span class="wpstg-settings-field-label"><?php esc_html_e('Slack Notifications', 'wp-staging'); ?></span>
                                        <?php if (!defined('WPSTGPRO_VERSION')) : ?>
                                        <a href="https://wp-staging.com/#pricing" target="_blank" rel="noopener" class="wpstg-button danger wpstg-banner-button"><?php esc_html_e('Upgrade Now', 'wp-staging');?></a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="wpstg-settings-field-description">
                                        <?php esc_html_e('If a scheduled backup fails, send a report to the Slack channel.', 'wp-staging'); ?>
                                    </div>
                                </div>
                                <div class="wpstg-settings-field-input">
                                    <?php
                                    $isCheckboxChecked = get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_SLACK_ERROR_REPORT) === 'true';
                                    Toggle::render('wpstg-send-schedules-slack-error-report', 'wpstg_settings[schedulesSlackErrorReport]', 'true', $isCheckboxChecked, ['classes' => 'wpstg-settings-field', 'isDisabled' => !empty($attrDisabled)]);
                                    ?>
                                </div>
                            </div>
                            <div class="wpstg-settings-field  <?php echo $isCheckboxChecked && defined('WPSTGPRO_VERSION') ? '' : 'hidden';?>"  id="wpstg-send-schedules-slack-error-report-input">
                                <div>
                                    <div class="wpstg-settings-field-header">
                                        <label for="wpstg-send-schedules-report-slack-webhook" class="wpstg-settings-field-label"><?php esc_html_e('Slack Webhook URL', 'wp-staging'); ?></label>
                                    </div>
                                    <div class="wpstg-settings-field-description">
                                        <?php
                                        $link = '<a href="https://api.slack.com/messaging/webhooks" target="_blank" rel="noopener">' .
                                                esc_html__('Slack webhooks documentation', 'wp-staging') .
                                                '</a>';
                                        echo wp_kses_post(sprintf(
                                            /* translators: %s is a link to Slack webhook documentation */
                                            Escape::escapeHtml(__('Send Slack notifications by using a Webhook URL. Read the %s to learn how to create one.', 'wp-staging')),
                                            $link
                                        ));
                                        ?>
                                    </div>
                                </div>
                                <div class="wpstg-settings-field-input">
                                    <input type="text" id="wpstg-send-schedules-report-slack-webhook" name="wpstg_settings[schedulesReportSlackWebhook]" class="wpstg-settings-field" value="<?php echo esc_attr(get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_REPORT_SLACK_WEBHOOK)) ?>"<?php echo esc_attr($attrDisabled);?>/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Backup & Notifications Settings -->
                </div>
                <div class="wpstg-settings-column">
                    <!-- Access Control Settings -->
                    <div class="wpstg-settings-card wpstg-settings-card-fixed">
                        <div class="wpstg-settings-card-header">
                            <h3 class="wpstg-settings-card-title"><?php esc_html_e("Access Control", "wp-staging");?></h3>
                            <p class="wpstg-settings-card-description"><?php esc_html_e("Manage user permissions and access control for staging operations", "wp-staging");?></p>
                        </div>
                        <div class="wpstg-settings-card-body">
                            <?php if (defined("WPSTGPRO_VERSION")) : ?>
                                <?php Hooks::callInternalHook(Settings::ACTION_WPSTG_PRO_SETTINGS, [$form]); ?>
                            <?php else :?>
                                <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                    <div>
                                        <div class="wpstg-settings-field-header">
                                            <span class="wpstg-settings-field-label">
                                                <?php echo esc_html__("Keep Permalinks", "wp-staging"); ?>
                                            </span>
                                            <a href="https://wp-staging.com/#pricing" target="_blank" rel="noopener" class="wpstg-button danger wpstg-banner-button"><?php esc_html_e('Upgrade Now', 'wp-staging');?></a>
                                        </div>
                                        <div class="wpstg-settings-field-description">
                                            <?php
                                            echo wp_kses_post(sprintf(
                                                __(
                                                    'Use on the staging site the same permalink structure and do not set permalinks to plain structure. <br/>Read more: <a href="%1$s" target="_blank">Permalink Settings</a> ',
                                                    'wp-staging'
                                                ),
                                                'https://wp-staging.com/docs/activate-permalinks-staging-site/'
                                            )); ?>
                                        </div>
                                    </div>
                                    <div class="wpstg-settings-field-input">
                                        <?php Toggle::render('wpstg-keep-permalinks', 'wpstg-keep-permalinks', 'false', false, ['classes' => 'wpstg-settings-field', 'isDisabled' => true]); ?>
                                    </div>
                                </div>

                                <div class="wpstg-settings-field">
                                    <div>
                                        <div class="wpstg-settings-field-header">
                                            <span class="wpstg-settings-field-label">
                                                <?php echo esc_html__("Access Permissions", "wp-staging"); ?>
                                            </span>
                                            <a href="https://wp-staging.com/#pricing" target="_blank" rel="noopener" class="wpstg-button danger wpstg-banner-button"><?php esc_html_e('Upgrade Now', 'wp-staging');?></a>
                                        </div>
                                        <div class="wpstg-settings-field-description">
                                            <?php
                                            echo Escape::escapeHtml(__(
                                                'Select the user role you want to give access to the staging site. You can select multiple roles by holding CTRL or ⌘ Cmd key while clicking. <strong>Change this option on the staging site if you want to change the authentication behavior there.</strong>',
                                                'wp-staging'
                                            )); ?>
                                        </div>
                                    </div>
                                    <div class="wpstg-settings-field-input wpstg-select wpstg-multi-select">
                                        <?php
                                        $roles = wp_roles()->get_names();
                                        echo '<select multiple disabled>';
                                        echo '<option value="all">Allow access from all visitors</option>';

                                        foreach ($roles as $key => $name) {
                                            echo '<option value="' . esc_attr($key) . '">' . esc_html($name) . '</option>';
                                        }

                                        echo '</select>';
                                        ?>
                                    </div>
                                </div>
                                <div class="wpstg-settings-field">
                                    <div>
                                        <div class="wpstg-settings-field-header">
                                            <label class="wpstg-settings-field-label">
                                                <?php echo esc_html__("Users With Staging Access", "wp-staging"); ?>
                                            </label>
                                            <a href="https://wp-staging.com/#pricing" target="_blank" rel="noopener" class="wpstg-button danger wpstg-banner-button"><?php esc_html_e('Upgrade Now', 'wp-staging');?></a>
                                        </div>
                                        <div class="wpstg-settings-field-description">
                                            <?php
                                            echo Escape::escapeHtml(__(
                                                'Specify users who will have access to the staging site regardless of their role. You can enter multiple user names separated by a comma. <strong>Change this option on the staging site if you want to change the authentication behavior there.</strong>',
                                                'wp-staging'
                                            )); ?>
                                        </div>
                                    </div>
                                    <div class="wpstg-settings-field-input">
                                        <input type="text" value="" name="wpstg-users-with-staging-access" disabled>
                                    </div>
                                </div>
                                <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                    <div>
                                        <div class="wpstg-settings-field-header">
                                            <label class="wpstg-settings-field-label">
                                                <?php echo esc_html__("Admin Bar Background Color", "wp-staging"); ?>
                                            </label>
                                            <a href="https://wp-staging.com/#pricing" target="_blank" rel="noopener" class="wpstg-button danger wpstg-banner-button"><?php esc_html_e('Upgrade Now', 'wp-staging');?></a>
                                        </div>
                                        <div class="wpstg-settings-field-description">
                                        </div>
                                    </div>
                                    <div class="wpstg-settings-field-input">
                                        <input type="color" value="#ff8d00" disabled>
                                    </div>
                                </div>
                                <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                    <div>
                                        <div class="wpstg-settings-field-header">
                                            <span class="wpstg-settings-field-label"><?php $form->renderLabel("wpstg_settings[disableAdminLogin]"); ?></span>
                                        </div>
                                        <div class="wpstg-settings-field-description">
                                            <?php
                                            echo sprintf(esc_html__(
                                                "Remove the requirement to login to the staging site. %s The staging site always discourages search engines from indexing the site by setting the 'noindex' tag into header of the staging site.",
                                                "wp-staging"
                                            ), "<strong>Note:</strong>"); ?>
                                        </div>
                                    </div>
                                    <div class="wpstg-settings-field-input">
                                        <?php $form->renderInput("wpstg_settings[disableAdminLogin]"); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (defined('WPSTGPRO_VERSION') && $this->siteInfo->isStagingSite()) : ?>
                                <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                    <div>
                                        <div class="wpstg-settings-field-header">
                                            <span class="wpstg-settings-field-label">
                                                <?php esc_html_e('Allow Cloning (Staging Site Only)', 'wp-staging') ?>
                                            </span>
                                        </div>
                                        <div class="wpstg-settings-field-description">
                                            <?php esc_html_e('Check this box to make this staging site cloneable.', 'wp-staging') ?>
                                            <?php echo sprintf(__("If you would like to know more about cloning staging sites check out <a href='%s' target='_new'>this article</a>.", 'wp-staging'), 'https://wp-staging.com/docs/cloning-a-staging-site-testing-push-method/'); ?>
                                        </div>
                                    </div>
                                    <div class="wpstg-settings-field-input">
                                        <?php Toggle::render('wpstg-is-staging-cloneable', 'wpstg_settings[isStagingSiteCloneable]', 'true', $this->siteInfo->isCloneable(), ['classes' => 'wpstg-settings-field']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Access Control Settings -->
                    <!-- Debug & System Settings -->
                    <div class="wpstg-settings-card">
                        <div class="wpstg-settings-card-header">
                            <h3 class="wpstg-settings-card-title"><?php esc_html_e("System & Debugging", "wp-staging");?></h3>
                            <p class="wpstg-settings-card-description"><?php esc_html_e("Advanced system settings and debugging options", "wp-staging");?></p>
                        </div>
                        <div class="wpstg-settings-card-body">
                            <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                <div>
                                    <div class="wpstg-settings-field-header">
                                        <span class="wpstg-settings-field-label"><?php $form->renderLabel("wpstg_settings[debugMode]"); ?></span>
                                        <span class="wpstg-settings-field-badge wpstg-caution"><?php echo esc_html__('Caution', 'wp-staging');?></span>
                                    </div>
                                    <div class="wpstg-settings-field-description">
                                        <?php
                                        esc_html_e(
                                            "Enable a debug mode that creates log entries in wp-content/uploads/wp-staging/logs/logfile.log.",
                                            "wp-staging"
                                        );
                                        ?>
                                        <strong>
                                            <?php esc_attr_e('It\'s not recommended to activate this until we ask you to do so!', 'wp-staging') ?>
                                        </strong>
                                    </div>
                                </div>
                                <div class="wpstg-settings-field-input">
                                    <?php $form->renderInput("wpstg_settings[debugMode]"); ?>
                                </div>
                            </div>

                            <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                <div>
                                    <div class="wpstg-settings-field-header">
                                        <span class="wpstg-settings-field-label"><?php $form->renderLabel("wpstg_settings[optimizer]"); ?></span>
                                        <span class="wpstg-settings-field-badge wpstg-recommended"><?php esc_html_e("Recommended", "wp-staging");?></span>
                                    </div>
                                    <div class="wpstg-settings-field-description">
                                        <?php
                                        esc_html_e(
                                            "The Optimizer is a mu-plugin that disables all other plugins during staging and backup operations.
                                        This lowers memory consumption and speeds up processing. It should always be enabled!",
                                            "wp-staging"
                                        ); ?>
                                    </div>
                                </div>
                                <div class="wpstg-settings-field-input">
                                    <?php $form->renderInput("wpstg_settings[optimizer]"); ?>
                                </div>
                            </div>
                            <div class="wpstg-settings-field wpstg-settings-has-toggle">
                                <div>
                                    <div class="wpstg-settings-field-header">
                                        <span class="wpstg-settings-field-label"><?php $form->renderLabel("wpstg_settings[unInstallOnDelete]"); ?></span>
                                        <span class="wpstg-settings-field-badge wpstg-caution"><?php echo esc_html__('Caution', 'wp-staging');?></span>
                                    </div>
                                    <div class="wpstg-settings-field-description">
                                        <?php
                                        esc_html_e(
                                            "Remove all WP STAGING settings and data on uninstall. Staging site data, backups, and related database tables will not be deleted unless empty.",
                                            "wp-staging"
                                        );
                                        ?>
                                        <br><br>
                                        <strong><?php echo esc_html__("Note:", "wp-staging"); ?></strong>
                                        <br>
                                        <?php
                                        echo sprintf(
                                            esc_html__("The backups folder %s will only be deleted if it does not contain any backup files.", "wp-staging"),
                                            "<strong>" . esc_html($directory->getBackupDirectory()) . "</strong>"
                                        );
                                        ?>
                                        <br>
                                        <?php esc_html_e("Staging site data is never deleted while staging sites exist.", "wp-staging");?>
                                    </div>
                                </div>
                                <div class="wpstg-settings-field-input">
                                    <?php $form->renderInput("wpstg_settings[unInstallOnDelete]"); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Debug & System Settings -->
                </div>
            </div>
            <?php if (defined('WPSTGPRO_VERSION')) : ?>
            <div class="wpstg-settings-card wpstg-full-width">
                <div class="wpstg-settings-card-header">
                    <h3 class="wpstg-settings-card-title"><?php esc_html_e("Usage Information", "wp-staging");?></h3>
                    <p class="wpstg-settings-card-description"><?php esc_html_e("Help improve this plugin by sharing anonymous usage data.", "wp-staging");?></p>
                </div>
                <div class="wpstg-settings-card-body">
                    <div class="wpstg-settings-field wpstg-settings-has-toggle">
                        <div>
                            <div class="wpstg-settings-field-header">
                                <label class="wpstg-settings-field-label"><?php esc_html_e("Send Usage Information", "wp-staging");?></label>
                            </div>
                            <div class="wpstg-settings-field-description">
                                <?php esc_html_e("Send usage information to wp-staging.com to help improve the plugin. No personal data is collected.", "wp-staging");
                                echo '<br/><i>' . wp_kses_post(sprintf(__('See the data we collect <a href="%s" target="_blank">here</a>', 'wp-staging'), 'https://wp-staging.com/what-data-do-we-collect/')) . '</i>';
                                ?>
                            </div>
                        </div>
                        <div class="wpstg-settings-field-input">
                            <?php
                            $analytics        = \WPStaging\Core\WPStaging::make(\WPStaging\Framework\Analytics\AnalyticsConsent::class);
                            $analyticsAllowed = $analytics->hasUserConsent();
                            $isChecked        = $analyticsAllowed === '1' || $analyticsAllowed === true;
                            ?>
                            <span id="wpstg-analytics-consent-allowed-link" data-href="<?php echo esc_url($analytics->getConsentLink(true)) ?>"></span>
                            <span id="wpstg-analytics-consent-decline-link" data-href="<?php echo esc_url($analytics->getConsentLink(false)) ?>"></span>
                            <?php
                            Toggle::render('wpstg-analytics-consent', 'analytics_consent', 'true', $isChecked, ['classes' => 'wpstg-settings-field']);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php
        endforeach;
        ?>
            <div class="wpstg-settings-actions">
                <button type="button" class="wpstg-btn wpstg-btn-md wpstg-btn-secondary" id="wpstg-reset-settings-to-defaults">
                    <svg class="wpstg-btn-icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18l-2 13H5L3 6z"></path>
                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    <?php echo esc_html__('Reset to Defaults', 'wp-staging');?>
                </button>
                <button type="submit" class="wpstg-btn wpstg-btn-md wpstg-btn-primary">
                    <svg class="wpstg-btn-icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    <?php echo esc_html__('Save Changes', 'wp-staging');?>
                </button>
            </div>
        <?php
        unset($tabs);
        ?>
    </form>
</div>