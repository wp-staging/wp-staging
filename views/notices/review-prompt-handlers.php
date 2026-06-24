<?php

/**
 * Page-level delegated handlers for the success-based review prompt.
 *
 * The prompt lives in content that is added at runtime — the staging listing
 * (injected via innerHTML) and the backup completion modal (SweetAlert) — so an
 * inline <script> inside those fragments never executes. These handlers are
 * delegated from the document and the partial is included once on every page
 * that can show the prompt (the staging dashboard and the Backup & Migration
 * page). Both surfaces share the same wpstg_rating state, so dismissing or
 * snoozing in one silences the review everywhere.
 *
 * Selectors (shared by the strip and the modal):
 *   .wpstg-leave-review        -> opens the review page (default) + permanently dismisses
 *   .wpstg_rate_later          -> progressive snooze (14 / 30 / 180 days)
 *   .wpstg_hide_rating         -> permanently dismisses
 */

?>
<script>
  (function () {
    var PROMPT_SELECTOR = '.wpstg_fivestar';

    function dismiss(action, promptEl) {
      var body = new URLSearchParams();
      body.append('action', action);
      body.append('nonce', wpstg.nonce);

      fetch(ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body.toString()
      }).then(function () {
        if (promptEl) {
          promptEl.style.display = 'none';
        }
      });
    }

    document.addEventListener('click', function (event) {
      var rateLater = event.target.closest('.wpstg_rate_later');
      if (rateLater) {
        event.preventDefault();
        dismiss('wpstg_hide_later', rateLater.closest(PROMPT_SELECTOR));
        return;
      }

      var hideRating = event.target.closest('.wpstg_hide_rating');
      if (hideRating) {
        event.preventDefault();
        dismiss('wpstg_hide_rating', hideRating.closest(PROMPT_SELECTOR));
        return;
      }

      // Keep the default action so the review page opens in a new tab, then dismiss.
      // Class hook (not an id): the block is cloned from a hidden host into the
      // success modal, so an id would be duplicated in the DOM while the modal is open.
      var leaveReview = event.target.closest('.wpstg-leave-review');
      if (leaveReview) {
        dismiss('wpstg_hide_rating', leaveReview.closest(PROMPT_SELECTOR));
      }
    });
  })();
</script>
