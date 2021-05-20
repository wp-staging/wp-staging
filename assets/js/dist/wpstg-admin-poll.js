(function () {
  'use strict';

  jQuery(document).ready(function ($) {
    $('.wpstg_hide_poll').on('click', function (e) {
      e.preventDefault();
      window.WPStaging.ajax({
        action: 'wpstg_hide_poll'
      }, function (response) {
        if (true === response) {
          $('.wpstg_poll').slideUp('fast');
          return true;
        } else {
          alert('Unexpected message received. This might mean the data was not saved ' + 'and you might see this message again.');
        }
      });
    });
  });

}());
//# sourceMappingURL=wpstg-admin-poll.js.map
