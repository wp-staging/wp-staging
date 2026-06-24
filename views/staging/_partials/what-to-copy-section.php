<?php

use WPStaging\Staging\Service\DirectoryScanner;
use WPStaging\Staging\Renderer\SetupRenderer;
use WPStaging\Staging\Service\TableScanner;

/**
 * Shared "What to copy" accordion section for staging setup modals.
 *
 * @var DirectoryScanner $directoryScanner
 * @var TableScanner     $tableScanner
 * @var SetupRenderer    $renderer
 * @var string           $copyMode
 */

$copyMode = isset($copyMode) ? $copyMode : 'create';
$copyPanelId = isset($copyPanelId) ? $copyPanelId : sprintf('wpstg-%s-copy-panel', $copyMode);
$copyAccordionCardClass = isset($copyAccordionCardClass) ? $copyAccordionCardClass : 'wpstg-create-accordion-card';
$copyHeaderClass = isset($copyHeaderClass) ? $copyHeaderClass : 'wpstg-tab-header wpstg-create-accordion-header';
$copyChevronClass = isset($copyChevronClass) ? $copyChevronClass : 'wpstg-create-accordion-chevron';
$copyIconClass = isset($copyIconClass) ? $copyIconClass : 'wpstg-create-accordion-icon';
$copyPanelClass = isset($copyPanelClass) ? $copyPanelClass : 'wpstg-create-accordion-panel wpstg-collapse-panel';
$copyBadgeText = isset($copyBadgeText) ? $copyBadgeText : esc_html__('Recommended defaults', 'wp-staging');
$copyDescription = isset($copyDescription) ? $copyDescription : esc_html__('Database tables and files. Defaults are recommended for most sites.', 'wp-staging');
$showFileSizeLimitCard = isset($showFileSizeLimitCard) ? $showFileSizeLimitCard : $copyMode === 'create';
$databaseCheckboxId = sprintf('wpstg-%s-copy-database', $copyMode);
$filesCheckboxId = sprintf('wpstg-%s-copy-files', $copyMode);
$databaseToggleClass = sprintf('wpstg-%s-copy-toggle', $copyMode);
$filesToggleClass = sprintf('wpstg-%s-copy-toggle', $copyMode);
$databaseCustomizeClass = sprintf('wpstg-%s-copy-customize-tables', $copyMode);
$filesCustomizeClass = sprintf('wpstg-%s-copy-customize-files', $copyMode);
?>
<div class="<?php echo esc_attr($copyAccordionCardClass); ?>">
    <a href="#" class="<?php echo esc_attr($copyHeaderClass); ?>" data-id="#<?php echo esc_attr($copyPanelId); ?>" data-collapsed="true" role="button" aria-expanded="false" aria-controls="<?php echo esc_attr($copyPanelId); ?>">
        <span class="<?php echo esc_attr($copyIconClass); ?>" aria-hidden="true">
            <?php $renderer->icon('copy'); ?>
        </span>
        <span class="wpstg-min-w-0 wpstg-flex-1">
            <span class="wpstg-flex wpstg-flex-wrap wpstg-items-center wpstg-gap-2">
                <strong><?php esc_html_e('What to copy', 'wp-staging'); ?></strong>
                <span class="wpstg-create-pill wpstg-create-pill--soft"><?php echo esc_html($copyBadgeText); ?></span>
            </span>
            <span class="wpstg-create-accordion-description"><?php echo esc_html($copyDescription); ?></span>
        </span>
        <span class="<?php echo esc_attr($copyChevronClass); ?>" aria-hidden="true">
            <?php $renderer->icon('chevron', 'wpstg-h-4 wpstg-w-4'); ?>
        </span>
    </a>
    <div class="<?php echo esc_attr($copyPanelClass); ?>" id="<?php echo esc_attr($copyPanelId); ?>" style="display: none;" aria-hidden="true">
        <div class="wpstg-create-copy-options">
            <?php
            $renderer->setupCopyCard(
                $databaseCheckboxId,
                $databaseToggleClass,
                __('Copy WordPress database tables', 'wp-staging'),
                function () {
                    ?>
                    <span
                        data-wpstg-create-copy-summary="tables"
                        data-count-template="<?php echo esc_attr__('%s tables selected automatically.', 'wp-staging'); ?>"
                        data-count-template-singular="<?php echo esc_attr__('%s table selected automatically.', 'wp-staging'); ?>"
                    >
                        <?php esc_html_e('Tables selected automatically.', 'wp-staging'); ?>
                    </span>
                    <?php
                },
                $databaseCustomizeClass,
                __('Customize database tables', 'wp-staging'),
                'database'
            );

            $renderer->setupCopyCard(
                $filesCheckboxId,
                $filesToggleClass,
                __('Copy selected WordPress files', 'wp-staging'),
                function () {
                    ?>
                    <span data-wpstg-create-copy-summary="files"><?php echo wp_kses_post(sprintf(/* translators: %s: file-size limit in MB (a number). */ esc_html__('Includes plugins, themes, uploads, and other WordPress files. Files larger than %s MB are skipped by default.', 'wp-staging'), '<span data-wpstg-files-skip-size>8</span>')); ?></span>
                    <?php
                },
                $filesCustomizeClass,
                __('Customize files and folders', 'wp-staging'),
                'folder',
                function () use ($directoryScanner, $tableScanner) {
                    ?>
                    <section class="wpstg-create-tab-panel-card">
                        <fieldset class="wpstg-tab-section wpstg-selection-tab-section active wpstg-create-selection wpstg-create-selection--tables" id="wpstg-setup-tables">
                            <?php $tableScanner->renderTablesSelection(); ?>
                        </fieldset>
                        <fieldset class="wpstg-tab-section wpstg-selection-tab-section wpstg-create-selection wpstg-create-selection--files" id="wpstg-setup-files">
                            <?php $directoryScanner->renderFilesSelection(); ?>
                        </fieldset>
                    </section>
                    <?php
                }
            );
            ?>
        </div>
    </div>
</div>
