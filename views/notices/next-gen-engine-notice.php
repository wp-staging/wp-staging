<?php

/**
 * @see \WPStaging\Framework\Notices\Notices::renderNotices
 *
 * @var object $this \WPStaging\Framework\Notices\Notices (Don't switch the order to avoid phpstan error)
 */

use WPStaging\Framework\Notices\Notices;

$articleUrl = 'https://wp-staging.com/next-gen-cloning-engine-known-issue/';

?>
<div class="notice notice-error wpstg-next-gen-engine-notice">
    <p><strong><?php esc_html_e('WP STAGING - Important: Next-Gen Engine staging sites may be corrupted', 'wp-staging'); ?></strong></p>
    <p>
        <strong><?php esc_html_e('Your live website is not affected by this issue.', 'wp-staging'); ?></strong>
        <?php esc_html_e('Only staging sites created with the Next-Gen (BETA) cloning engine can contain corrupted data.', 'wp-staging'); ?>
    </p>
    <p>
        <?php esc_html_e('The Next-Gen (BETA) cloning engine has been fixed in the current version. Staging sites created with affected earlier versions can still contain corrupted data.', 'wp-staging'); ?>
    </p>
    <p>
        <?php esc_html_e('If you created any staging site with the Next-Gen engine before this fix, do not rely on it: delete that staging site and create a new one with the current WP STAGING version.', 'wp-staging'); ?>
    </p>
    <p>
        <a href="<?php echo esc_url($articleUrl); ?>" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e('Read what happened and how to fix affected staging sites', 'wp-staging'); ?>
        </a>
    </p>
    <p>
        <?php Notices::renderNoticeDismissAction(
            $this->viewsNoticesPath,
            'next_gen_engine',
            '.wpstg_dismiss_next_gen_engine_notice',
            '.wpstg-next-gen-engine-notice'
        ) ?>
    </p>
</div>
