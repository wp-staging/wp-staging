<?php

/**
 * CLI Dock CTA Button - Compact button shown after banner is dismissed
 *
 * Rendered by JavaScript after the shrink-to-dock animation completes,
 * or server-side when the banner was previously dismissed.
 *
 * Structure: Vertical container wraps button + sublabel so the sublabel
 * aligns under the Local button only (not centered under entire row).
 *
 * License gating: Only Developer or Agency license holders can use this feature.
 * Free/Basic/Pro users see a "Pro" badge and the button opens upgrade modal
 * instead of performing the action.
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Notices\CliIntegrationNotice;

/** @var CliIntegrationNotice $cliNotice */
$cliNotice = WPStaging::make(CliIntegrationNotice::class);
$hasDeveloperLicense = $cliNotice->isDeveloperOrHigherLicense();

?>
<!--
    Server-rendered dock CTA: includes --visible class to appear immediately.
    This is intentional - animation only occurs during the JS dismiss flow.
    When users return to a page after previously dismissing, no animation is needed.
-->
<div class="wpstg-cli-dock-cta-wrapper wpstg-flex wpstg-flex-col wpstg-items-center">
    <button type="button"
        id="wpstg-cli-dock-cta-button"
        class="wpstg-btn wpstg-btn-md wpstg-btn-outline wpstg-cli-dock-cta wpstg-cli-dock-cta--visible wpstg-w-fit"
        <?php if ($hasDeveloperLicense) : ?>
        aria-label="<?php esc_attr_e('Create Local Site', 'wp-staging'); ?>"
        <?php else : ?>
        aria-label="<?php esc_attr_e('Create Local Site - Requires Developer license (opens upgrade dialog)', 'wp-staging'); ?>"
        aria-haspopup="dialog"
        aria-disabled="true"
        data-pro-feature="cli-local-site"
        <?php endif; ?>
    >
        <svg class="wpstg-btn-icon-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 10v6"></path>
            <path d="M9 13h6"></path>
            <path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"></path>
        </svg>
        <?php esc_html_e('Create Local Site', 'wp-staging'); ?>
        <?php if (!$hasDeveloperLicense) : ?>
        <span class="wpstg-badge wpstg-badge-pro"><?php esc_html_e('Pro', 'wp-staging'); ?></span>
        <?php endif; ?>
    </button>
    <span class="wpstg-text-xs wpstg-leading-[0.8] wpstg-text-gray-500 dark:wpstg-text-gray-400 wpstg-mt-1.5"><?php esc_html_e('Local via Docker', 'wp-staging'); ?></span>
</div>
