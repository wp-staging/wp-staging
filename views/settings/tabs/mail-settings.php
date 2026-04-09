<?php

use WPStaging\Framework\Facades\UI\Checkbox;

?>
<div class="wpstg-provider-page-header">
    <h1 class="wpstg-text-2xl wpstg-font-semibold wpstg-text-slate-900 dark:wpstg-text-slate-100"><?php esc_html_e('Mail Settings', 'wp-staging'); ?></h1>
    <p class="wpstg-mt-1 wpstg-text-sm wpstg-text-slate-600 dark:wpstg-text-slate-400"><?php esc_html_e('Control email delivery on this staging site.', 'wp-staging'); ?></p>
</div>
<form class="wpstg-mail-settings-form" method="post">
    <?php $isEmailsAllowed = !((bool)(new \WPStaging\Staging\CloneOptions())->get((\WPStaging\Staging\FirstRun::MAILS_DISABLED_KEY))); ?>
    <div class="wpstg-form-group">
        <label for="wpstg_allow_emails">
            <?php esc_html_e('Allow Mails Sending:', 'wp-staging'); ?>
            <?php Checkbox::render('wpstg_allow_emails', 'wpstg_allow_emails', '', $isEmailsAllowed); ?>
        </label>
    </div>
    <p>
        <b><?php esc_html_e('Note', 'wp-staging') ?>: </b> <?php echo sprintf(__('Some plugins might still be able to send out mails if they don\'t depend upon %s.', 'wp-staging'), '<code>wp_mail()</code>'); ?>
    </p>
    <button type="button" id="wpstg-update-mail-settings" class="wpstg-button wpstg-blue-primary"><?php esc_html_e("Update Settings", "wp-staging") ?></button>
</form>
