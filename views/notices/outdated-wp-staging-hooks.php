<?php
/**
 * @var $this \WPStaging\Framework\Notices\Notices
 * @see \WPStaging\Framework\Notices\Notices::renderNotices
 */
?>
<div class="wpstg-hooks-outdated-notice notice notice-error">
    <p>
        <strong><?php esc_html_e('WP STAGING - Hooks Outdated.', 'wp-staging'); ?></strong> <br/>
        <?php echo sprintf(__('You are using an outdated version of the WP STAGING hooks plugin. The filters for <code>wpstg_clone_excl_folders</code> and <code>wpstg_clone_mu_excl_folders</code> have been changed. Download the latest version from <a href="%s" target="_blank">here</a> and adjust your filters.', 'wp-staging'), 'https://github.com/wp-staging/wp-staging-hooks'); ?>
    </p>
</div>
