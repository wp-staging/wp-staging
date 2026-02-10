<?php

use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Service\DirectoryScanner;
use WPStaging\Staging\Service\AbstractStagingSetup;
use WPStaging\Staging\Service\TableScanner;

/**
 * @var AbstractStagingSetup $stagingSetup
 * @var StagingSiteDto       $stagingSiteDto
 * @var DirectoryScanner     $directoryScanner
 * @var TableScanner         $tableScanner
 */
?>

<h1 class="wpstg-u-m-0 wpstg-mt-10px wpstg--swal2-title"><?php esc_html_e('Reset Staging Site', 'wp-staging'); ?></h1>
<p style="text-align: justify;"><?php esc_html_e('Do you really want to reset this staging site with the current state of the production site?', 'wp-staging'); ?></p>
<p style="font-size: 18px;" class="wpstg--red-warning"><?php esc_html_e('This will delete your modifications!', 'wp-staging'); ?></p>
<p class="wpstg-tables-selection-note" style="text-align: justify;">
    <b class="wpstg--red"><?php esc_html_e("Note: ", "wp-staging") ?></b>
    <?php esc_html_e("The original selection for tables and files have been preselected. You can adjust and verify them before starting the reset.", "wp-staging") ?>
</p>
<div class="wpstg-tabs-wrapper" style="text-align: left;">
    <a href="#" class="wpstg-tab-header active" data-id="#wpstg-setup-tables" data-collapsed="true">
        <span class="wpstg-tab-triangle"></span>
        <?php esc_html_e("Database Tables", "wp-staging") ?>
        <span id="wpstg-tables-count" class="wpstg-selection-preview"></span>
    </a>

    <fieldset class="wpstg-tab-section" id="wpstg-setup-tables">
        <?php $tableScanner->renderTablesSelection() ?>
    </fieldset>

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-setup-files" data-collapsed="true">
        <span class="wpstg-tab-triangle"></span>
        <?php esc_html_e("Files", "wp-staging") ?>
        <span id="wpstg-files-count" class="wpstg-selection-preview"></span>
    </a>

    <fieldset class="wpstg-tab-section" id="wpstg-setup-files">
        <?php $directoryScanner->renderFilesSelection() ?>
    </fieldset>
</div>
