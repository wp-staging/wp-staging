<?php
/**
 * @var $this \WPStaging\Backend\Notices\Notices
 * @see \WPStaging\Backend\Notices\Notices::messages
 */
?>
<div class="wpstg-hooks-outdated-notice notice notice-error">
    <p>
        <strong><?php _e('WP STAGING - Hooks Outdated.', 'wp-staging'); ?></strong> <br/>
        <?php echo sprintf(__('You are using an outdated version of the WP STAGING hooks plugin. The filters for <code>wpstg_clone_excl_folders</code> and <code>wpstg_clone_mu_excl_folders</code> have been changed. Download the latest version from <a href="%s" target="_blank">here</a> and adjust your filters.', 'wp-staging'), 'https://github.com/wp-staging/wp-staging-hooks'); ?>
    </p>
</div>
