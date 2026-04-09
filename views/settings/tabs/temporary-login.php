<div class="wpstg-tab-temporary-logins">
    <div class="wpstg-provider-page-header">
        <h1 class="wpstg-text-2xl wpstg-font-semibold wpstg-text-slate-900 dark:wpstg-text-slate-100"><?php esc_html_e('Temporary Logins', 'wp-staging'); ?></h1>
        <p class="wpstg-mt-1 wpstg-text-sm wpstg-text-slate-600 dark:wpstg-text-slate-400"><?php esc_html_e('Create and share temporary login links that expire automatically. No password required.', 'wp-staging'); ?></p>
        <a href="https://wp-staging.com/docs/create-magic-login-links/" target="_blank" class="wpstg-btn wpstg-btn-md wpstg-btn-primary wpstg-mt-2">
            <?php esc_html_e('Learn more (Pro)', 'wp-staging'); ?>
        </a>
    </div>
    <p>
        <?php echo sprintf(esc_html__('This is a %s feature.', 'wp-staging'), '<a href="https://wp-staging.com/#pricing" target="_blank" rel="noopener">WP Staging Pro</a>') ?>
    </p>
    <div id="wpstg-temporary-logins-wrapper"></div>
</div>
