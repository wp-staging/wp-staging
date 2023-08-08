(function () {
  'use strict';

  var wpstgTimesWaited = 0;

  /*
  Let's wait for jQuery to be available to show the rating.
  We need it to dispatch AJAX requests.
   */
  var wpstgWaitForJQuery = setInterval(function () {
    if (wpstgTimesWaited > 100) {
      // Give up waiting.
      clearInterval(wpstgWaitForJQuery);
    }
    if (typeof jQuery != 'undefined') {
      wpstgRegisterRatingEvents();
      clearInterval(wpstgWaitForJQuery);
    }
    wpstgTimesWaited = wpstgTimesWaited + 1;
  }, 100);
  function wpstgRegisterRatingEvents() {
    // Show the rating once jQuery is loaded.
    jQuery('.wpstg_fivestar').show();

    /**
       * Dispatch the request to hide the notice after user clicks to rate the plugin.
       */
    jQuery(document).on('click', '#wpstg_clicked_deserved_it', function (e) {
      jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'wpstg_hide_rating',
          nonce: wpstg.nonce
        },
        error: function error(xhr, textStatus, errorThrown) {
          console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
          console.log(textStatus);
          alert('Unknown error. Please get in contact with us to solve it support@wp-staging.com');
        },
        success: function success(data) {
          jQuery('.wpstg_fivestar').slideUp('fast');
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
    jQuery('.wpstg_hide_rating').on('click', function (e) {
      e.preventDefault();
      jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'wpstg_hide_rating',
          nonce: wpstg.nonce
        },
        error: function error(xhr, textStatus, errorThrown) {
          console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
          console.log(textStatus);
          alert('Unknown error. Please get in contact with us to solve it support@wp-staging.com');
        },
        success: function success(data) {
          jQuery('.wpstg_fivestar').slideUp('fast');
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
    jQuery('.wpstg_rate_later').on('click', function (e) {
      e.preventDefault();
      jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'wpstg_hide_later',
          nonce: wpstg.nonce
        },
        error: function error(xhr, textStatus, errorThrown) {
          console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
          console.log(textStatus);
          alert('Unknown error. Please get in contact with us to solve it support@wp-staging.com');
        },
        success: function success(data) {
          jQuery('.wpstg_fivestar').slideUp('fast');
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
  }
  document.styleSheets[0].insertRule('@media only screen and (max-width:600px){.wpstg-welcome-box{display:block !important}.wpstg-welcome-text{padding-left:8px !important}}', '');

})();
//# sourceMappingURL=wpstg-admin-rating.js.map
