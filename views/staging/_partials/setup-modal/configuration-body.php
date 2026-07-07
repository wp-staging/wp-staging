<?php

use WPStaging\Framework\Language\Language;
use WPStaging\Staging\Service\DirectoryScanner;
use WPStaging\Staging\Service\TableScanner;

/**
 * Renders the shared create-shaped setup modal body.
 *
 * @var \WPStaging\Staging\Renderer\SetupRenderer $renderer
 * @var bool                                      $isCreate
 * @var bool                                      $isUpdate
 * @var bool                                      $isReset
 * @var bool                                      $isProLicenseActive
 * @var bool                                      $isProBuild
 * @var string                                    $setupMode
 * @var \WPStaging\Staging\Service\AbstractStagingSetup $stagingSetup
 * @var \WPStaging\Staging\Dto\StagingSiteDto     $stagingSiteDto
 * @var DirectoryScanner                          $directoryScanner
 * @var TableScanner                              $tableScanner
 * @var string                                    $stagingSiteName
 * @var string                                    $defaultSiteName
 * @var string                                    $productionSiteUrl
 * @var string                                    $selectedEngineName
 * @var string                                    $enginePanelId
 * @var bool                                      $showWooSchedulerSettings
 * @var array                                     $runtimeSummaryTooltips
 * @var string                                    $defaultPathBase
 */

$bodyClass    = sprintf('wpstg-create-setup-modal__body wpstg-%s-setup-modal__body wpstg-staging-setup-modal__body', $setupMode);
$mainClass    = sprintf('wpstg-create-setup-modal__main wpstg-%s-setup-modal__main wpstg-staging-setup-modal__main', $setupMode);
$summaryClass = sprintf('wpstg-create-setup-modal__summary wpstg-%s-setup-modal__summary wpstg-staging-setup-modal__summary', $setupMode);
?>
<div class="<?php echo esc_attr($bodyClass); ?>">
    <main class="<?php echo esc_attr($mainClass); ?>">
        <?php if ($isCreate) : ?>
            <section class="wpstg-create-name-section">
                <h2 class="wpstg-m-0 wpstg-mb-2 wpstg-text-sm wpstg-font-bold wpstg-leading-5 wpstg-text-[#001b3d] dark:wpstg-text-slate-100"><?php esc_html_e('Staging site name', 'wp-staging'); ?></h2>
                <label class="wpstg-block" for="wpstg-new-clone-id">
                    <input
                        type="text"
                        id="wpstg-new-clone-id"
                        class="wpstg-input wpstg-input-lg wpstg-box-border !wpstg-w-full"
                        value="<?php echo esc_attr($defaultSiteName); ?>"
                        placeholder="<?php echo esc_attr($defaultSiteName); ?>"
                        maxlength="100"
                        pattern="[A-Za-z0-9 _\-]+"
                        aria-label="<?php esc_attr_e('Staging site name', 'wp-staging'); ?>"
                        aria-describedby="wpstg-clone-id-error-msg"
                        aria-invalid="false"
                        data-clone="<?php echo esc_attr($stagingSiteDto->getCloneId()); ?>"
                        data-default-name="<?php echo esc_attr($defaultSiteName); ?>"
                        data-production-url="<?php echo esc_attr($productionSiteUrl); ?>"
                        data-invalid-message="<?php esc_attr_e('Use letters, numbers, spaces, hyphens, and underscores only.', 'wp-staging'); ?>"
                        data-exists-message="<?php esc_attr_e('A staging site with this name already exists. Please choose another name.', 'wp-staging'); ?>"
                    />
                </label>
                <div class="wpstg-mt-2.5 wpstg-flex wpstg-flex-wrap wpstg-items-center wpstg-gap-2 wpstg-text-[12.5px] wpstg-leading-5">
                    <span class="wpstg-create-pill wpstg-create-pill--slate"><?php $renderer->icon('link', 'wpstg-h-3 wpstg-w-3'); ?><?php esc_html_e('URL preview', 'wp-staging'); ?></span>
                    <span class="wpstg-break-all wpstg-font-mono wpstg-text-[12.5px] wpstg-font-normal wpstg-text-[#374151] dark:wpstg-text-slate-300" data-wpstg-url-preview><span class="wpstg-create-url-base"><?php echo esc_html(trailingslashit($productionSiteUrl)); ?></span><span class="wpstg-create-url-slug wpstg-font-semibold wpstg-text-blue-700 dark:wpstg-text-blue-300"><?php echo esc_html($defaultSiteName); ?></span></span>
                </div>
                <div id="wpstg-clone-id-error" class="wpstg-callout wpstg-create-name-message wpstg-mt-3" style="display:none;" role="alert" aria-live="polite"><div class="wpstg-text-sm" id="wpstg-clone-id-error-msg"></div></div>
            </section>
            <?php $stagingSetup->renderNetworkCloneSettings(); ?>
            <?php
            $renderer->readyCard('', '', '', '', true, '', '', '', '', 'shield', [
                'database' => [
                    'content' => '#wpstg-setup-tables',
                    'label'   => __('Customize', 'wp-staging'),
                    'panel'   => '#wpstg-create-copy-panel',
                    'trigger' => '.wpstg-create-copy-customize-tables',
                ],
                'files'    => [
                    'content' => '#wpstg-setup-files',
                    'label'   => __('Customize', 'wp-staging'),
                    'panel'   => '#wpstg-create-copy-panel',
                    'trigger' => '.wpstg-create-copy-customize-files',
                ],
                'engine'   => [
                    'label' => __('Customize', 'wp-staging'),
                    'panel' => '#wpstg-create-engine-panel',
                ],
                'runtime'  => [
                    'label' => __('Customize', 'wp-staging'),
                    'panel' => '#wpstg-runtime-settings',
                ],
            ], !$isProLicenseActive);
            ?>
        <?php else : ?>
            <?php
            $readyCustomizeLinks = [
                'database' => [
                    'content' => '#wpstg-setup-tables',
                    'label'   => __('Customize', 'wp-staging'),
                    'panel'   => sprintf('#wpstg-%s-copy-panel', $setupMode),
                    'trigger' => sprintf('.wpstg-%s-copy-customize-tables', $setupMode),
                ],
                'files'    => [
                    'content' => '#wpstg-setup-files',
                    'label'   => __('Customize', 'wp-staging'),
                    'panel'   => sprintf('#wpstg-%s-copy-panel', $setupMode),
                    'trigger' => sprintf('.wpstg-%s-copy-customize-files', $setupMode),
                ],
                'engine'   => [
                    'label' => __('Customize', 'wp-staging'),
                    'panel' => '#' . $enginePanelId,
                ],
            ];

            $renderer->readyCard(
                $isReset ? __('Preselected reset data ready to review', 'wp-staging') : __('Recommended selections ready to review', 'wp-staging'),
                $isReset ? __('The original selection for tables and files have been preselected. You can adjust and verify them before starting the reset.', 'wp-staging') : __('These table and folder selections will be remembered for future updates and resets of this staging site.', 'wp-staging'),
                __('WordPress tables preselected', 'wp-staging'),
                __('WordPress files preselected', 'wp-staging'),
                $isUpdate,
                __('Staging isolation can be reviewed', 'wp-staging'),
                'wpstg-mt-0',
                '',
                '',
                'shield',
                $readyCustomizeLinks
            );
            ?>
        <?php endif; ?>

        <section class="<?php echo esc_attr($isCreate ? 'wpstg-create-customizations wpstg-create-customizations--collapsed' : 'wpstg-create-customizations wpstg-mt-4'); ?>">
            <?php if ($isCreate) : ?>
                <h2 class="wpstg-m-0 wpstg-text-sm wpstg-font-bold wpstg-leading-5 wpstg-text-[#001b3d] dark:wpstg-text-slate-100"><?php esc_html_e('Customize staging site', 'wp-staging'); ?></h2>
                <p class="wpstg-m-0 wpstg-mt-2 wpstg-text-sm wpstg-leading-6 wpstg-text-[#536579] dark:wpstg-text-slate-400"><?php esc_html_e('Recommended defaults are selected. Open a section only to change what gets copied or how staging behaves.', 'wp-staging'); ?></p>
            <?php endif; ?>
            <div id="wpstg-staging-setup-tabs" class="wpstg-tabs-wrapper wpstg-selection-tabs-wrapper wpstg-create-accordion wpstg-staging-accordion wpstg-mt-4">
                <?php
                $copyMode = $setupMode;
                $copyBadgeText = $isCreate ? esc_html__('Recommended defaults', 'wp-staging') : esc_html__('Preselected defaults', 'wp-staging');
                $copyDescription = $isCreate ? esc_html__('Database tables and files. Defaults are recommended for most sites.', 'wp-staging') : esc_html__('Tables and files are preselected from the saved staging selection.', 'wp-staging');
                $copyPanelClass = 'wpstg-create-accordion-panel wpstg-staging-accordion-panel wpstg-collapse-panel';
                $copyAccordionCardClass = 'wpstg-create-accordion-card wpstg-staging-accordion-card';
                $copyHeaderClass = 'wpstg-tab-header wpstg-create-accordion-header wpstg-staging-accordion-header';
                $copyChevronClass = 'wpstg-create-accordion-chevron wpstg-staging-accordion-chevron';
                $copyIconClass = 'wpstg-create-accordion-icon wpstg-staging-accordion-icon';
                $showFileSizeLimitCard = true;
                assert($directoryScanner instanceof DirectoryScanner);
                assert($tableScanner instanceof TableScanner);
                require WPSTG_VIEWS_DIR . 'staging/_partials/what-to-copy-section.php';
                ?>
                <?php $renderer->engineSection($setupMode, $enginePanelId); ?>
                <?php $renderer->runtimeSection($isCreate, $isUpdate, $isProLicenseActive, $showWooSchedulerSettings, $stagingSiteDto, $runtimeSummaryTooltips); ?>
                <?php if ($isCreate) {
                    $renderer->destinationSection($isProLicenseActive, $isProBuild, $isCreate, $stagingSetup, $defaultPathBase, $defaultSiteName, $productionSiteUrl);
                } ?>
            </div>
        </section>
    </main>

    <aside class="<?php echo esc_attr($summaryClass); ?>" aria-label="<?php echo esc_attr($isCreate ? __('Creation Summary', 'wp-staging') : ($isUpdate ? __('Update Summary', 'wp-staging') : __('Reset Summary', 'wp-staging'))); ?>">
        <div class="wpstg-create-summary-sticky">
        <section class="wpstg-create-summary-block wpstg-staging-summary-block">
            <h2><?php $renderer->icon('clipboard', 'wpstg-create-summary-heading-icon'); ?><?php echo esc_html($isCreate ? __('Creation Summary', 'wp-staging') : ($isUpdate ? __('Update Summary', 'wp-staging') : __('Reset Summary', 'wp-staging'))); ?></h2>
            <dl class="wpstg-create-summary-list wpstg-staging-summary-list">
                <div><dt><?php $renderer->icon('globe', 'wpstg-create-summary-icon'); ?><?php echo esc_html($isCreate ? __('Site name', 'wp-staging') : __('Staging site', 'wp-staging')); ?></dt><dd class="<?php echo esc_attr($isCreate ? 'wpstg-create-summary-site-name' : ''); ?>"><?php echo esc_html($stagingSiteName); ?></dd></div>
                <div><dt><?php $renderer->icon('database', 'wpstg-create-summary-icon'); ?><?php esc_html_e('Database', 'wp-staging'); ?></dt><dd class="wpstg-create-summary-database"><?php echo esc_html($isCreate ? __('WordPress tables', 'wp-staging') : __('Preselected', 'wp-staging')); ?></dd></div>
                <div><dt><?php $renderer->icon('folder', 'wpstg-create-summary-icon'); ?><?php esc_html_e('Files', 'wp-staging'); ?></dt>
                    <?php if ($isCreate) : ?>
                        <dd class="wpstg-create-summary-files-cell">
                            <span class="wpstg-create-summary-files"><?php esc_html_e('All folders selected', 'wp-staging'); ?></span>
                            <small class="wpstg-create-summary-subnote"><?php echo wp_kses_post(sprintf(/* translators: %s: file-size limit in MB (a number). */ esc_html__('Files over %s MB skipped', 'wp-staging'), '<span data-wpstg-files-skip-size>8</span>')); ?></small>
                        </dd>
                    <?php else : ?>
                        <dd><?php esc_html_e('Preselected', 'wp-staging'); ?></dd>
                    <?php endif; ?>
                </div>
                <?php if ($isCreate) : ?>
                    <hr class="wpstg-create-summary-divider" />
                    <div class="wpstg-create-summary-subhead"><?php esc_html_e('Staging isolation', 'wp-staging'); ?></div>
                    <div><dt><?php $renderer->icon('mail', 'wpstg-create-summary-icon'); ?><?php esc_html_e('Emails', 'wp-staging'); ?></dt><?php $renderer->runtimeSummaryValue('emails', $runtimeSummaryTooltips['emails'], $isProLicenseActive); ?></div>
                    <div><dt><?php $renderer->icon('clock', 'wpstg-create-summary-icon'); ?><?php esc_html_e('Cron jobs', 'wp-staging'); ?></dt><?php $renderer->runtimeSummaryValue('cron', $runtimeSummaryTooltips['cron'], $isProLicenseActive); ?></div>
                    <div><dt><?php $renderer->icon('cart', 'wpstg-create-summary-icon'); ?><?php esc_html_e('WooCommerce actions', 'wp-staging'); ?></dt><?php $renderer->runtimeSummaryValue('woo', $runtimeSummaryTooltips['woo'], $isProLicenseActive); ?></div>
                <?php endif; ?>
            </dl>
            <?php if ($isCreate && !$isProLicenseActive) : ?>
                <a class="wpstg-create-summary-pro-link" href="<?php echo esc_url(Language::getUpgradeUrl('creation_summary')); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Requires WP STAGING Pro', 'wp-staging'); ?>"><?php $renderer->icon('sparkles', 'wpstg-h-3 wpstg-w-3'); ?><?php esc_html_e('Advanced controls available in Pro', 'wp-staging'); ?></a>
            <?php endif; ?>
        </section>
        <?php if ($isReset) : ?>
            <section class="wpstg-create-summary-block wpstg-staging-summary-card">
                <h2><?php esc_html_e('Reset target', 'wp-staging'); ?></h2>
                <p><?php esc_html_e('This will reset staging site:', 'wp-staging'); ?></p>
                <code class="wpstg-mt-2 wpstg-inline-block"><?php echo esc_html($stagingSiteName); ?></code>
            </section>
        <?php endif; ?>
        <?php if (!$isReset) : ?>
            <hr class="wpstg-create-summary-divider wpstg-create-summary-divider--section" />
            <section class="wpstg-create-summary-block wpstg-create-summary-block--disk">
                <h2><?php esc_html_e('Estimated size', 'wp-staging'); ?></h2>
                <p class="wpstg-create-disk-status">
                    <span class="wpstg-create-disk-spinner" data-wpstg-disk-spinner aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.2"></circle><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path></svg>
                    </span>
                    <strong data-wpstg-disk-space-status data-status-checking="<?php esc_attr_e('Calculating…', 'wp-staging'); ?>" data-status-failed="<?php esc_attr_e('Check failed', 'wp-staging'); ?>"><?php esc_html_e('Not calculated yet', 'wp-staging'); ?></strong>
                </p>
                <div id="wpstg-disk-space-result" class="wpstg-create-disk-result" style="display:none;">
                    <p class="wpstg-create-disk-total wpstg-m-0"><span data-wpstg-disk-space-value></span> <span class="wpstg-create-disk-total-suffix"><?php esc_html_e('total', 'wp-staging'); ?></span></p>
                    <div id="wpstg-disk-space-result-msg"></div>
                </div>
                <a href="#" id="wpstg-check-space" class="wpstg-create-disk-link wpstg-staging-disk-link"><?php $renderer->icon('refresh', 'wpstg-h-3 wpstg-w-3'); ?><?php esc_html_e('Recalculate size', 'wp-staging'); ?></a>
            </section>
        <?php endif; ?>
        </div>
    </aside>
</div>
