<div class="notice notice-error">
    <p>
        <strong><?php esc_html_e('Warning:', 'wp-staging'); ?></strong>
        <?php esc_html_e('WP STAGING can not clone this website because this site has no database table prefix. To proceed please', 'wp-staging')?>
        <?php echo sprintf(__('<a href="%s" target="_blank">fix it first.</a>', 'wp-staging'), 'https://wp-staging.com/wordpress-has-no-database-table-prefix-how-to-fix-it/') ?>
    </p>
</div>