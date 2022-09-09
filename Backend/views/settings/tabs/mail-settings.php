<form class="wpstg-mail-settings-form" method="post">
    <?php $emailsAllowed = !((bool)(new \WPStaging\Framework\Staging\CloneOptions())->get((\WPStaging\Framework\Staging\FirstRun::MAILS_DISABLED_KEY))); ?>        
    <p>
        <strong class="wpstg-fs-14"> <?php esc_html_e('Mail Delivery Setting', 'wp-staging'); ?></strong>
        <br/>
        <?php esc_html_e('Toggle mails sending for this staging site', 'wp-staging'); ?>
    </p>
    <div class="wpstg-form-group">
        <label class="wpstg-checkbox" for="wpstg_allow_emails">
            <?php esc_html_e('Allow Mails Sending:', 'wp-staging'); ?> <input type="checkbox" name="wpstg_allow_emails" id="wpstg_allow_emails" <?php echo $emailsAllowed === true ? 'checked' : '' ?>>
        </label>
    </div>
    <p>
        <b><?php esc_html_e('Note', 'wp-staging') ?>: </b> <?php echo sprintf(__('Some plugins might still be able to send out mails if they don\'t depend upon %s.', 'wp-staging'), '<code>wp_mail()</code>'); ?>
    </p>
    <button type="button" id="wpstg-update-mail-settings" class="wpstg-link-btn wpstg-blue-primary"><?php esc_html_e("Update Settings", "wp-staging") ?></button>
</form>
