<?php

/**
 * Hidden host for the backup completion modal's review block.
 *
 * wpstg-backup.js copies #wpstg-backup-success-content into the success modal
 * (Free only) and reveals .wpstg-rate-us once a backup completes. The review
 * block itself (and its eligibility gating) lives in the shared partial so the
 * staging and backup success modals stay identical.
 *
 * @see views/notices/review-prompt-modal.php
 */

?>
<div id="wpstg-backup-success-content" style="display:none;">
    <?php include WPSTG_VIEWS_DIR . 'notices/review-prompt-modal.php'; ?>
</div>
