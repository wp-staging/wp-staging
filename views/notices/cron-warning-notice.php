<?php

/**
 * Cron Warning Notice - Compact banner for WP-Cron status warnings
 *
 * Uses UI primitives for buttons and badges, with minimal custom layout styles.
 *
 * @var \WPStaging\Backup\BackupScheduler $backupScheduler
 * @var bool $cronStatus
 * @var string $cronMessage
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\ServerVars;

// Don't show if no cron issues
if ($cronMessage === '') {
    return;
}

$overdueCount = $backupScheduler->getOverdueCronJobsCount();
$hasOverdue = $backupScheduler->hasOverdueCronJobs();
$isWpCronDisabled = $backupScheduler->isWpCronDisabled();
$isLitespeed = WPStaging::make(ServerVars::class)->isLitespeed();
$isPro = WPStaging::isPro();

// Help article URL
$helpUrl = $isPro
    ? 'https://wp-staging.com/docs/wp-cron-is-not-working-correctly/'
    : 'https://wordpress.org/support/plugin/wp-staging/';
?>

<div class="wpstg-cron-banner" id="wpstg-cron-warning-notice">
    <!-- Collapsed row: icon + message + badge + buttons -->
    <div class="wpstg-cron-banner-row">
        <!-- Icon Box - uses UI primitive -->
        <div class="wpstg-icon-box wpstg-icon-box-amber wpstg-cron-banner-icon-size">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>

        <!-- Message + Badge -->
        <div class="wpstg-cron-banner-message">
            <span class="wpstg-cron-banner-text"><?php esc_html_e('Scheduled backups may not run.', 'wp-staging'); ?></span>
            <?php if ($hasOverdue && $overdueCount > 0) : ?>
                <span class="wpstg-badge wpstg-cron-banner-badge"><?php echo esc_html((string)$overdueCount); ?> <?php esc_html_e('overdue', 'wp-staging'); ?></span>
            <?php endif; ?>
        </div>

        <!-- Actions - uses UI primitive buttons -->
        <div class="wpstg-cron-banner-actions">
            <a href="<?php echo esc_url($helpUrl); ?>" target="_blank" rel="noopener" class="wpstg-btn wpstg-btn-sm wpstg-btn-warning">
                <?php esc_html_e('Fix WP-Cron', 'wp-staging'); ?>
                <svg class="wpstg-btn-icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
            </a>
            <button
                type="button"
                class="wpstg-btn wpstg-btn-sm wpstg-btn-warning-outline wpstg-cron-banner-toggle"
                aria-expanded="false"
                aria-controls="wpstg-cron-banner-details"
            >
                <?php esc_html_e('Details', 'wp-staging'); ?>
                <svg class="wpstg-cron-banner-caret wpstg-btn-icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
        </div>
    </div>

    <!-- Expandable details panel -->
    <div id="wpstg-cron-banner-details" class="wpstg-cron-banner-details" hidden>
        <div class="wpstg-cron-banner-details-content">
            <?php if ($isWpCronDisabled) : ?>
                <p class="wpstg-cron-banner-cause">
                    <?php esc_html_e('Detected', 'wp-staging'); ?>
                    <code class="wpstg-code-chip">DISABLE_WP_CRON=true</code>
                    <?php esc_html_e('in wp-config.php.', 'wp-staging'); ?>
                </p>
            <?php endif; ?>
            <p class="wpstg-cron-banner-guidance">
                <?php esc_html_e('Enable WP-Cron or configure a server cron job.', 'wp-staging'); ?>
                <?php if ($hasOverdue) : ?>
                    <?php esc_html_e('This could also indicate a development site with no visitors.', 'wp-staging'); ?>
                <?php endif; ?>
            </p>
            <?php if ($isLitespeed) : ?>
                <p class="wpstg-cron-banner-litespeed">
                    <?php esc_html_e('LiteSpeed server detected.', 'wp-staging'); ?>
                    <a href="https://wp-staging.com/docs/scheduled-backups-do-not-work-hosting-company-uses-the-litespeed-webserver-fix-wp-cron/" target="_blank" rel="noopener">
                        <?php esc_html_e('Learn more', 'wp-staging'); ?>
                        <svg class="wpstg-btn-icon-sm wpstg-inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var toggle = document.querySelector('.wpstg-cron-banner-toggle');
    if (!toggle) return;

    toggle.onclick = function() {
        var details = document.getElementById('wpstg-cron-banner-details');
        var isExpanded = this.getAttribute('aria-expanded') === 'true';

        this.setAttribute('aria-expanded', !isExpanded);
        details.hidden = isExpanded;
        this.classList.toggle('wpstg-expanded', !isExpanded);
    };
})();
</script>
