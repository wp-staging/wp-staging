<?php
/**
 * @var string $label
 */
?>
<div class="notice notice-error">
    <p>
        <strong><?php printf(esc_html__('Action required: %s needs to be set up again.', 'wp-staging'), esc_html($label)); ?></strong>
        <br>
        <?php esc_html_e('The encryption key has changed. Please re-enter and save your settings.', 'wp-staging'); ?>
    </p>
    <p><?php esc_html_e('Optionally you can reuse the old encryption key.', 'wp-staging'); ?> <a href="<?php echo esc_url('https://wp-staging.com/docs/wp-staging-encryption-setup/'); ?>" target="_blank" rel="noopener noreferrer"><strong><?php esc_html_e('Where to get the original key.', 'wp-staging'); ?></strong></a></p>
</div>
