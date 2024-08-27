<?php

/**
 * @see \WPStaging\Framework\Notices\Notices::renderNotices
 *
 * @var $this \WPStaging\Framework\Notices\Notices (Don't switch the order to avoid phpstan error)
 * @var bool  $outgoingMailsDisabled
 * @var bool  $freemiusOptionsCleared
 * @var bool  $isJetpackStagingModeActive
 * @var array $excludedPlugins
 * @var array $excludedFiles
 * @var array $excludedGoDaddyFiles
 */

use WPStaging\Framework\Notices\Notices;

if (empty(get_option('permalink_structure'))) {
    $permalinksMessage = sprintf(__('Post name permalinks are disabled. <a href="%s" target="_blank">How to activate permalinks</a>', 'wp-staging'), 'https://wp-staging.com/docs/activate-permalinks-staging-site/');
} elseif ($this->serverVars->isApache() &&  !file_exists(get_home_path() . '.htaccess')) {
    $permalinksMessage = __('.haccess is missing but required for permalinks! Go to Settings > Permalinks and click on Save Settings to create the .htaccess to make permalinks work!', 'wp-staging');
} elseif (!$this->serverVars->isApache()) {
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
        <?php if (is_array($excludedFiles) && count($excludedFiles) > 0) : ?>
        <li>
            <?php echo wp_kses_post(__('<a href="#" id="wpstg-excluded-files-link">These files</a> were excluded and not copied to the staging site:', 'wp-staging')); ?>
            <br>
            <ul id="wpstg-excluded-files-list" style="margin-left: 0px; margin-top: 4px;">
                <?php foreach ($excludedFiles as $excludedFile) : ?>
                    <li><span style="font-size: 13px;">➜</span> <?php echo esc_html($excludedFile); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php echo wp_kses_post(sprintf(__('You can use <a href="%s" target="_blank" rel="external nofollow">this filter</a> to change this.', 'wp-staging'), 'https://wp-staging.com/docs/actions-and-filters/#Exclude_Files')); ?>
        </li>
        <?php endif; ?>
        <?php if (is_array($excludedGoDaddyFiles) && count($excludedGoDaddyFiles) > 0) : ?>
        <li>
            <?php echo wp_kses_post(__('<a href="#" id="wpstg-excluded-godaddy-files-link">These GoDaddy files/folders</a> were excluded and not copied to the staging site:', 'wp-staging')); ?>
            <br>
            <ul id="wpstg-excluded-godaddy-files-list" style="margin-left: 0px; margin-top: 4px;">
                <?php foreach ($excludedGoDaddyFiles as $excludedGoDaddyFile) : ?>
                    <li><span style="font-size: 13px;">➜</span> <?php echo esc_html($excludedGoDaddyFile); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php echo esc_html__('Excluding these files/folders allows you to connect to this staging site and update WordPress without errors.', 'wp-staging'); ?>
        </li>
        <?php endif; ?>
    </ol>    
    <p>
      <?php Notices::renderNoticeDismissAction(
          $this->viewsNoticesPath,
          'disabled_items',
          '.wpstg_dismiss_disabled_items_notice',
          '.wpstg-disabled-items-notice'
      ) ?>
    </p>
    <script>
        jQuery(document).ready(function ($) {
            //display or hide excluded files list
            const el = $('#wpstg-excluded-files-list');
            el.hide();
            $('#wpstg-excluded-files-link').click(function(e) {
            e.preventDefault();
            if (el.is(':visible')) {
                el.hide('slow');
                return;
            }
            el.show('slow');
            });

            //display or hide excluded godaddy files list
            const goElement = $('#wpstg-excluded-godaddy-files-list');
            goElement.hide();
            $('#wpstg-excluded-godaddy-files-link').click(function(e) {
                e.preventDefault();
                if (goElement.is(':visible')) {
                    goElement.hide('slow');
                    return;
                }
                goElement.show('slow');
            });
        });
    </script>
</div>
