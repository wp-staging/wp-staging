<?php

use WPStaging\Staging\Dto\DirectoryNodeDto;
use WPStaging\Staging\Service\DirectoryScanner;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Filesystem\Filters\ExcludeFilter;
use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Service\AbstractStagingSetup;

/**
 * @var DirectoryScanner $scanner
 * @var AbstractStagingSetup $stagingSetup
 * @var StagingSiteDto $stagingSiteDto
 * @var DirectoryNodeDto[] $directories
 * @var ExcludeFilter $excludeUtils
 * @see WPStaging\Staging\Service\DirectoryScanner::renderFilesSelection
 */

?>
<p>
<strong><?php esc_html_e("Select Folders to Copy", "wp-staging") ?></strong>
    <br>
<?php esc_html_e("Click on a folder name to expand it.", "wp-staging") ?>
</p>
<div id="wpstg-directories-listing" data-existing-excludes="<?php echo ($scanner->isUpdateOrResetJob() && !empty($stagingSiteDto->getExcludedDirectories())) ? esc_attr(implode(',', $stagingSiteDto->getExcludedDirectories())) : '' ?>">
    <div class="wpstg-mb-8px">
        <button type="button" class="wpstg-unselect-dirs button"><?php esc_html_e('Unselect All', 'wp-staging'); ?></button>
        <button type="button" class="wpstg-select-dirs-default button"> <?php esc_html_e('Select Default', 'wp-staging'); ?></button>
    </div>
    <?php echo $scanner->directoryListing($directories); // phpcs:ignore ?>
</div>
<!-- Exclusion Rules Table -->
<div class="wpstg-excluded-filters-container" id="wpstg-exclude-filters-container">
    <table>
        <tbody>
            <?php
            $hasRules = false;
            if ($scanner->isUpdateOrResetJob()) :
                foreach ($options->currentClone['excludeSizeRules'] as $rule) :
                    $hasRules = true;
                    echo $excludeUtils->renderSizeExclude($rule); // phpcs:ignore
                endforeach;
                foreach ($options->currentClone['excludeGlobRules'] as $rule) :
                    $hasRules = true;
                    echo $excludeUtils->renderGlobExclude($rule); // phpcs:ignore
                endforeach;
            endif; ?>
        </tbody>
    </table>
    <p <?php echo !$hasRules ? 'style="display: none;"' : '' ?> class="wpstg-has-exclude-rules"><b><?php esc_html_e('Note', 'wp-staging'); ?>:</b> <?php esc_html_e('These rules will not affect wp-admin and wp-includes directories!', 'wp-staging')?></p>
    <div class="wpstg-exclude-filters-foot">
        <div class="wpstg-dropdown wpstg-exclude-filter-dropdown" id="wpstg-exclude-filter-dropdown">
            <button class="wpstg-dropdown-toggler wpstg-button--secondary wpstg-button--blue">
                <?php esc_html_e("Add Exclude Rule + ", "wp-staging"); ?>
            </button>
            <div class="wpstg-dropdown-menu wpstg-menu-dropup" id="wpstg-exclude-filter-dropdown-menu">
                <button class="wpstg-dropdown-action wpstg-file-size-rule"><?php esc_html_e('File Size', 'wp-staging'); ?></button>
                <button class="wpstg-dropdown-action wpstg-file-ext-rule"><?php esc_html_e('File Extension', 'wp-staging'); ?></button>
                <button class="wpstg-dropdown-action wpstg-file-name-rule"><?php esc_html_e('File Name', 'wp-staging'); ?></button>
                <button class="wpstg-dropdown-action wpstg-dir-name-rule"><?php esc_html_e('Folder Name', 'wp-staging'); ?></button>
            </div>
        </div>
        <button <?php echo !$hasRules ? 'style="display: none;"' : '' ?> class="wpstg-ml-8px wpstg-button--secondary wpstg-clear-all-rules wpstg-has-exclude-rules wpstg-button--red">
            <?php esc_html_e("Clear All Rules", "wp-staging"); ?>
        </button>
    </div>
</div>
<?php
if ($stagingSetup->isUpdateJob()) {
    echo '<p>' . esc_html__("Applying an exclude rule will not effect existing files on the staging site if you don't clean up the wp-content folder before updating. Existing files will not be deleted afterwards automatically!", 'wp-staging') . '</p>';
}
?>
<!-- End Exclusion Rules Table -->
        
<!-- Templates for exclusion filters. These will never be rendered until added to exclusion rule table -->
<?php unset($rule); ?>
<template id="wpstg-file-ext-exclude-filter-template">
    <?php require(WPSTG_VIEWS_DIR . 'exclude-filters/file-ext-exclude-filter.php'); ?>
</template>
<template id="wpstg-file-size-exclude-filter-template">
    <?php require(WPSTG_VIEWS_DIR . 'exclude-filters/file-size-exclude-filter.php'); ?>
</template>
<template id="wpstg-file-name-exclude-filter-template">
    <?php require(WPSTG_VIEWS_DIR . 'exclude-filters/file-name-exclude-filter.php'); ?>
</template>
<template id="wpstg-dir-name-exclude-filter-template">
    <?php require(WPSTG_VIEWS_DIR . 'exclude-filters/dir-name-exclude-filter.php'); ?>
</template>
<!-- End - Templates for exclusion filters -->

<?php if (defined('WPSTG_ALLOW_EXTRA_DIRECTORIES') && constant('WPSTG_ALLOW_EXTRA_DIRECTORIES')) { ?>
<h4 style="margin:10px 0 10px 0">
    <?php echo esc_html__("Extra directories to copy", "wp-staging") ?>
</h4>

<textarea id="wpstg_extraDirectories" name="wpstg_extraDirectories" style="width:100%;height:100px;"></textarea>
<p>
    <span>
        <?php
        echo sprintf(
            Escape::escapeHtml(__("Enter one folder path per line.<br>Folders must be relative to the path: %s", 'wp-staging')),
            esc_html($stagingSetup->getRoot())
        );
        ?>
    </span>
</p>
<?php } ?>

<?php if ($scanner->isUpdateOrResetJob()) : ?>
<p>
    <span>
        <?php echo esc_html__("All files will be copied to: ", "wp-staging") . "<code>" . esc_html($stagingSiteDto->getDirectoryName()) . "</code>"; ?>
    </span>
</p>
<?php endif; ?>
