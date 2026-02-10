<?php

/**
 * This views is used in staging site feature. The inner content of this view is changes according to the step of the staging site feature.
 * @see src/views/clone/index.php
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Notices\CliIntegrationNotice;

// Show CLI integration notice (includes modal when banner is visible)
$cliNotice = WPStaging::make(CliIntegrationNotice::class);
$cliNotice->maybeShowCliNotice();

// When banner is dismissed but dock CTA should be shown, render modal separately
$cliNotice->maybeRenderCliModalForDockCta();

?>

<div id="wpstg-workflow">
    <div class="wpstg-staging-listing-skeleton wpstg-animate-pulse wpstg-py-4">
        <div class="wpstg-space-y-3">
            <div class="wpstg-h-4 wpstg-bg-gray-200 wpstg-rounded wpstg-w-1/4 dark:wpstg-bg-gray-700"></div>
            <div class="wpstg-h-3 wpstg-bg-gray-200 wpstg-rounded wpstg-w-full dark:wpstg-bg-gray-700"></div>
            <div class="wpstg-h-3 wpstg-bg-gray-200 wpstg-rounded wpstg-w-5/6 dark:wpstg-bg-gray-700"></div>
            <div class="wpstg-h-3 wpstg-bg-gray-200 wpstg-rounded wpstg-w-4/5 dark:wpstg-bg-gray-700"></div>
        </div>
    </div>
</div>
