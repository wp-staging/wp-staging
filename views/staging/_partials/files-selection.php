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
 * @var ExcludeFilter $excludeFilters
 * @var bool $showFileDestination
 * @see WPStaging\Staging\Service\DirectoryScanner::renderFilesSelection
 */

$showFileDestination = isset($showFileDestination) ? $showFileDestination : true;
$hasRules = $scanner->isUpdateOrResetJob() && (!empty($stagingSiteDto->getExcludeSizeRules()) || !empty($stagingSiteDto->getExcludeGlobRules()));
// The redesigned reset modal reuses the shared update-style selection chrome
// (.wpstg-update-selection--files in update.scss restyles the tree and hides the
// exclude-rules panel), so reset, update and create all render the same panel.
$wrapperClass          = 'wpstg-selection-panel';
$directoryListingClass = '';
$directoryHeaderClass  = 'wpstg-selection-header';
$directoryListClass    = 'wpstg-directory-selection-list wpstg-selection-list wpstg-selection-list-padded';
$excludeRulesClass     = 'wpstg-excluded-filters-container wpstg-selection-section wpstg-box-border wpstg-w-full !wpstg-my-0';
$extraDirectoriesClass = 'wpstg-selection-section';
$destinationClass      = 'wpstg-selection-section wpstg-m-0 wpstg-text-sm wpstg-leading-5 wpstg-text-[#536579] dark:wpstg-text-slate-400';
$updateExcludeNoticeClass = 'wpstg-selection-section wpstg-m-0 wpstg-text-sm wpstg-leading-5 wpstg-text-[#536579] dark:wpstg-text-slate-400';
$showFileSizeExcludeLimit = $stagingSetup->isUpdateJob() || $stagingSetup->isResetJob() || $stagingSetup->isPushJob();
?>
<div class="<?php echo esc_attr($wrapperClass); ?>">
    <div id="wpstg-directories-listing" class="<?php echo esc_attr($directoryListingClass); ?>" data-existing-excludes="<?php echo ($scanner->isUpdateOrResetJob() && !empty($stagingSiteDto->getExcludedDirectories())) ? esc_attr(implode(',', $stagingSiteDto->getExcludedDirectories())) : '' ?>">
        <div class="<?php echo esc_attr($directoryHeaderClass); ?>">
            <div class="wpstg-min-w-0">
                <strong class="wpstg-selection-title"><?php esc_html_e("Select Folders to Copy", "wp-staging") ?></strong>
                <p class="wpstg-selection-description"><?php esc_html_e("Click a folder name to expand it.", "wp-staging") ?></p>
            </div>

            <div class="wpstg-selection-actions">
                <button type="button" class="wpstg-unselect-dirs wpstg-btn wpstg-btn-sm wpstg-btn-secondary !wpstg-rounded"><?php esc_html_e('Deselect all', 'wp-staging'); ?></button>
                <button type="button" class="wpstg-select-dirs-default wpstg-btn wpstg-btn-sm wpstg-btn-secondary !wpstg-rounded"><?php esc_html_e('Restore defaults', 'wp-staging'); ?></button>
            </div>
        </div>

        <div class="<?php echo esc_attr($directoryListClass); ?>">
            <?php echo $scanner->directoryListing($directories); // phpcs:ignore ?>
        </div>
    </div>

    <!-- Exclusion Rules Table -->
    <div class="<?php echo esc_attr($excludeRulesClass); ?>" id="wpstg-exclude-filters-container">
        <div class="wpstg-flex wpstg-w-full wpstg-flex-wrap wpstg-items-center wpstg-justify-between wpstg-gap-3">
            <strong class="wpstg-selection-title"><?php esc_html_e("Exclude Rules", "wp-staging") ?></strong>

            <div class="wpstg-ml-auto wpstg-flex wpstg-max-w-full wpstg-flex-wrap wpstg-items-center wpstg-justify-end wpstg-gap-2">
                <button type="button" <?php echo !$hasRules ? 'style="display: none;"' : '' ?> class="wpstg-clear-all-rules wpstg-has-exclude-rules wpstg-btn wpstg-btn-sm wpstg-btn-secondary !wpstg-rounded">
                    <?php esc_html_e("Clear All Rules", "wp-staging"); ?>
                </button>

                <div class="wpstg-dropdown wpstg-exclude-filter-dropdown wpstg-flex-shrink-0" id="wpstg-exclude-filter-dropdown">
                    <button type="button" class="wpstg-dropdown-toggler wpstg-btn wpstg-btn-sm wpstg-btn-secondary !wpstg-rounded">
                        <?php esc_html_e("Add Exclude Rule + ", "wp-staging"); ?>
                    </button>
                    <div class="wpstg-dropdown-menu" id="wpstg-exclude-filter-dropdown-menu">
                        <button class="wpstg-dropdown-action wpstg-file-ext-rule"><?php esc_html_e('File Extension', 'wp-staging'); ?></button>
                        <button class="wpstg-dropdown-action wpstg-file-name-rule"><?php esc_html_e('File Name', 'wp-staging'); ?></button>
                        <button class="wpstg-dropdown-action wpstg-dir-name-rule"><?php esc_html_e('Folder Name', 'wp-staging'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($showFileSizeExcludeLimit) : ?>
            <p class="wpstg-file-size-exclude-limit-row wpstg-m-0 wpstg-mt-2 wpstg-flex wpstg-flex-wrap wpstg-items-center wpstg-gap-2 wpstg-text-sm wpstg-leading-5 wpstg-text-[#536579] dark:wpstg-text-slate-400">
                <span><?php esc_html_e('Exclude files greater than', 'wp-staging') ?></span>
                <input type="number" class="wpstg-exclude-rule-input wpstg-file-size-exclude-input wpstg-input wpstg-input-sm !wpstg-w-20" id="wpstg_cloning_file_size_limit_mb" value="8" />
                <span><?php esc_html_e('MB', 'wp-staging') ?></span>
            </p>
        <?php else : ?>
            <div class="wpstg-create-copy-skip wpstg-exclude-skip-row">
                <div class="wpstg-create-copy-size-row">
                    <strong><?php esc_html_e('Skip files larger than', 'wp-staging'); ?></strong>
                    <span class="wpstg-create-file-size-field">
                        <input type="number" class="wpstg-create-file-size-limit" id="wpstg-create-file-size-limit-visible" value="8" min="2" step="1" inputmode="numeric" />
                        <span class="wpstg-create-file-size-unit"><?php esc_html_e('MB', 'wp-staging'); ?></span>
                    </span>
                </div>
                <span><?php esc_html_e('Larger files are skipped to speed up cloning. This may exclude large media, videos, archives or downloads.', 'wp-staging'); ?></span>
            </div>
            <input type="hidden" id="wpstg_cloning_file_size_limit_mb" value="8" />
        <?php endif; ?>
        <div class="wpstg-exclude-list wpstg-mt-3">
            <?php
            if ($scanner->isUpdateOrResetJob()) :
                foreach ($stagingSiteDto->getExcludeSizeRules() as $rule) :
                    $hasRules = true;
                    echo $excludeFilters->renderSizeExclude($rule); // phpcs:ignore
                endforeach;
                foreach ($stagingSiteDto->getExcludeGlobRules() as $rule) :
                    $hasRules = true;
                    echo $excludeFilters->renderGlobExclude($rule); // phpcs:ignore
                endforeach;
            endif; ?>
        </div>
        <p<?php echo !$hasRules ? ' style="display: none;"' : ''; ?> class="wpstg-has-exclude-rules wpstg-m-0 wpstg-mt-3 wpstg-text-[13px] wpstg-leading-5 wpstg-text-[#001b3d] dark:wpstg-text-slate-100"><?php esc_html_e('These rules will not affect wp-admin and wp-includes directories!', 'wp-staging')?></p>
    </div>
    <?php
    if ($stagingSetup->isUpdateJob()) {
        echo '<p class="' . esc_attr($updateExcludeNoticeClass) . '">' . esc_html__("Applying an exclude rule will not effect existing files on the staging site if you don't clean up the wp-content folder before updating. Existing files will not be deleted afterwards automatically!", 'wp-staging') . '</p>';
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
    <div class="<?php echo esc_attr($extraDirectoriesClass); ?>">
        <h4 class="wpstg-selection-title wpstg-m-0">
            <?php echo esc_html__("Extra directories to copy", "wp-staging") ?>
        </h4>

        <textarea id="wpstg_extraDirectories" name="wpstg_extraDirectories" class="wpstg-input wpstg-mt-2 !wpstg-h-24 !wpstg-w-full"></textarea>
        <p class="wpstg-selection-description wpstg-mt-2">
            <span>
                <?php
                echo sprintf(
                    Escape::escapeHtml(__("Enter one folder path per line.<br>Folders must be relative to the path: %s", 'wp-staging')),
                    esc_html($stagingSetup->getRoot())
                );
                ?>
            </span>
        </p>
    </div>
<?php } ?>

<?php if ($showFileDestination && $scanner->isUpdateOrResetJob() && !$stagingSetup->isResetJob()) : ?>
    <p class="<?php echo esc_attr($destinationClass); ?>">
        <span>
            <?php echo esc_html__("All files will be copied to: ", "wp-staging") . "<code>" . esc_html($stagingSiteDto->getDirectoryName()) . "</code>"; ?>
        </span>
    </p>
<?php endif; ?>
</div>
