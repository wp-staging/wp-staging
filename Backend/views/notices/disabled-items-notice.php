<?php
/**
 * @var $this \WPStaging\Backend\Notices\Notices
 * @see \WPStaging\Backend\Notices\Notices::showNotices
 */
?>
<div class="notice wpstg-disabled-items-notice" style="border-left: 4px solid #ffba00; padding: 8px; padding-left: 16px; padding-top: 12px;">
    <strong style="margin-bottom: 10px;"><?php _e('WP STAGING Notes:', 'wp-staging'); ?></strong> <br/>
    <ol style="margin-left: 12px;">
        <li> <?php echo sprintf(__('Disabled the cache by setting the constant WP_CACHE to FALSE in the wp-config.php and excluding wp-content/cache. <a href="%s" target="_blank">How to activate caching</a>', 'wp-staging'), 'https://wp-staging.com/docs/how-to-activate-caching-on-staging-site/') ?></li>
        <?php if ($outgoingMailsDisabled) : ?>
        <li> <?php echo sprintf(__('Disabled outgoing emails. <a href="%s" target="_blank">How to activate mails</a>', 'wp-staging'), 'https://wp-staging.com/docs/how-to-activate-email-sending-on-the-staging-site/')?></li>
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
          action: 'wpstg_hide_disabled_items_notice'
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
