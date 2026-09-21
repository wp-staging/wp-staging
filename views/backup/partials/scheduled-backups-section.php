<?php

/**
 * Collapsible scheduled-backups section. JS-populated via AJAX; hidden until schedules exist.
 */

?>
<div id="wpstg-scheduled-backups-section" class="wpstg-scheduled-backups-section wpstg-mb-4 wpstg-rounded-md" style="display:none;" aria-label="<?php esc_attr_e('Scheduled Backups', 'wp-staging'); ?>">

    <div id="wpstg-scheduled-backups-header" class="wpstg-scheduled-backups-header" role="button" tabindex="0"
         aria-expanded="false"
         aria-controls="wpstg-scheduled-backups-table-wrap">

        <!-- Top row: title + count badge + site time + chevron -->
        <div class="wpstg-schedule-header-top">

            <span class="wpstg-schedule-section-title wpstg-font-semibold wpstg-text-[13px] wpstg-text-gray-900 dark:wpstg-text-gray-100">
                <?php esc_html_e('Backup Jobs', 'wp-staging'); ?>
            </span>

            <span id="wpstg-schedule-badge-total" class="wpstg-badge wpstg-badge-gray wpstg-schedule-count-badge wpstg-font-semibold"></span>

            <span class="wpstg--tooltip wpstg-schedule-header-time wpstg-inline-flex wpstg-items-center wpstg-gap-1 wpstg-text-xs wpstg-whitespace-nowrap wpstg-text-gray-500 dark:wpstg-text-gray-400">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span id="wpstg-schedule-current-time" class="wpstg-font-semibold wpstg-text-gray-900 dark:wpstg-text-gray-100">--</span>
                <span id="wpstg-schedule-utc-label"></span>
                <div class="wpstg--tooltiptext"><?php esc_html_e('Backup jobs use your WordPress site timezone.', 'wp-staging'); ?></div>
            </span>

            <span class="wpstg-schedule-chevron wpstg-inline-flex wpstg-items-center wpstg-shrink-0" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </span>

        </div><!-- .wpstg-schedule-header-top -->

        <!-- Summary bar: next run | latest run | health badge -->
        <div class="wpstg-schedule-header-summary">

            <span id="wpstg-schedule-summary-next" class="wpstg-inline-flex wpstg-items-center wpstg-gap-1" style="display:none !important;">
                <?php esc_html_e('Next run:', 'wp-staging'); ?>
                <span id="wpstg-schedule-summary-next-value" class="wpstg-font-semibold wpstg-text-gray-700 dark:wpstg-text-gray-300"></span>
            </span>

            <span id="wpstg-schedule-summary-last" class="wpstg-inline-flex wpstg-items-center wpstg-gap-1" style="display:none !important;">
                <?php esc_html_e('Latest run:', 'wp-staging'); ?>
                <span id="wpstg-schedule-summary-last-value" class="wpstg-font-semibold wpstg-text-gray-700 dark:wpstg-text-gray-300"></span>
            </span>

            <span id="wpstg-schedule-health-badge" class="wpstg-badge" style="display:none;" aria-live="polite"></span>

        </div><!-- .wpstg-schedule-header-summary -->

    </div><!-- #wpstg-scheduled-backups-header -->

    <div id="wpstg-scheduled-backups-table-wrap" class="wpstg-scheduled-backups-table-wrap wpstg-overflow-hidden wpstg-max-h-0 wpstg-opacity-0" aria-hidden="true">

        <!-- Card rows: populated by JS -->
        <div id="wpstg-schedule-cards" class="wpstg-schedule-cards"></div>

    </div><!-- #wpstg-scheduled-backups-table-wrap -->

</div><!-- #wpstg-scheduled-backups-section -->
