(function () {
  'use strict';

  jQuery(document).ready(function ($) {
    $('#wpstg-link-restore-settings').on('click', function (e) {
      e.preventDefault();
      jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'wpstg_restore_settings'
        },
        error: function error(xhr, textStatus, errorThrown) {
          alert('Unknown error. Please get in contact with us to solve it support@wp-staging.com');
        },
        success: function success(data) {
          jQuery('#wpstg-corrupt-settings-notice').slideUp('fast');
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

}());
//# sourceMappingURL=wpstg-admin-corrupt-settings.js.map
