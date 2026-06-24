<?php

/**
 * Shared, success-based review block for the backup/staging completion modals.
 *
 * Renders nothing unless this is the Free version and the shared review state
 * still allows asking (not permanently dismissed, not snoozed). The caller wraps
 * it in a hidden container whose JS injects it into the success modal and reveals
 * it via the `.show` class, so the ask is always tied to a completed operation
 * and never appears as loose text on the dashboard.
 *
 * Actions reuse the shared selectors wired by review-prompt-handlers.php and the
 * same wpstg_rating state, so a "Maybe Later" / "Don't Ask Again" here silences
 * the review across both the staging and backup flows.
 *
 * @see \WPStaging\Basic\Notices\RatingNotice::isReviewPromptEligible
 */

use WPStaging\Basic\Notices\RatingNotice;
use WPStaging\Core\WPStaging;

if (WPStaging::isPro() || !WPStaging::make(RatingNotice::class)->isReviewPromptEligible()) {
    return;
}
?>
<div class="wpstg-rate-us wpstg_fivestar">
    <div class="wpstg-rate-us-inner">
        <p class="wpstg-rate-us-title">
            <strong><?php esc_html_e('WP STAGING helped you create a safer workflow?', 'wp-staging'); ?></strong>
        </p>
        <p class="wpstg-rate-us-body">
            <?php esc_html_e('If WP STAGING saved you time, an honest review on WordPress.org would help us a lot.', 'wp-staging'); ?>
        </p>
        <div class="wpstg-rate-us-action">
            <a href="https://wordpress.org/support/plugin/wp-staging/reviews/#new-post"
               target="_blank"
               rel="noopener noreferrer"
               class="wpstg-button wpstg-blue-primary wpstg-leave-review"><?php esc_html_e('Leave a Review', 'wp-staging'); ?></a>
            <a href="javascript:void(0);" class="wpstg_rate_later wpstg-rate-us-secondary"><?php esc_html_e('Maybe Later', 'wp-staging'); ?></a>
            <a href="javascript:void(0);" class="wpstg_hide_rating wpstg-rate-us-secondary"><?php esc_html_e('Don\'t Ask Again', 'wp-staging'); ?></a>
        </div>
    </div>
</div>
