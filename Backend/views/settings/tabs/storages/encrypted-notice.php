<?php
/**
 * @var string $providerName
 */
?>
<div class="notice notice-error">
    <p>
      <strong><?php printf(esc_html__('Please setup again the storage provider %s.', 'wp-staging'), esc_html($providerName))?></strong>
        <br>
        <?php esc_html_e('The data has been encrypted and the storage provider needs to be reconnected because the key has changed.', 'wp-staging') ?>
    </p>
    <p><?php esc_html_e('Optionally you can reuse the old encryption key.', 'wp-staging') ?><a href="https://wp-staging.com/docs/wp-staging-encryption-setup/" target="_blank"><strong><?php esc_html_e('Where to get the original key.', 'wp-staging') ?></strong></a></p>
</div>