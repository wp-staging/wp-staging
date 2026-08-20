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

 
use WPStaging\Framework\Facades\UI\Checkbox;

$settingsEnabled = true;
 
$isEmailsAllowed         = true;
$isEmailsReminderEnabled = false;
 
if (!$isPro) {
    $settingsEnabled = false;
}

 
if ($isPro && !empty($options->current)) {





 
 
 



    $defaultEmailsSending = true;
    if (isset($options->existingClones[$options->current]['emailsDisabled'])) {
        $defaultEmailsSending = !((bool)$options->existingClones[$options->current]['emailsDisabled']);
    }

    $isEmailsAllowed         = empty($options->existingClones[$options->current]['isEmailsAllowed']) ? false : true;
 
    if (!isset($options->existingClones[$options->current]['isEmailsAllowed']) && isset($options->existingClones[$options->current]['emailsAllowed'])) {
        $isEmailsAllowed = (bool)$options->existingClones[$options->current]['emailsAllowed'];
    }

    $isEmailsReminderEnabled = empty($options->existingClones[$options->current]['isEmailsReminderEnabled']) ? false : true;
 
    if (!isset($options->existingClones[$options->current]['isEmailsReminderEnabled']) && isset($options->existingClones[$options->current]['emailsReminderAllowed'])) {
        $isEmailsReminderEnabled = (bool)$options->existingClones[$options->current]['emailsReminderAllowed'];
    }
}
?>
<div class="wpstg--advanced-settings--checkbox">
    <label for="wpstg_allow_emails"><?php esc_html_e('Allow Emails Sending', 'wp-staging'); ?></label>
    <?php Checkbox::render('wpstg_allow_emails', 'wpstg_allow_emails', 'true', $isEmailsAllowed, ['isDisabled' => !$settingsEnabled]); ?>
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
    <?php Checkbox::render('wpstg_reminder_emails', 'wpstg_reminder_emails', 'false', $isEmailsReminderEnabled, ['isDisabled' => !$settingsEnabled]); ?>
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
            <?php esc_html_e('You will receive an email reminder every two weeks about your active staging site. This helps you manage and delete unused staging sites, ensuring safety and preventing multiple unnecessary test environments.', 'wp-staging'); ?>
        </span>
    </span>
</div>
