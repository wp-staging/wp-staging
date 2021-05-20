(function () {
  'use strict';

  jQuery(document).ready(function ($) {
    /**
       * DEACTIVATION FEEDBACK FORM
       */
    // show overlay when clicked on "deactivate"
    wpstg_deactivate_link = $('.wp-admin.plugins-php tr[data-slug="wp-staging"] .row-actions .deactivate a');
    wpstg_deactivate_link_url = wpstg_deactivate_link.attr('href');
    wpstg_deactivate_link.on('click', function (e) {
      e.preventDefault(); // only show feedback form once per 30 days

      var c_value = wpstg_admin_get_cookie('wpstg_hide_feedback');

      if (c_value === undefined) {
        $('#wpstg-feedback-overlay').show();
      } else {
        // click on the link
        window.location.href = wpstg_deactivate_link_url;
      }
    }); // show text fields

    $('#wpstg-feedback-content input[type="radio"]').on('click', function () {
      // show text field if there is one
      $(this).parents('li').next('li').children('input[type="text"], textarea').show();
    }); // send form or close it

    $('#wpstg-feedback-content .button').on('click', function (e) {
      e.preventDefault(); // set cookie for 30 days

      var exdate = new Date();
      exdate.setSeconds(exdate.getSeconds() + 2592000);
      document.cookie = 'wpstg_hide_feedback=1; expires=' + exdate.toUTCString() + '; path=/';
      $('#wpstg-feedback-overlay').hide();

      if ('wpstg-feedback-submit' === this.id) {
        // Send form data
        $.ajax({
          type: 'POST',
          url: ajaxurl,
          dataType: 'json',
          data: {
            action: 'wpstg_send_feedback',
            data: $('#wpstg-feedback-content form').serialize()
          },
          complete: function complete(MLHttpRequest, textStatus, errorThrown) {
            // deactivate the plugin and close the popup
            $('#wpstg-feedback-overlay').remove();
            window.location.href = wpstg_deactivate_link_url;
          }
        });
      } else {
        $('#wpstg-feedback-overlay').remove();
        window.location.href = wpstg_deactivate_link_url;
      }
    }); // close form without doing anything

    $('.wpstg-feedback-not-deactivate').on('click', function (e) {
      $('#wpstg-feedback-overlay').hide();
    });

    function wpstg_admin_get_cookie(name) {
      var i;
      var x;
      var y;
      var wpstg_cookies = document.cookie.split(';');

      for (i = 0; i < wpstg_cookies.length; i++) {
        x = wpstg_cookies[i].substr(0, wpstg_cookies[i].indexOf('='));
        y = wpstg_cookies[i].substr(wpstg_cookies[i].indexOf('=') + 1);
        x = x.replace(/^\s+|\s+$/g, '');

        if (x === name) {
          return unescape(y);
        }
      }
    }
  });

}());
//# sourceMappingURL=wpstg-admin-plugins.js.map
