(function () {
  'use strict';

  jQuery(document).ready(function ($) {
    $('.wpstg_hide_beta').on('click', function (e) {
      e.preventDefault();
      window.WPStaging.ajax({
        action: 'wpstg_hide_beta'
      }, function (response) {
        if (true === response) {
          $('.wpstg_beta_notice').slideUp('slow');
          return true;
        }

        alert('Unexpected message received. This might mean the data was not saved ' + 'and you might see this message again');
      });
    });
  });

}());
//# sourceMappingURL=wpstg-admin-beta.js.map
