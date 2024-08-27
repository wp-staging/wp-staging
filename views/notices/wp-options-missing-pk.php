<?php
/**
 * @var string $optionTable
 * @var bool $isPrimaryKeyMissing
 * @var bool $isPrimaryKeyIsOptionName
 * @see \WPStaging\Framework\Notices\Notices::renderNotices
 */
?>
<div class="notice notice-error">
    <p>
    <?php if ($isPrimaryKeyMissing) : ?>
        <strong><?php echo sprintf(esc_html__('WP STAGING - The table %s has no primary key index or missing autoincrement flag.', 'wp-staging'), esc_html($optionTable))?></strong>
        <br>
        <?php esc_html_e('This is a serious issue and needs to be fixed as soon as possible. The longer you wait, the harder it gets to repair it and can make your site inaccessible at worst. We recommend fixing it right now! This error has either been caused by a person with access to the database or by another plugin. You should not do any changes to your site until this is fixed.', 'wp-staging') ?>
    <?php elseif ($isPrimaryKeyIsOptionName) : ?>
      <strong><?php echo sprintf(esc_html__('WP STAGING - The primary key for table %s has been changed from option_id to option_name.', 'wp-staging'), esc_html($optionTable))?></strong>
        <br>
        <?php esc_html_e('If you encounter performance issues during cloning or backup, we recommend changing it back to option_id as the primary key.', 'wp-staging') ?>
    <?php endif; ?>
    </p>
    <p><a href="https://wp-staging.com/docs/missing-primary-key-in-table-wp-options" target="_blank"><strong><?php esc_html_e('How to fix this.', 'wp-staging') ?></strong></a></p>
</div>
