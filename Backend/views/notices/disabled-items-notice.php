<?php
/**
 * @var $this \WPStaging\Backend\Notices\Notices
 * @see \WPStaging\Backend\Notices\Notices::messages
 * @var bool  $outgoingMailsDisabled
 * @var bool  $freemiusOptionsCleared
 * @var array $excludedPlugins
 */
?>
<div class="notice notice-warning wpstg-disabled-items-notice">
    <p><strong><?php _e('WP STAGING - Notes:', 'wp-staging'); ?></strong></p>
    <ol style="margin-left: 12px;">
        <li> <?php echo sprintf(__('WP STAGING Disabled the cache by setting the constant <code>WP_CACHE</code> to <code>FALSE</code>in the file <code>wp-config.php</code>. <a href="%s" target="_blank"> You can revert this easily</a>', 'wp-staging'), 'https://wp-staging.com/docs/how-to-activate-caching-on-staging-site/') ?></li>
        <li> <?php echo sprintf(__('Permalinks are disabled. <a href="%s" target="_blank">How to activate permalinks</a>', 'wp-staging'), 'https://wp-staging.com/docs/activate-permalinks-staging-site/') ?></li>
        <?php if ($outgoingMailsDisabled) : ?>
        <li> <?php echo sprintf(__('Disabled outgoing emails. <a href="%s" target="_blank">How to activate email sending</a>', 'wp-staging'), 'https://wp-staging.com/docs/how-to-activate-email-sending-on-the-staging-site/')?></li>
        <?php endif; ?>
        <?php if ($freemiusOptionsCleared) : ?>
        <li>
            <?php _e('You may need to consider to reactivate your Freemius license to make sure that Freemius integration does not act slightly differently in your staging site.', 'wp-staging') ?>
          <a href="https://wp-staging.com/docs/freemius-integration-how-its-handled-by-wp-staging/"><?php _e('Read more here', 'wp-staging') ?>
        </li>
        <?php endif; ?>
        <?php if (count($excludedPlugins) > 0) : ?>
        <li>
            <?php _e('Excluded the following plugins:', 'wp-staging') ?>
            <ul style="margin-left: 0px; margin-top: 4px;">
                <?php foreach ($excludedPlugins as $excludedPlugin) : ?>
                    <li> <span style="font-size: 13px;">➜</span> <?php echo $excludedPlugin; ?></li>
                <?php endforeach; ?>
            </ul>
        </li>
        <?php endif; ?>
    </ol>
    <p>
        <a href="javascript:void(0);" class="wpstg_hide_disabled_items_notice" title="Close this message"
            style="font-weight:bold;">
            <?php _e('Close this message', 'wp-staging') ?>
        </a>
    </p>
</div>
<script>
  jQuery(document).ready(function ($) {
    jQuery(document).on('click', '.wpstg_hide_disabled_items_notice', function (e) {
      e.preventDefault();
      jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'wpstg_dismiss_notice',
          wpstg_notice: 'disabled_items'
        },
        error: function error(xhr, textStatus, errorThrown) {
          console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
          console.log(textStatus);
          alert('Unknown error. Please get in contact with us to solve it support@wp-staging.com');
        },
        success: function success(data) {
          jQuery('.wpstg-disabled-items-notice').slideUp('fast');
          return true;
        },
        statusCode: {
          404: function _() {
            alert('Something went wrong; can\'t find ajax request URL! Please get in contact with us to solve it support@wp-staging.com');
          },
          500: function _() {
            alert('Something went wrong; internal server error while processing the request! Please get in contact with us to solve it support@wp-staging.com');
          }
        }
      });
    });
  });
</script>
