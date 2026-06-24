<?php

use WPStaging\Framework\Facades\UI\Checkbox;
use WPStaging\Staging\Service\AbstractStagingSetup;

/**
 * Renders destination and advanced create options.
 *
 * @var \WPStaging\Staging\Renderer\SetupRenderer $renderer
 * @var bool                                      $isProLicenseActive
 * @var bool                                      $isProBuild
 * @var bool                                      $isCreate
 * @var AbstractStagingSetup                      $stagingSetup
 * @var string                                    $defaultPathBase
 * @var string                                    $defaultSiteName
 * @var string                                    $productionSiteUrl
 */

$renderer->accordionSection([
    'badge'        => $isProLicenseActive ? __('Default location', 'wp-staging') : __('Available in Pro', 'wp-staging'),
    'badgeClass'   => $isProLicenseActive ? 'wpstg-create-pill wpstg-create-pill--slate' : 'wpstg-badge-amber',
    'badgeIcon'    => $isProLicenseActive ? '' : 'lock',
    'cardClass'    => 'wpstg-create-accordion-card wpstg-relative wpstg-z-20 wpstg-overflow-visible',
    'chevronClass' => 'wpstg-create-accordion-chevron',
    'description'  => __('Choose where the staging site is created and which database it uses.', 'wp-staging'),
    'headerClass'  => 'wpstg-tab-header wpstg-create-accordion-header',
    'icon'         => 'server',
    'iconClass'    => 'wpstg-create-accordion-icon',
    'panelClass'   => 'wpstg-create-accordion-panel wpstg-collapse-panel wpstg-overflow-visible',
    'panelId'      => 'wpstg-destination-advanced-settings',
    'title'        => __('Destination and advanced options', 'wp-staging'),
], function () use ($renderer, $isProLicenseActive, $isProBuild, $isCreate, $stagingSetup, $defaultPathBase, $defaultSiteName, $productionSiteUrl) {
    // Treat an unlicensed Pro install like the free build: lock the advanced
    // destination controls (custom path/url, external DB, new admin, symlink).
    $isPro                 = $isProLicenseActive;
    $isDestinationDisabled = !$isPro;
    $advancedSectionStyle  = (!$isProBuild && $isCreate) || $stagingSetup->getIsOpenDisabledSettingsSectionByDefault() ? '' : ' style="display: none;"';

    // Locked controls in the free build carry the same "Available in Pro"
    // badge as the Staging isolation section, so the gating reads the same way.
    $renderProBadge = function ($extraClass = '') use ($renderer) {
        ?><span class="wpstg-badge-amber <?php echo esc_attr($extraClass); ?>"><?php $renderer->icon('lock', 'wpstg-h-3 wpstg-w-3'); ?><?php esc_html_e('Available in Pro', 'wp-staging'); ?></span><?php
    };

    $pathInlineTarget = $isPro ? ' data-wpstg-inline-target="#wpstg_clone_dir"' : '';
    $urlInlineTarget  = $isPro ? ' data-wpstg-inline-target="#wpstg_clone_hostname"' : '';
    ?>
    <div class="wpstg-create-destination-stack" data-wpstg-advanced-settings-panel>
        <section class="wpstg-create-destination-card">
            <div class="wpstg-create-destination-card__title">
                <?php $renderer->icon('folder', 'wpstg-h-5 wpstg-w-5'); ?>
                <h3><?php esc_html_e('Destination', 'wp-staging'); ?></h3>
                <?php if ($isDestinationDisabled) {
                    $renderProBadge('wpstg-ml-auto');
                } ?>
            </div>
            <p><?php esc_html_e('Default staging location selected.', 'wp-staging'); ?></p>
            <dl class="wpstg-create-destination-meta">
                <div data-wpstg-inline-edit="path"<?php echo $pathInlineTarget; // phpcs:ignore WPStagingCS.Security.EscapeOutput.OutputNotEscaped ?>>
                    <dt><?php esc_html_e('Path', 'wp-staging'); ?></dt>
                    <dd>
                        <span class="wpstg-create-inline-display" data-wpstg-inline-display>
                            <span class="wpstg-create-inline-value" data-wpstg-inline-value><span class="wpstg-create-path-base"><?php echo esc_html($defaultPathBase); ?></span><span class="wpstg-create-path-slug"><?php echo esc_html($defaultSiteName); ?></span>/</span>
                            <?php if ($isPro) :
                                ?><button type="button" class="wpstg-create-inline-customize" data-wpstg-inline-edit-trigger><?php esc_html_e('Customize', 'wp-staging'); ?></button><?php
                            else :
                                ?><button type="button" class="wpstg-create-inline-customize" disabled aria-disabled="true" title="<?php esc_attr_e('Available in Pro', 'wp-staging'); ?>"><?php esc_html_e('Customize', 'wp-staging'); ?></button><?php
                            endif; ?>
                        </span>
                        <?php if ($isPro) : ?>
                            <span class="wpstg-create-inline-form" data-wpstg-inline-form style="display:none;">
                                <input type="text" class="wpstg-input wpstg-create-inline-input" data-wpstg-inline-input aria-label="<?php esc_attr_e('Destination path', 'wp-staging'); ?>" />
                                <button type="button" class="wpstg-create-inline-save" data-wpstg-inline-save><?php esc_html_e('Save', 'wp-staging'); ?></button>
                                <button type="button" class="wpstg-create-inline-cancel" data-wpstg-inline-cancel><?php esc_html_e('Cancel', 'wp-staging'); ?></button>
                            </span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div data-wpstg-inline-edit="url"<?php echo $urlInlineTarget; // phpcs:ignore WPStagingCS.Security.EscapeOutput.OutputNotEscaped ?>>
                    <dt><?php esc_html_e('URL', 'wp-staging'); ?></dt>
                    <dd>
                        <span class="wpstg-create-inline-display" data-wpstg-inline-display>
                            <span class="wpstg-create-inline-value" data-wpstg-inline-value><span class="wpstg-create-url-base"><?php echo esc_html(trailingslashit($productionSiteUrl)); ?></span><span class="wpstg-create-url-slug"><?php echo esc_html($defaultSiteName); ?></span></span>
                            <?php if ($isPro) :
                                ?><button type="button" class="wpstg-create-inline-customize" data-wpstg-inline-edit-trigger><?php esc_html_e('Customize', 'wp-staging'); ?></button><?php
                            else :
                                ?><button type="button" class="wpstg-create-inline-customize" disabled aria-disabled="true" title="<?php esc_attr_e('Available in Pro', 'wp-staging'); ?>"><?php esc_html_e('Customize', 'wp-staging'); ?></button><?php
                            endif; ?>
                        </span>
                        <?php if ($isPro) : ?>
                            <span class="wpstg-create-inline-form" data-wpstg-inline-form style="display:none;">
                                <input type="text" class="wpstg-input wpstg-create-inline-input" data-wpstg-inline-input aria-label="<?php esc_attr_e('Staging site URL', 'wp-staging'); ?>" />
                                <button type="button" class="wpstg-create-inline-save" data-wpstg-inline-save><?php esc_html_e('Save', 'wp-staging'); ?></button>
                                <button type="button" class="wpstg-create-inline-cancel" data-wpstg-inline-cancel><?php esc_html_e('Cancel', 'wp-staging'); ?></button>
                            </span>
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>
            <?php
            // The read-only Destination Path / Target Hostname fields are
            // redundant once the path/URL rows carry their own (disabled)
            // Customize affordance, so only render the editable fields for a
            // licensed Pro install. Free create defaults to the standard
            // location and never reads these inputs.
            if ($isPro) : ?>
                <div id="wpstg-clone-directory" class="wpstg-advanced-settings-expanded-section"<?php echo $advancedSectionStyle; // phpcs:ignore WPStagingCS.Security.EscapeOutput.OutputNotEscaped ?>>
                    <div class="wpstg-advanced-settings-expanded-fields wpstg-py-1">
                        <?php $stagingSetup->renderCustomDirectorySettings(); ?>
                    </div>
                </div>
            <?php endif;
            ?>
        </section>

        <section class="wpstg-create-destination-card">
            <div class="wpstg-create-destination-card__title">
                <?php $renderer->icon('database', 'wpstg-h-5 wpstg-w-5'); ?>
                <h3><?php esc_html_e('Database', 'wp-staging'); ?></h3>
                <?php if ($isDestinationDisabled) {
                    $renderProBadge('wpstg-ml-auto');
                } ?>
            </div>
            <p><?php esc_html_e('Current WordPress database selected. Tables will use a staging prefix and will not replace live tables.', 'wp-staging'); ?></p>
            <?php if ($isPro) : ?>
                <input type="checkbox" id="wpstg-ext-db" name="wpstg-ext-db" value="true" class="wpstg-toggle-advance-settings-section wpstg-create-hidden-toggle" data-id="wpstg-external-db-section" />
                <label for="wpstg-ext-db" class="wpstg-create-action-button">
                    <?php $renderer->icon('database', 'wpstg-h-4 wpstg-w-4'); ?>
                    <?php esc_html_e('Use a different database', 'wp-staging'); ?>
                </label>
            <?php endif; ?>
            <div id="wpstg-external-db-section" class="wpstg-advanced-settings-expanded-section"<?php echo $advancedSectionStyle; // phpcs:ignore WPStagingCS.Security.EscapeOutput.OutputNotEscaped ?>>
                <div class="wpstg-advanced-settings-expanded-fields wpstg-py-1">
                    <?php $stagingSetup->renderExternalDatabaseSettings(); ?>
                    <?php if ($isProBuild) : ?>
                        <div class="wpstg-form-group wpstg-text-field wpstg-advanced-settings-link-row wpstg-mt-1 wpstg-flex wpstg-flex-wrap wpstg-items-center wpstg-gap-2">
                            <button type="button" id="wpstg-db-connect" class="wpstg-create-action-button" disabled>
                                <span class="wpstg-create-btn-spinner" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.2"></circle><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path></svg>
                                </span>
                                <?php esc_html_e('Test Database Connection', 'wp-staging'); ?>
                            </button>
                        </div>
                        <span class="wpstg-create-db-test-result" data-wpstg-db-test-result></span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <div class="wpstg-create-destination-grid">
            <section class="wpstg-create-destination-card wpstg-create-destination-card--wide">
                <div class="wpstg-create-destination-card__title">
                    <?php $renderer->icon('user-plus', 'wpstg-h-5 wpstg-w-5'); ?>
                    <h3><?php esc_html_e('Add new admin account', 'wp-staging'); ?></h3>
                    <?php if ($isDestinationDisabled) {
                        $renderProBadge('wpstg-ml-auto');
                    } ?>
                </div>
                <p><?php esc_html_e('Created only on the staging site.', 'wp-staging'); ?></p>
                <?php if ($isPro) : ?>
                    <input type="checkbox" id="wpstg-new-admin-user" name="wpstg-new-admin-user" value="true" class="wpstg-toggle-advance-settings-section wpstg-create-hidden-toggle" data-id="wpstg-new-admin-user-section" />
                    <label for="wpstg-new-admin-user" class="wpstg-create-action-button">
                        <?php $renderer->icon('user-plus', 'wpstg-h-4 wpstg-w-4'); ?>
                        <?php esc_html_e('Add admin account', 'wp-staging'); ?>
                    </label>
                <?php endif; ?>
                <div id="wpstg-new-admin-user-section" class="wpstg-advanced-settings-expanded-section"<?php echo $advancedSectionStyle; // phpcs:ignore WPStagingCS.Security.EscapeOutput.OutputNotEscaped ?>>
                    <div class="wpstg-advanced-settings-expanded-fields wpstg-py-1">
                        <?php $stagingSetup->renderNewAdminSettings(); ?>
                    </div>
                </div>
            </section>

            <label class="wpstg-create-option-card wpstg-create-option-card--compact wpstg-create-option-card--wide <?php echo esc_attr($isDestinationDisabled ? 'wpstg-create-option-card--locked' : ''); ?>" for="wpstg_symlink_upload">
                <?php Checkbox::render('wpstg_symlink_upload', 'wpstg_symlink_upload', 'true', false, ['usePrimitive' => true, 'isDisabled' => $isDestinationDisabled]); ?>
                <span class="wpstg-create-option-card__copy">
                    <strong><?php esc_html_e('Reuse uploads via symlink', 'wp-staging'); ?></strong>
                    <span><?php esc_html_e('Advanced option. Saves disk space but links staging uploads to live files.', 'wp-staging'); ?></span>
                </span>
                <span class="wpstg-ml-auto wpstg-flex wpstg-flex-shrink-0 wpstg-items-start wpstg-gap-2">
                    <?php if ($isDestinationDisabled) {
                        $renderProBadge('wpstg-mt-0.5');
                    } ?>
                    <span class="wpstg--tooltip wpstg-mt-0.5 wpstg-flex wpstg-h-5 wpstg-w-5 wpstg-flex-shrink-0 wpstg-items-center wpstg-justify-center">
                        <span class="dashicons dashicons-info-outline wpstg-text-[#a8b5c6]" aria-hidden="true"></span>
                        <span class="wpstg--tooltiptext wpstg-bottom-0"><?php echo wp_kses_post(esc_html__('Symlinks the staging wp-content/uploads folder to the production uploads folder, so no upload files are copied — this speeds up cloning and pushing a lot.', 'wp-staging') . '<br/><br/>' . esc_html__('Warning: both sites then share the same uploads folder, so editing or replacing images on staging also changes them on production, and mixed-content issues can occur if both sites load stylesheets from uploads. Use with care.', 'wp-staging') . '<br/><br/>' . esc_html__('Only works when the staging site is on the same domain as production.', 'wp-staging')); ?></span>
                    </span>
                </span>
            </label>
        </div>
    </div>
    <?php
});
