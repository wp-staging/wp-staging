<?php
/**
 * @var $cssClassSelectorNotice
 * @var $cssClassSelectorDismiss
 * @var $wpstgNotice
 * @see \WPStaging\WPStaging\Backend\Notices\Notices::renderNoticeDismissAction
 */
?>
<a href="javascript:void(0);"
    class="<?php echo substr($cssClassSelectorDismiss, 1) ?>"
    title="<?php _e('Close this message', 'wp-staging') ?>"
    style="font-weight:bold;">
    <?php _e('Close this message', 'wp-staging') ?>
</a>
<script>
  jQuery(document).ready(function ($) {
    jQuery(document).on('click', '<?php echo $cssClassSelectorDismiss ?>', function (e) {
      e.preventDefault();
      jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'wpstg_dismiss_notice',
          wpstg_notice: '<?php echo $wpstgNotice ?>',
        },
        error: function error(xhr, textStatus, errorThrown) {
          console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
          console.log(textStatus);
          alert('Unknown error. Please get in contact with us to solve it support@wp-staging.com');
        },
        success: function success(data) {
          jQuery('<?php echo $cssClassSelectorNotice ?>').slideUp('fast');
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
