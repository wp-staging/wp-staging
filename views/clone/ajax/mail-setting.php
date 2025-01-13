<?php

/**
 * This file is currently being called for the both FREE and PRO version:
 * src/views/clone/ajax/scan.php:64
 *
 * @var \WPStaging\Backend\Modules\Jobs\Scan $scan
 * @var stdClass                             $options
 * @var boolean                              $isPro
 *
 * @see \WPStaging\Backend\Modules\Jobs\Scan::start For details on $options.
 */

// Settings Enabled by default
use WPStaging\Framework\Facades\UI\Checkbox;

$settingsEnabled = true;
// New staging site. Mails Sending is checked by default.
$emailsAllowed   = true;
$emailsReminderAllowed = false;
// If plugin is not pro disable this Option
if (!$isPro) {
    $settingsEnabled = false;
}

// Only change default check status when clone options exists plugin is PRO
if ($isPro && !empty($options->current)) {
    /**
     * Existing staging site.
     * We read the site configuration. If none set, default to checked, since not having the setting
     * to allow the email in the database means it was not disabled.
     */
    // To support staging site created with older version of this feature,
    // Invert it's value if it is present
    // Can be removed when we are sure that all staging sites have been updated.
    $defaultEmailsSending = true;
    if (isset($options->existingClones[$options->current]['emailsDisabled'])) {
        $defaultEmailsSending = !((bool)$options->existingClones[$options->current]['emailsDisabled']);
    }

    $emailsAllowed         = empty($options->existingClones[$options->current]['emailsAllowed']) ? false : true;
    $emailsReminderAllowed = empty($options->existingClones[$options->current]['emailsReminderAllowed']) ? false : true;
} ?>
<div class="wpstg--advanced-settings--checkbox">
    <label for="wpstg_allow_emails"><?php esc_html_e('Allow Emails Sending', 'wp-staging'); ?></label>
    <?php Checkbox::render('wpstg_allow_emails', 'wpstg_allow_emails', 'true', $emailsAllowed, ['isDisabled' => !$settingsEnabled]); ?>
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
            <?php esc_html_e('Allow emails sending for this staging site.', 'wp-staging'); ?>
            <br /> <br />
            <b><?php esc_html_e('Note', 'wp-staging') ?>: </b> <?php echo sprintf(esc_html__('Even if email sending is disabled, some plugins might still be able to send out mails if they don\'t depend upon %s.', 'wp-staging'), '<code>wp_mail()</code>'); ?>
        </span>
    </span>
</div>
<div class="wpstg--advanced-settings--checkbox">
    <label for="wpstg_reminder_emails"><?php esc_html_e('Get Reminder Email', 'wp-staging'); ?></label>
    <?php Checkbox::render('wpstg_reminder_emails', 'wpstg_reminder_emails', 'false', $emailsReminderAllowed, ['isDisabled' => !$settingsEnabled]); ?>
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
            <?php esc_html_e('You will receive an email reminder every two weeks about your active staging site. This helps you manage and delete unused staging sites, ensuring safety and preventing multiple unnecessary test environments.', 'wp-staging'); ?>
        </span>
    </span>
</div>
