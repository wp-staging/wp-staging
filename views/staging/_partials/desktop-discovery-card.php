<?php

/**
 * Permanent WP STAGING Desktop discovery card, rendered beside the staging-site
 * list for the plans that do not already own the local CLI workflow.
 *
 * Developer and Agency get {@see src/views/staging/_partials/desktop-context-link.php} beside their local
 * action instead: they own that workflow, so an upgrade message would be wrong.
 *
 * @see src/views/staging/index.php
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Language\Language;
use WPStaging\Framework\Notices\CliIntegrationNotice;

if (WPStaging::make(CliIntegrationNotice::class)->isDeveloperOrHigherLicense()) {
    return;
}

$desktopUrl = Language::getDesktopUrl('staging_dashboard_desktop_card');
$upgradeUrl = Language::getUpgradeUrl('staging_dashboard_desktop_card', Language::getInstallSource());

$upgradeLink = sprintf(
    '<a href="%1$s" target="_blank" rel="noopener noreferrer" data-wpstg-upgrade-cta="staging_dashboard_desktop_card" class="wpstg-text-gray-600 hover:wpstg-text-gray-700 dark:wpstg-text-slate-300 dark:hover:wpstg-text-slate-200">%2$s</a>',
    esc_url($upgradeUrl),
    esc_html__('Developer', 'wp-staging')
);

$upgradeLinkAllowedHtml = [
    'a' => [
        'class'                  => [],
        'href'                   => [],
        'target'                 => [],
        'rel'                    => [],
        'data-wpstg-upgrade-cta' => [],
    ],
];
?>
<div class="wpstg-staging-layout__aside">
    <div class="wpstg-desktop-card wpstg-rounded-lg wpstg-border wpstg-border-solid wpstg-border-gray-200 dark:wpstg-border-slate-700 wpstg-bg-gray-50 dark:wpstg-bg-slate-800/60 wpstg-p-4">
        <div class="wpstg-flex wpstg-items-center wpstg-gap-2 wpstg-mb-2">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="wpstg-text-gray-500 dark:wpstg-text-slate-400">
                <rect x="3" y="4" width="18" height="12" rx="2.5"></rect>
                <path d="M2 20h20"></path>
            </svg>
            <span class="wpstg-inline-flex wpstg-items-center wpstg-rounded wpstg-border wpstg-border-solid wpstg-border-gray-300 dark:wpstg-border-slate-600 wpstg-px-1.5 wpstg-py-0.5 wpstg-text-[11px] wpstg-font-semibold wpstg-uppercase wpstg-tracking-wide wpstg-text-gray-600 dark:wpstg-text-slate-300">
                <?php esc_html_e('Free app', 'wp-staging'); ?>
            </span>
        </div>
        <h3 class="wpstg-mt-0 wpstg-mb-1 wpstg-text-sm wpstg-font-semibold wpstg-text-gray-800 dark:wpstg-text-slate-100">
            <?php esc_html_e('Work locally with WP STAGING Desktop', 'wp-staging'); ?>
        </h3>
        <p class="wpstg-mt-0 wpstg-mb-3 wpstg-text-[13px] wpstg-leading-relaxed wpstg-text-gray-600 dark:wpstg-text-slate-300">
            <?php esc_html_e('Create local WordPress sites and restore WP STAGING backups on your computer.', 'wp-staging'); ?>
        </p>
        <a class="wpstg-btn wpstg-btn-md wpstg-btn-outline"
           href="<?php echo esc_url($desktopUrl); ?>"
           target="_blank"
           rel="noopener noreferrer"
           data-wpstg-desktop-cta="staging_dashboard_desktop_card">
            <?php esc_html_e('Get Desktop Free', 'wp-staging'); ?>
            <svg class="wpstg-btn-icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="M15 3h6v6"></path>
                <path d="M10 14 21 3"></path>
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
            </svg>
        </a>
        <p class="wpstg-mt-2 wpstg-mb-0 wpstg-text-xs wpstg-text-gray-500 dark:wpstg-text-slate-400">
            <?php esc_html_e('macOS · Windows · Linux', 'wp-staging'); ?>
        </p>
        <p class="wpstg-mt-3 wpstg-mb-0 wpstg-pt-3 wpstg-border-0 wpstg-border-t wpstg-border-solid wpstg-border-gray-200 dark:wpstg-border-slate-700 wpstg-text-xs wpstg-leading-relaxed wpstg-text-gray-500 dark:wpstg-text-slate-400">
            <?php esc_html_e('Need an automated workflow?', 'wp-staging'); ?><br>
            <?php
            echo wp_kses(
                sprintf(
                    /* translators: %s: "Developer", linked to the pricing table. */
                    esc_html__('%s adds direct site pull and WP Staging CLI.', 'wp-staging'),
                    $upgradeLink
                ),
                $upgradeLinkAllowedHtml
            );
            ?>
        </p>
    </div>
</div>
