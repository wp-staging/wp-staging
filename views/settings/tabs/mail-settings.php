<?php

use WPStaging\Framework\Facades\UI\Checkbox;

$numberOfLoadingBars = 5;
include(WPSTG_VIEWS_DIR . '_main/loading-placeholder.php');
?>
<form class="wpstg-mail-settings-form" method="post">
    <?php $emailsAllowed = !((bool)(new \WPStaging\Staging\CloneOptions())->get((\WPStaging\Staging\FirstRun::MAILS_DISABLED_KEY))); ?>
    <p>
        <strong class="wpstg-fs-14"> <?php esc_html_e('Mail Delivery Setting', 'wp-staging'); ?></strong>
        <br/>
        <?php esc_html_e('Toggle mails sending for this staging site', 'wp-staging'); ?>
    </p>
    <div class="wpstg-form-group">
        <label for="wpstg_allow_emails">
            <?php esc_html_e('Allow Mails Sending:', 'wp-staging'); ?>
            <?php Checkbox::render('wpstg_allow_emails', 'wpstg_allow_emails', '', $emailsAllowed); ?>
        </label>
    </div>
    <p>
        <b><?php esc_html_e('Note', 'wp-staging') ?>: </b> <?php echo sprintf(__('Some plugins might still be able to send out mails if they don\'t depend upon %s.', 'wp-staging'), '<code>wp_mail()</code>'); ?>
    </p>
    <button type="button" id="wpstg-update-mail-settings" class="wpstg-button wpstg-blue-primary"><?php esc_html_e("Update Settings", "wp-staging") ?></button>
</form>
