<div class="notice notice-warning" id="wpstg-pro-crons-notice">
    <p>
        <strong><?php esc_html_e('WP STAGING', 'wp-staging'); ?></strong> <br>
        <?php esc_html_e('You downgraded WP Staging Pro to the WP Staging free version. To create a scheduled backup plan with WP Staging free, please click on the confirm button below.', 'wp-staging') ?> <br>
        <?php esc_html_e('This will delete all existing backup plans created with the pro version and you can setup one scheduled backup that runs once per day.', 'wp-staging') ?>
        <a href="javascript:void(0);" id="wpstg-link-clean-pro-crons" title="<?php esc_html_e('Confirm', 'wp-staging') ?>">
            <strong><?php esc_html_e('CONFIRM', 'wp-staging') ?></strong>
        </a>
    </p>
</div>
<script>
  jQuery(document).ready(function ($) {
    jQuery(document).on('click', '#wpstg-link-clean-pro-crons', function (e) {
      e.preventDefault();
      jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'wpstg_clean_pro_crons',
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce,
        },
        error: function error(xhr, textStatus, errorThrown) {
          console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
          console.log(textStatus);
          alert('Unknown error. Please get in contact with us to solve it support@wp-staging.com');
        },
        success: function success(data) {
          jQuery('#wpstg-pro-crons-notice').slideUp('fast');
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
