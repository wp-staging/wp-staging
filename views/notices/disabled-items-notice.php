<?php

/**
 * @see \WPStaging\Framework\Notices\Notices::renderNotices
 *
 * @var object $this \WPStaging\Framework\Notices\Notices (Don't switch the order to avoid phpstan error)
 * @var bool  $outgoingMailsDisabled
 * @var bool  $freemiusOptionsCleared
 * @var bool  $isJetpackStagingModeActive
 * @var array $excludedPlugins
 * @var array $excludedFiles
 * @var array $excludedGoDaddyFiles
 */

use WPStaging\Framework\Notices\Notices;

?>
<style>
    .wpstg-disable-item-notice-ml-12 {
        margin-left: 12px;
    }

    .wpstg-disable-item-notice-excluded-files-ul {
        margin-left: 0;
        margin-top: 4px;
    }

    .wpstg-disable-item-notice-files-font {
        font-size: 13px;
    }
</style>
<?php
if (empty(get_option('permalink_structure'))) {
    $permalinksMessage = sprintf(
        esc_html__('Post name permalinks are disabled. %s', 'wp-staging'),
        '<a href="https://wp-staging.com/docs/activate-permalinks-staging-site/" target="_blank">' . esc_html__('How to activate permalinks', 'wp-staging') . '</a>'
    );
} elseif ($this->serverVars->isApache() &&  !file_exists(get_home_path() . '.htaccess')) {
    $permalinksMessage = esc_html__('.htaccess is missing but required for permalinks! Go to Settings > Permalinks and click on Save Settings to create the .htaccess to make permalinks work!', 'wp-staging');
} elseif (!$this->serverVars->isApache()) {
    $permalinksMessage = sprintf(
        esc_html__('Permalinks are active but may not work. Please %s to find out how to make them work.', 'wp-staging'),
        '<a href="https://wp-staging.com/docs/activate-permalinks-staging-site/" target="_blank">' . esc_html__('read this article', 'wp-staging') . '</a>'
    );
}

?>
<div class="notice notice-warning wpstg-disabled-items-notice">
    <p><strong><?php esc_html_e('WP STAGING - Notes:', 'wp-staging'); ?></strong></p>
    <ol class="wpstg-disable-item-notice-ml-12">
        <?php if (!defined('WP_CACHE') || WP_CACHE === false) :?>
        <li>
            <?php
            echo sprintf(
                esc_html__('WP STAGING Disabled the cache by setting the constant %1$s to %2$s in the file %3$s. %4$s', 'wp-staging'),
                '<code>WP_CACHE</code>',
                '<code>FALSE</code>',
                '<code>wp-config.php</code>',
                '<a href="https://wp-staging.com/docs/how-to-activate-caching-on-staging-site/" target="_blank">' . esc_html__('You can revert this easily', 'wp-staging') . '</a>'
            );
            ?>
        </li>
        <?php endif;?>
        <?php if (isset($permalinksMessage)) : ?>
        <li> <?php echo wp_kses_post($permalinksMessage); ?></li>
        <?php endif; ?>
        <?php if ($outgoingMailsDisabled) : ?>
        <li>
            <?php
            echo sprintf(
                esc_html__('Disabled outgoing emails. %s', 'wp-staging'),
                '<a href="https://wp-staging.com/docs/how-to-activate-email-sending-on-the-staging-site/" target="_blank">' . esc_html__('How to activate email sending', 'wp-staging') . '</a>'
            );
            ?>
        </li>
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
            <ul class="wpstg-disable-item-notice-excluded-files-ul">
                <?php foreach ($excludedPlugins as $excludedPlugin) : ?>
                    <li> <span class="wpstg-disable-item-notice-files-font">➜</span> <?php echo esc_html($excludedPlugin); ?></li>
                <?php endforeach; ?>
            </ul>
        </li>
        <?php endif; ?>
        <?php if (is_array($excludedFiles) && count($excludedFiles) > 0) : ?>
        <li>
            <?php
            echo sprintf(
                esc_html__('%s were excluded and not copied to the staging site:', 'wp-staging'),
                '<a href="#" id="wpstg-excluded-files-link">' . esc_html__('These files', 'wp-staging') . '</a>'
            );
            ?>
            <br>
            <ul id="wpstg-excluded-files-list" class="wpstg-disable-item-notice-excluded-files-ul">
                <?php foreach ($excludedFiles as $excludedFile) : ?>
                    <li><span class="wpstg-disable-item-notice-files-font">➜</span> <?php echo esc_html($excludedFile); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php
            echo sprintf(
                esc_html__('You can use %s to change this.', 'wp-staging'),
                '<a href="https://wp-staging.com/docs/actions-and-filters/#Exclude_Files" target="_blank">' . esc_html__('this filter', 'wp-staging') . '</a>'
            );
            ?>
        </li>
        <?php endif; ?>
        <?php if (is_array($excludedGoDaddyFiles) && count($excludedGoDaddyFiles) > 0) : ?>
        <li>
            <?php
            echo sprintf(
                esc_html__('%s were excluded and not copied to the staging site:', 'wp-staging'),
                '<a href="#" id="wpstg-excluded-godaddy-files-link">' . esc_html__('These GoDaddy files/folders', 'wp-staging') . '</a>'
            );
            ?>
            <br>
            <ul id="wpstg-excluded-godaddy-files-list" class="wpstg-disable-item-notice-excluded-files-ul">
                <?php foreach ($excludedGoDaddyFiles as $excludedGoDaddyFile) : ?>
                    <li><span class="wpstg-disable-item-notice-files-font">➜</span> <?php echo esc_html($excludedGoDaddyFile); ?></li>
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
