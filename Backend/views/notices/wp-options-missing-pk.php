<?php
/**
 * @var string $optionTable
 * @see \WPStaging\Framework\Notices\Notices::renderNotices
 */
?>
<div class="notice notice-error">
    <p>
        <strong><?php echo sprintf(esc_html__('WP STAGING - The table %s has no primary key index or missing autoincrement flag.', 'wp-staging'), esc_html($optionTable))?></strong>
        <br>
        <?php esc_html_e('This is a serious issue and needs to be fixed as soon as possible. The longer you wait, the harder it gets to repair it and can make your site inaccessible at worst. We recommend fixing it right now! This error has either been caused by a person with access to the database or by another plugin. You should not do any changes to your site until this is fixed.', 'wp-staging') ?>
    </p>
    <p><a href="https://wp-staging.com/docs/missing-primary-key-in-table-wp-options" target="_blank"><strong><?php esc_html_e('How to fix this.', 'wp-staging') ?></strong></a></p>
</div>
