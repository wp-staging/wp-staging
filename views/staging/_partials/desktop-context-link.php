<?php

/**
 * Permanent WP STAGING Desktop link beside the local-development action, for the
 * plans that already own that workflow and are owed no upgrade message.
 *
 * Beside the collapsed local action this reads as one short row — "CLI · Prefer
 * GUI? WP STAGING Desktop" — with the dock CTA's own copy of the CLI label
 * suppressed. Inside the banner, which has the room and has already named the
 * workflow, the longer "Prefer a GUI?" shows and the CLI half does not.
 *
 * The product name keeps its underline: it is the one part of the row that is
 * clickable, and colour alone must not be what says so.
 *
 * @see src/views/staging/_partials/create-staging-cta.php
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Language\Language;
use WPStaging\Framework\Notices\CliIntegrationNotice;

if (!WPStaging::make(CliIntegrationNotice::class)->isDeveloperOrHigherLicense()) {
    return;
}
?>
<p class="wpstg-desktop-meta !wpstg-flex wpstg-flex-wrap wpstg-items-center wpstg-gap-x-1.5 wpstg-m-0 wpstg-text-xs wpstg-leading-tight wpstg-text-gray-500 dark:wpstg-text-gray-400">
    <span class="wpstg-desktop-meta__cli">
        <?php esc_html_e('CLI', 'wp-staging'); ?>
        <span aria-hidden="true">&middot;</span>
    </span>
    <span class="wpstg-desktop-meta__gui-compact"><?php esc_html_e('Prefer GUI?', 'wp-staging'); ?></span>
    <span class="wpstg-desktop-meta__gui"><?php esc_html_e('Prefer a GUI?', 'wp-staging'); ?></span>
    <a class="wpstg-desktop-context-link wpstg-inline-flex wpstg-items-center wpstg-gap-1 wpstg-text-blue-600 hover:wpstg-text-blue-700 dark:wpstg-text-blue-400 dark:hover:wpstg-text-blue-300"
       href="<?php echo esc_url(Language::getDesktopUrl('staging_dashboard_local_action')); ?>"
       target="_blank"
       rel="noopener noreferrer"
       data-wpstg-desktop-cta="staging_dashboard_local_action">
        WP STAGING Desktop
        <svg class="wpstg-w-3 wpstg-h-3 wpstg-flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M15 3h6v6"></path>
            <path d="M10 14 21 3"></path>
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
        </svg>
    </a>
</p>
