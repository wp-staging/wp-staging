<?php
/**
 * @var \WPStaging\Framework\Analytics\AnalyticsConsent $this
 */
?>
<div class="notice notice-info">
    <p><strong><?php echo esc_html__('Want to Improve WP STAGING?', 'wp-staging') ?></strong></p>
    <p>
        <?php echo esc_html__('Do you allow us sending some non-personal usage information to wp-staging.com? All data will be sent 100% encrypted. You can always disable this in the settings.', 'wp-staging') ?>
    </p>
        <?php echo esc_html__('We don’t collect any personal user information like your mail address or name and will not send you any marketing mails!', 'wp-staging') ?>
    <p>
        <a href="<?php echo esc_url($this->getConsentLink(true)) ?>" class="button-primary"><?php echo esc_html__('Yes, help us by sending usage information', 'wp-staging') ?></a>
        <a href="<?php echo esc_url($this->getConsentLink(false)) ?>" class="button-cancel"><?php echo esc_html__('No, don\'t send any information', 'wp-staging') ?></a>
        <a href="<?php echo esc_url($this->getRemindMeLaterConsentLink()) ?>" class="button-cancel"><?php echo esc_html__('Ask me later', 'wp-staging') ?></a>
    </p>
    <p><?php echo wp_kses_post(__(sprintf('<i>See the data we collect <a href="%s" target="_blank">here</a></i>', 'https://wp-staging.com/what-data-do-we-collect/')), 'wp-staging') ?></p>
</div>
