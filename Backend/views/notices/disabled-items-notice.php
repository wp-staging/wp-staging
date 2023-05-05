<?php

/**
 * @var $this \WPStaging\Framework\Notices\Notices
 * @var $viewsNoticesPath
 * @see \WPStaging\Framework\Notices\Notices::renderNotices
 * @var bool  $outgoingMailsDisabled
 * @var bool  $freemiusOptionsCleared
 * @var bool  $isJetpackStagingModeActive
 * @var array $excludedPlugins
 */

use WPStaging\Framework\Notices\Notices;
use WPStaging\Framework\Utils\ServerVars;
use WPStaging\Core\WPStaging;

if (empty(get_option('permalink_structure'))) {
    $permalinksMessage = sprintf(__('Post name permalinks are disabled. <a href="%s" target="_blank">How to activate permalinks</a>', 'wp-staging'), 'https://wp-staging.com/docs/activate-permalinks-staging-site/');
} elseif (WPStaging::make(ServerVars::class)->isApache() &&  !file_exists(get_home_path() . '.htaccess')) {
    $permalinksMessage = __('.haccess is missing but required for permalinks! Go to Settings > Permalinks and click on Save Settings to create the .htaccess to make permalinks work!', 'wp-staging');
} elseif (!WPStaging::make(ServerVars::class)->isApache()) {
    $permalinksMessage = sprintf(__('Permalinks are active but may not work. Please <a href="%s" target="_blank">read this article</a> to find out how to make them work.', 'wp-staging'), 'https://wp-staging.com/docs/activate-permalinks-staging-site/');
}

?>
<div class="notice notice-warning wpstg-disabled-items-notice">
    <p><strong><?php esc_html_e('WP STAGING - Notes:', 'wp-staging'); ?></strong></p>
    <ol style="margin-left: 12px;">
        <li> <?php echo sprintf(__('WP STAGING Disabled the cache by setting the constant <code>WP_CACHE</code> to <code>FALSE</code>in the file <code>wp-config.php</code>. <a href="%s" target="_blank"> You can revert this easily</a>', 'wp-staging'), 'https://wp-staging.com/docs/how-to-activate-caching-on-staging-site/') ?></li>
        <?php if (isset($permalinksMessage)) : ?>
        <li> <?php echo wp_kses_post($permalinksMessage); ?></li>
        <?php endif; ?>
        <?php if ($outgoingMailsDisabled) : ?>
        <li> <?php echo sprintf(__('Disabled outgoing emails. <a href="%s" target="_blank">How to activate email sending</a>', 'wp-staging'), 'https://wp-staging.com/docs/how-to-activate-email-sending-on-the-staging-site/')?></li>
        <?php endif; ?>
        <?php if ($freemiusOptionsCleared) : ?>
        <li>
            <?php esc_html_e('You may need to consider to reactivate your Freemius license to make sure that Freemius integration does not act slightly differently in your staging site.', 'wp-staging') ?>
          <a href="https://wp-staging.com/docs/freemius-integration-how-its-handled-by-wp-staging/"><?php esc_html_e('Read more here', 'wp-staging') ?>
        </li>
        <?php endif; ?>
        <?php if ($isJetpackStagingModeActive) : ?>
        <li>
            <?php esc_html_e('Jetpack constant JETPACK_STAGING_MODE is enabled on this staging site.', 'wp-staging') ?>
            <a href="https://wp-staging.com/docs/make-jetpack-working-on-staging-site/"><?php esc_html_e('Read more here', 'wp-staging') ?>
        </li>
        <?php endif; ?>
        <?php if (count($excludedPlugins) > 0) : ?>
        <li>
            <?php esc_html_e('Excluded the following plugins:', 'wp-staging') ?>
            <ul style="margin-left: 0px; margin-top: 4px;">
                <?php foreach ($excludedPlugins as $excludedPlugin) : ?>
                    <li> <span style="font-size: 13px;">➜</span> <?php echo esc_html($excludedPlugin); ?></li>
                <?php endforeach; ?>
            </ul>
        </li>
        <?php endif; ?>
    </ol>    
    <p>
      <?php Notices::renderNoticeDismissAction(
          $viewsNoticesPath,
          'disabled_items',
          '.wpstg_dismiss_disabled_items_notice',
          '.wpstg-disabled-items-notice'
      ) ?>
    </p>
</div>
