<?php
/**
 * @var $cssClassSelectorNotice
 * @var $cssClassSelectorDismiss
 * @var $wpstgNotice
 * @see \WPStaging\WPStaging\Framework\Notices\Notices::renderNoticeDismissAction
 */
?>
<a href="javascript:void(0);"
    class="<?php echo esc_attr(substr($cssClassSelectorDismiss, 1)) ?>"
    title="<?php esc_html_e('Close this message', 'wp-staging') ?>"
    style="font-weight:bold;">
    <?php esc_html_e('Close this message', 'wp-staging') ?>
</a>
<script>
  jQuery(document).ready(function ($) {
    jQuery(document).on('click', '<?php echo esc_attr($cssClassSelectorDismiss) ?>', function (e) {
      e.preventDefault();
      jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'wpstg_dismiss_notice',
          wpstg_notice: '<?php echo esc_html($wpstgNotice) ?>',
        },
        error: function error(xhr, textStatus, errorThrown) {
          console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
          console.log(textStatus);
          alert('Unknown error. Please get in contact with us to solve it support@wp-staging.com');
        },
        success: function success(data) {
          jQuery('<?php echo esc_attr($cssClassSelectorNotice) ?>').slideUp('fast');
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
