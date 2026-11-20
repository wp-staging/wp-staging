<?php

/**
 * The Job History page shell: the Pro listing on a Developer or Agency plan, the preview otherwise.
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Pro\License\Licensing;

$canUseJobHistory = defined('WPSTGPRO_VERSION')
    && class_exists(Licensing::class)
    && WPStaging::make(Licensing::class)->isAgencyOrDeveloperPlan();

$jobHistoryView = $canUseJobHistory
    ? WPSTG_VIEWS_DIR . 'pro/job-history/listing.php'
    : WPSTG_VIEWS_DIR . 'job-history/preview.php';

?>
<div class="wpstg_admin" id="wpstg-clonepage-wrapper">
    <?php

    require_once(WPSTG_VIEWS_DIR . (defined('WPSTGPRO_VERSION') ? 'pro/_main/header.php' : '_main/header.php'));

    $isActiveJobHistoryPage = true;
    require_once(WPSTG_VIEWS_DIR . '_main/main-navigation.php');
    ?>
    <div class="wpstg-loading-bar-container">
        <div class="wpstg-loading-bar"></div>
    </div>

    <div class="wpstg--tab--contents">
        <header class="wpstg-job-history-header wpstg-mb-6">
            <span class="wpstg-job-history-header-icon" aria-hidden="true">
                <?php WPStaging::make(Assets::class)->renderSvg('history'); ?>
            </span>
            <h1 class="wpstg-text-2xl wpstg-font-bold wpstg-tracking-tight wpstg-text-gray-900 dark:wpstg-text-gray-100 wpstg-mb-2 wpstg-m-0">
                <?php esc_html_e('Job History', 'wp-staging'); ?>
            </h1>
            <p class="wpstg-text-body wpstg-text-sm wpstg-m-0">
                <?php esc_html_e('Every backup, staging and sync operation on this site, with the user who started it and how it ended.', 'wp-staging'); ?>
            </p>
        </header>
        <?php require_once($jobHistoryView); ?>
    </div>
    <?php
    require_once(WPSTG_VIEWS_DIR . '_main/footer.php');
    ?>
</div>
