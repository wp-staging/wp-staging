jQuery(document).ready(function ($) {
  jQuery(document).on('click', '.wpstg_hide_disabled_mail_notice', function (e) {
    e.preventDefault();
    jQuery.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'wpstg_hide_disabled_mail_notice'
      },
      error: function error(xhr, textStatus, errorThrown) {
        console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
        console.log(textStatus);
        alert('Unknown error. Please get in contact with us to solve it support@wp-staging.com');
      },
      success: function success(data) {
        jQuery('.wpstg-mails-notice').slideUp('fast');
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