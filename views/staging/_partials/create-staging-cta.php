<?php

/**
 * Shared "Create Staging Site" call-to-action: the create button paired with the CLI dock slot.
 * Included by both the staging listing and the empty state so the CTA renders consistently.
 *
 * @var bool $error True when the staging site option is corrupted; disables the create button.
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Notices\CliIntegrationNotice;

/** @var CliIntegrationNotice $cliNotice */
$cliNotice = WPStaging::make(CliIntegrationNotice::class);
?>
<div class="wpstg-staging-actions !wpstg-inline-flex">
    <button class="wpstg-btn wpstg-btn-md wpstg-btn-primary wpstg-px-3 wpstg-new-staging-btn"
        <?php echo $error ? 'disabled' : '' ?>
    >
        <svg class="wpstg-btn-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <?php echo esc_html__('Create Staging Site', 'wp-staging'); ?>
    </button>
    <!-- CLI Dock Slot - populated after banner collapse or server-side when banner was dismissed -->
    <div class="wpstg-cli-dock-slot"><?php $cliNotice->maybeRenderDockCta(); ?></div>
</div>
