<?php

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\UI\Checkbox;
use WPStaging\Framework\Language\Language;
use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\Service\AbstractStagingSetup;
use WPStaging\Staging\Service\DirectoryScanner;
use WPStaging\Staging\Service\StagingEngine;
use WPStaging\Staging\Service\TableScanner;
use WPStaging\Staging\Renderer\SetupRenderer;

/**
 * Unified setup UI for create, update, and reset staging jobs.
 *
 * @var AbstractStagingSetup $stagingSetup
 * @var StagingSiteDto       $stagingSiteDto
 * @var DirectoryScanner     $directoryScanner
 * @var TableScanner         $tableScanner
 * @var string               $setupMode
 */

$setupMode = isset($setupMode) ? $setupMode : 'create';
$isCreate  = $setupMode === 'create';
$isUpdate  = $setupMode === 'update';
$isReset   = $setupMode === 'reset';
$isPro      = WPStaging::isPro();
// Pro advanced settings stay locked unless the Pro build also has a valid/active
// license; an unlicensed Pro install is gated exactly like the free build.
$isProLicenseActive = $stagingSetup->isProLicenseActive();

$productionSiteUrl  = home_url('/');
$defaultSiteName    = $isCreate ? $stagingSiteDto->getSiteName() : '';
if ($isCreate && empty($defaultSiteName)) {
    $defaultSiteName = 'staging';
}

$previewSiteUrl     = trailingslashit($productionSiteUrl) . $defaultSiteName;
$defaultPathBase    = trailingslashit(wp_normalize_path(ABSPATH));

$stagingSiteName    = $isCreate ? $defaultSiteName : $stagingSiteDto->getSiteName();
if (!$isCreate && empty($stagingSiteName)) {
    $stagingSiteName = $stagingSiteDto->getCloneId();
}

$selectedEngine     = WPStaging::make(StagingEngine::class)->getEngine();
$selectedEngineName = $selectedEngine === StagingEngine::ENGINE_NEXT_GEN ? esc_html__('Next-Gen', 'wp-staging') : esc_html__('Classic', 'wp-staging');
$enginePanelId      = sprintf('wpstg-%s-engine-panel', $setupMode);
$setupRenderer      = new SetupRenderer();
$setupRenderer->setSelectedEngineName($selectedEngineName);

$stagingSiteDirectoryName = $stagingSiteDto->getDirectoryName();
$stagingSiteUrl           = $isCreate ? $previewSiteUrl : $stagingSiteDto->getUrl();
if (!$isCreate && empty($stagingSiteUrl) && !empty($stagingSiteDirectoryName)) {
    $stagingSiteUrl = trailingslashit($productionSiteUrl) . $stagingSiteDirectoryName;
}

$productionSiteHost       = wp_parse_url($productionSiteUrl, PHP_URL_HOST);
$stagingSitePath          = wp_parse_url($stagingSiteUrl, PHP_URL_PATH);
if (empty($productionSiteHost)) {
    $productionSiteHost = untrailingslashit(str_replace(['https://', 'http://'], '', $productionSiteUrl));
}

if (empty($stagingSitePath) || $stagingSitePath === '/') {
    $stagingSitePathName = empty($stagingSiteDirectoryName) ? $stagingSiteName : $stagingSiteDirectoryName;
    $stagingSitePath     = '/' . trim($stagingSitePathName, '/');
}

$showWooSchedulerSettings  = $setupRenderer->hasWooSchedulerSettings($stagingSetup);
$runtimeSummaryUpgradeLink = $isProLicenseActive ? '' : sprintf(
    ' <a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
    esc_url(Language::localizePricingUrl('https://wp-staging.com/#pricing')),
    esc_html__('Upgrade', 'wp-staging')
);


$runtimeSummaryTooltips = [
    'emails' => [
        'enabled'  => __('The staging site is allowed to send real emails.', 'wp-staging'),
        'disabled' => __('Email sending is blocked on the staging site.', 'wp-staging'),
    ],
    'cron'   => [
        'enabled'  => __('WordPress cron runs scheduled tasks such as publishing schedules, cleanup jobs, and plugin maintenance on staging.', 'wp-staging') . $runtimeSummaryUpgradeLink,
        'disabled' => __('WordPress cron is blocked, so scheduled tasks will not run on the staging site.', 'wp-staging') . $runtimeSummaryUpgradeLink,
    ],
    'woo'    => [
        'enabled'  => __('WooCommerce background actions can run on staging for subscriptions, webhooks, cleanup, and other queued shop tasks.', 'wp-staging') . $runtimeSummaryUpgradeLink,
        'disabled' => __('WooCommerce background actions are blocked, so queued shop tasks will not run on the staging site.', 'wp-staging') . $runtimeSummaryUpgradeLink,
    ],
];
?>

<?php if ($isUpdate) :
    $stagingSiteDisplay = preg_replace('#^https?://#', '', untrailingslashit($stagingSiteUrl));
    $hasSavedSelection  = !empty($stagingSiteDto->getIncludedTables()) || !empty($stagingSiteDto->getExcludedDirectories());

    // Advanced options surfaced from the (previously hidden) runtime/cleanup
    // config. Defaults, ids and the free Pro-lock mirror the runtime section so
    // the update payload stays byte-for-byte identical.
    $advOptionDisabled = !$isProLicenseActive;
    $advNotifyOptions  = [
        ['id' => 'wpstg_allow_emails', 'icon' => 'mail', 'label' => __('Allow Emails Sending', 'wp-staging'), 'checked' => $isProLicenseActive, 'sumLabel' => __('Emails', 'wp-staging'), 'tip' => __('Let the staging site send real emails after the update. Keep off so test activity never reaches real users.', 'wp-staging')],
        ['id' => 'wpstg_reminder_emails', 'icon' => 'bell', 'label' => __('Get Reminder Email', 'wp-staging'), 'checked' => false, 'sumLabel' => __('Reminder email', 'wp-staging'), 'tip' => __('Email you a periodic reminder that this staging site still exists.', 'wp-staging')],
        ['id' => 'wpstg_auto_update_plugins', 'icon' => 'refresh', 'label' => __('Auto Update Plugins', 'wp-staging'), 'checked' => false, 'sumLabel' => __('Auto-update plugins', 'wp-staging'), 'tip' => __('Automatically update plugins on staging once the update finishes.', 'wp-staging')],
    ];
    if ($showWooSchedulerSettings) {
        $advNotifyOptions[] = ['id' => 'wpstg_woo_scheduler_enabled', 'icon' => 'cart', 'label' => __('Enable WooCommerce Scheduler', 'wp-staging'), 'checked' => $isProLicenseActive, 'sumLabel' => __('WooCommerce scheduler', 'wp-staging'), 'tip' => __('Run WooCommerce scheduled actions on staging. Off prevents orders, emails and background jobs.', 'wp-staging')];
    }

    $advCleanupOptions = [
        ['id' => 'wpstg-clean-plugins-themes', 'label' => __('Clean Plugins/Themes', 'wp-staging'), 'checked' => false, 'disabled' => false, 'sumLabel' => __('Clean plugins/themes', 'wp-staging'), 'desc' => __('Remove staging plugins and themes that no longer exist on the live site.', 'wp-staging')],
        ['id' => 'wpstg-clean-uploads', 'label' => __('Clean Uploads', 'wp-staging'), 'checked' => false, 'disabled' => $stagingSiteDto->getUploadsSymlinked(), 'sumLabel' => __('Clean uploads', 'wp-staging'), 'desc' => $stagingSiteDto->getUploadsSymlinked() ? __('Disabled because uploads are symlinked.', 'wp-staging') : __('Remove staging uploads before copying uploads from the live site.', 'wp-staging')],
    ];

    // Summary mirrors only the enabled options (cleanup flagged as destructive).
    $advSummaryOptions = [];
    foreach ($advNotifyOptions as $advOption) {
        $advSummaryOptions[] = ['id' => $advOption['id'], 'icon' => $advOption['icon'], 'sumLabel' => $advOption['sumLabel'], 'checked' => $advOption['checked'], 'risk' => false];
    }

    foreach ($advCleanupOptions as $advOption) {
        $advSummaryOptions[] = ['id' => $advOption['id'], 'icon' => 'trash', 'sumLabel' => $advOption['sumLabel'], 'checked' => $advOption['checked'], 'risk' => true];
    }

    $advAnyEnabled = (bool) array_filter(wp_list_pluck($advSummaryOptions, 'checked'));
    ?>
    <div class="wpstg-update-setup-modal wpstg-create-setup-modal wpstg-staging-setup-modal wpstg-text-left" role="dialog" aria-modal="true" aria-labelledby="wpstg-update-modal-title" data-engine-legacy-label="<?php esc_attr_e('Classic', 'wp-staging'); ?>" data-engine-next-gen-label="<?php esc_attr_e('Next-Gen', 'wp-staging'); ?>" data-update-none="<?php esc_attr_e('None', 'wp-staging'); ?>" data-update-empty-summary="<?php esc_attr_e('Nothing selected — open to choose what to overwrite', 'wp-staging'); ?>" data-update-tables-count="<?php esc_attr_e('%1$s of %2$s tables · %3$s', 'wp-staging'); ?>" data-update-folders-count="<?php esc_attr_e('%1$s of %2$s folders', 'wp-staging'); ?>" data-update-of="<?php esc_attr_e('%1$s of %2$s', 'wp-staging'); ?>" data-update-summary-line="<?php esc_attr_e('%1$s tables · %2$s folders', 'wp-staging'); ?>">
        <?php $setupRenderer->closeButton('wpstg-update-modal-close wpstg-staging-modal-close'); ?>

        <header class="wpstg-update-setup-modal__header">
            <span class="wpstg-update-header-badge" aria-hidden="true"><?php $setupRenderer->icon('refresh', 'wpstg-h-5 wpstg-w-5'); ?></span>
            <div class="wpstg-min-w-0 wpstg-flex-1">
                <h1 id="wpstg-update-modal-title" class="wpstg-update-header-title"><?php esc_html_e('Update Staging Site', 'wp-staging'); ?></h1>
                <p class="wpstg-update-header-subtitle">
                    <?php echo wp_kses_post(sprintf(
                        /* translators: %s: staging site name */
                        __('Copy selected files and database tables from your live site to %s.', 'wp-staging'),
                        '<span class="wpstg-font-semibold wpstg-text-gray-700 dark:wpstg-text-slate-300">' . esc_html($stagingSiteName) . '</span>'
                    )); ?>
                </p>
            </div>
        </header>

        <div class="wpstg-update-setup-modal__body">
            <main class="wpstg-update-setup-modal__main">

                <section class="wpstg-update-section">
                    <h2 class="wpstg-update-section__title"><?php esc_html_e('Staging site to update', 'wp-staging'); ?></h2>
                    <div class="wpstg-update-direction">
                        <div class="wpstg-update-direction__node">
                            <span class="wpstg-update-direction__label"><?php esc_html_e('Source', 'wp-staging'); ?></span>
                            <span class="wpstg-update-direction__name"><?php $setupRenderer->icon('globe', 'wpstg-update-direction__icon'); ?><span class="wpstg-truncate"><?php esc_html_e('Live Site', 'wp-staging'); ?></span></span>
                            <span class="wpstg-update-direction__url"><?php echo esc_html($productionSiteHost); ?></span>
                        </div>
                        <div class="wpstg-update-direction__arrow" aria-hidden="true">
                            <?php $setupRenderer->icon('arrow-right', 'wpstg-h-[18px] wpstg-w-[18px]', 2); ?>
                            <span class="wpstg-update-direction__arrow-label"><?php esc_html_e('overwrites', 'wp-staging'); ?></span>
                        </div>
                        <div class="wpstg-update-direction__node wpstg-update-direction__node--target">
                            <span class="wpstg-update-direction__label"><?php esc_html_e('Target — staging', 'wp-staging'); ?></span>
                            <span class="wpstg-update-direction__name"><?php $setupRenderer->icon('server', 'wpstg-update-direction__icon wpstg-update-direction__icon--target'); ?><span class="wpstg-truncate"><?php echo esc_html($stagingSiteName); ?></span></span>
                            <span class="wpstg-update-direction__url"><?php echo esc_html($stagingSiteDisplay); ?></span>
                        </div>
                    </div>
                </section>

                <section class="wpstg-update-section">
                    <div class="wpstg-update-hero">
                        <span class="wpstg-update-hero__icon" aria-hidden="true"><?php $setupRenderer->icon('warning', 'wpstg-h-4 wpstg-w-4', 2.2); ?></span>
                        <div class="wpstg-min-w-0">
                            <h3 class="wpstg-update-hero__title"><?php esc_html_e('This will overwrite selected staging data', 'wp-staging'); ?></h3>
                            <p class="wpstg-update-hero__body">
                                <?php echo wp_kses_post(sprintf(
                                    /* translators: %s: staging site name */
                                    __('Selected tables and files from your live site will replace matching data on %s. This cannot be undone. Unselected items stay unchanged.', 'wp-staging'),
                                    '<span class="wpstg-font-semibold">' . esc_html($stagingSiteName) . '</span>'
                                )); ?>
                            </p>
                        </div>
                    </div>
                </section>

                <section class="wpstg-update-section">
                    <div class="wpstg-create-accordion wpstg-staging-accordion">
                    <div class="wpstg-create-accordion-card wpstg-staging-accordion-card wpstg-update-overwrite-card">
                        <a href="#" class="wpstg-tab-header wpstg-create-accordion-header wpstg-staging-accordion-header" data-id="#wpstg-update-overwrite-panel" data-collapsed="true" role="button" aria-expanded="false" aria-controls="wpstg-update-overwrite-panel">
                            <span class="wpstg-create-accordion-icon wpstg-staging-accordion-icon" aria-hidden="true"><?php $setupRenderer->icon('copy'); ?></span>
                            <span class="wpstg-min-w-0 wpstg-flex-1">
                                <span class="wpstg-flex wpstg-flex-wrap wpstg-items-center wpstg-gap-2">
                                    <strong><?php esc_html_e('What to copy', 'wp-staging'); ?></strong>
                                    <?php if ($hasSavedSelection) : ?>
                                        <span class="wpstg-create-pill wpstg-create-pill--soft"><?php esc_html_e('Saved selection', 'wp-staging'); ?></span>
                                    <?php else : ?>
                                        <span class="wpstg-create-pill wpstg-create-pill--slate"><?php esc_html_e('All selected', 'wp-staging'); ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="wpstg-create-accordion-description" data-wpstg-update-overwrite-summary><?php esc_html_e('Open to choose what to overwrite', 'wp-staging'); ?></span>
                            </span>
                            <span class="wpstg-create-accordion-chevron wpstg-staging-accordion-chevron" aria-hidden="true"><?php $setupRenderer->icon('chevron', 'wpstg-h-4 wpstg-w-4'); ?></span>
                        </a>
                        <div class="wpstg-create-accordion-panel wpstg-staging-accordion-panel wpstg-collapse-panel" id="wpstg-update-overwrite-panel" style="display: none;" aria-hidden="true">
                            <p class="wpstg-update-overwrite-helper"><?php esc_html_e('Your previous selection is saved. Select which live site tables and folders should be copied to the staging site. Change this only if you want to overwrite different staging tables or files.', 'wp-staging'); ?></p>

                            <div class="wpstg-update-subblock">
                                <div class="wpstg-update-subblock__label"><?php $setupRenderer->icon('database', 'wpstg-h-[15px] wpstg-w-[15px]'); ?><?php esc_html_e('Database tables', 'wp-staging'); ?></div>
                                <div class="wpstg-update-list-head">
                                    <span class="wpstg-update-list-count" data-wpstg-update-tables-count></span>
                                    <span class="wpstg-update-list-actions">
                                        <button type="button" class="wpstg-update-select-all-tables"><?php esc_html_e('Select all live tables', 'wp-staging'); ?></button>
                                        <span class="wpstg-update-list-sep" aria-hidden="true">·</span>
                                        <button type="button" class="wpstg-update-deselect-all-tables"><?php esc_html_e('Deselect all', 'wp-staging'); ?></button>
                                    </span>
                                </div>
                                <fieldset id="wpstg-setup-tables" class="wpstg-update-selection wpstg-update-selection--tables">
                                    <?php $tableScanner->renderTablesSelection(); ?>
                                </fieldset>
                                <p class="wpstg-update-critical-note" data-wpstg-update-critical-note data-note-both="<?php esc_attr_e('wp_users and wp_options affect logins, users, and site settings.', 'wp-staging'); ?>" data-note-users="<?php esc_attr_e('wp_users affects logins and user accounts.', 'wp-staging'); ?>" data-note-options="<?php esc_attr_e('wp_options affects site settings and configuration.', 'wp-staging'); ?>" style="display:none;"><?php $setupRenderer->icon('warning', 'wpstg-h-[13px] wpstg-w-[13px]'); ?><span data-wpstg-update-critical-text></span></p>
                            </div>

                            <div class="wpstg-update-subblock">
                                <div class="wpstg-update-subblock__label"><?php $setupRenderer->icon('folder', 'wpstg-h-[15px] wpstg-w-[15px]'); ?><?php esc_html_e('Files & folders', 'wp-staging'); ?></div>
                                <div class="wpstg-update-list-head">
                                    <span class="wpstg-update-list-count" data-wpstg-update-folders-count></span>
                                    <span class="wpstg-update-list-actions">
                                        <button type="button" class="wpstg-update-select-all-files"><?php esc_html_e('Select all', 'wp-staging'); ?></button>
                                        <span class="wpstg-update-list-sep" aria-hidden="true">·</span>
                                        <button type="button" class="wpstg-update-deselect-all-files"><?php esc_html_e('Deselect all', 'wp-staging'); ?></button>
                                    </span>
                                </div>
                                <fieldset id="wpstg-setup-files" class="wpstg-update-selection wpstg-update-selection--files">
                                    <?php $directoryScanner->renderFilesSelection(); ?>
                                </fieldset>
                            </div>

                            <p class="wpstg-update-saved-line"><?php $setupRenderer->icon('check', 'wpstg-h-3 wpstg-w-3'); ?><?php esc_html_e('Selection saved for the next update.', 'wp-staging'); ?></p>
                        </div>
                    </div>
                    </div>
                </section>

                <section class="wpstg-update-section">
                    <div class="wpstg-create-accordion wpstg-staging-accordion">
                    <div class="wpstg-create-accordion-card wpstg-staging-accordion-card wpstg-update-advanced-card">
                        <a href="#" class="wpstg-tab-header wpstg-create-accordion-header wpstg-staging-accordion-header" data-id="#wpstg-update-advanced-panel" data-collapsed="true" role="button" aria-expanded="false" aria-controls="wpstg-update-advanced-panel">
                            <span class="wpstg-create-accordion-icon wpstg-staging-accordion-icon" aria-hidden="true"><?php $setupRenderer->icon('sliders'); ?></span>
                            <span class="wpstg-min-w-0 wpstg-flex-1">
                                <span class="wpstg-flex wpstg-flex-wrap wpstg-items-center wpstg-gap-2">
                                    <strong><?php esc_html_e('Advanced options', 'wp-staging'); ?></strong>
                                    <span class="wpstg-create-pill wpstg-create-pill--slate wpstg-update-advanced-badge"><span data-wpstg-staging-engine-summary><?php echo esc_html($selectedEngineName); ?></span><span><?php echo esc_html_x('engine', 'follows the engine name in the advanced-options badge, e.g. "Classic engine"', 'wp-staging'); ?></span></span>
                                </span>
                                <span class="wpstg-create-accordion-description"><?php esc_html_e('Engine, emails, scheduler and cleanup behavior', 'wp-staging'); ?></span>
                            </span>
                            <span class="wpstg-create-accordion-chevron wpstg-staging-accordion-chevron" aria-hidden="true"><?php $setupRenderer->icon('chevron', 'wpstg-h-4 wpstg-w-4'); ?></span>
                        </a>
                        <div class="wpstg-create-accordion-panel wpstg-staging-accordion-panel wpstg-collapse-panel" id="wpstg-update-advanced-panel" style="display: none;" aria-hidden="true">
                            <div class="wpstg-update-adv-groups">

                                <div class="wpstg-update-adv-group">
                                    <div class="wpstg-update-adv-group__label"><?php esc_html_e('Copy method', 'wp-staging'); ?></div>
                                    <?php
                                    $selectorClass = 'wpstg-update-engine-selector';
                                    require WPSTG_VIEWS_DIR . 'staging/_partials/staging-engine-selector-modal.php';
                                    ?>
                                </div>

                                <div class="wpstg-update-adv-group">
                                    <div class="wpstg-update-adv-group__label"><?php esc_html_e('Notifications and automation', 'wp-staging'); ?></div>
                                    <div class="wpstg-update-adv-rows">
                                        <?php foreach ($advNotifyOptions as $advOption) : ?>
                                            <div class="wpstg-update-adv-row">
                                                <label class="wpstg-update-adv-row__main" for="<?php echo esc_attr($advOption['id']); ?>">
                                                    <?php Checkbox::render($advOption['id'], $advOption['id'], 'true', $advOption['checked'], ['usePrimitive' => true, 'isDisabled' => $advOptionDisabled, 'classes' => 'wpstg-update-adv-option']); ?>
                                                    <span class="wpstg-update-adv-row__icon" aria-hidden="true"><?php $setupRenderer->icon($advOption['icon'], 'wpstg-h-[15px] wpstg-w-[15px]'); ?></span>
                                                    <span class="wpstg-update-adv-row__label">
                                                        <?php echo esc_html($advOption['label']); ?>
                                                        <?php if ($advOptionDisabled) : ?>
                                                            <span class="wpstg-badge-amber wpstg-flex-shrink-0"><?php $setupRenderer->icon('lock', 'wpstg-h-3 wpstg-w-3'); ?><?php esc_html_e('Pro', 'wp-staging'); ?></span>
                                                        <?php endif; ?>
                                                    </span>
                                                </label>
                                                <button type="button" class="wpstg--tooltip wpstg-update-infotip" aria-label="<?php echo esc_attr($advOption['tip']); ?>">
                                                    <?php $setupRenderer->icon('info', 'wpstg-h-[15px] wpstg-w-[15px]'); ?>
                                                    <span class="wpstg--tooltiptext"><?php echo esc_html($advOption['tip']); ?></span>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="wpstg-update-adv-group">
                                    <div class="wpstg-update-adv-group__label"><?php esc_html_e('Cleanup', 'wp-staging'); ?></div>
                                    <div class="wpstg-update-adv-cleanup">
                                        <?php foreach ($advCleanupOptions as $advOption) : ?>
                                            <label class="wpstg-update-clean-card" for="<?php echo esc_attr($advOption['id']); ?>">
                                                <?php Checkbox::render($advOption['id'], $advOption['id'], 'true', $advOption['checked'], ['usePrimitive' => true, 'isDisabled' => $advOption['disabled'], 'classes' => 'wpstg-update-adv-option']); ?>
                                                <span class="wpstg-update-clean-card__body">
                                                    <span class="wpstg-update-clean-card__title"><?php $setupRenderer->icon('trash', 'wpstg-update-clean-card__icon wpstg-h-[15px] wpstg-w-[15px]'); ?><?php echo esc_html($advOption['label']); ?></span>
                                                    <span class="wpstg-update-clean-card__desc"><?php echo esc_html($advOption['desc']); ?></span>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                            </div>

                            <div class="wpstg-update-hidden-config" aria-hidden="true" style="display: none;">
                                <?php Checkbox::render('wpstg_enable_cron', 'wpstg_enable_cron', 'true', $isProLicenseActive, ['usePrimitive' => true, 'isDisabled' => $advOptionDisabled]); ?>
                            </div>
                        </div>
                    </div>
                    </div>
                </section>
            </main>

            <aside class="wpstg-update-setup-modal__summary" aria-label="<?php esc_attr_e('Update Summary', 'wp-staging'); ?>">
                <div class="wpstg-update-summary-sticky">
                    <h2 class="wpstg-update-summary__heading"><?php $setupRenderer->icon('clipboard', 'wpstg-update-summary__heading-icon'); ?><?php esc_html_e('Update Summary', 'wp-staging'); ?></h2>
                    <dl class="wpstg-update-summary-list">
                        <div class="wpstg-update-summary-row">
                            <dt><?php $setupRenderer->icon('server', 'wpstg-update-summary__icon'); ?><?php esc_html_e('Target', 'wp-staging'); ?></dt>
                            <dd class="wpstg-update-summary-mono"><?php echo esc_html($stagingSiteName); ?></dd>
                        </div>
                        <div class="wpstg-update-summary-row">
                            <dt><?php $setupRenderer->icon('globe', 'wpstg-update-summary__icon'); ?><?php esc_html_e('Source', 'wp-staging'); ?></dt>
                            <dd><?php esc_html_e('Live site', 'wp-staging'); ?></dd>
                        </div>
                        <hr class="wpstg-update-summary-divider" />
                        <div class="wpstg-update-summary-row">
                            <dt><?php $setupRenderer->icon('database', 'wpstg-update-summary__icon'); ?><?php esc_html_e('Tables', 'wp-staging'); ?></dt>
                            <dd data-wpstg-update-summary-tables></dd>
                        </div>
                        <div class="wpstg-update-summary-row">
                            <dt><?php $setupRenderer->icon('folder', 'wpstg-update-summary__icon'); ?><?php esc_html_e('Folders', 'wp-staging'); ?></dt>
                            <dd data-wpstg-update-summary-folders></dd>
                        </div>
                        <div class="wpstg-update-summary-row">
                            <dt><?php $setupRenderer->icon('disk', 'wpstg-update-summary__icon'); ?><?php esc_html_e('Data to overwrite', 'wp-staging'); ?></dt>
                            <dd class="wpstg-update-summary-mono wpstg-update-summary-size">
                                <span class="wpstg-create-disk-status">
                                    <span class="wpstg-create-disk-spinner" data-wpstg-disk-spinner aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.2"></circle><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path></svg>
                                    </span>
                                    <strong data-wpstg-disk-space-status data-status-checking="<?php esc_attr_e('Calculating…', 'wp-staging'); ?>" data-status-failed="<?php esc_attr_e('Check failed', 'wp-staging'); ?>">—</strong>
                                </span>
                                <span class="wpstg-create-disk-total" style="display:none;"><span data-wpstg-disk-space-value></span></span>
                            </dd>
                        </div>
                        <hr class="wpstg-update-summary-divider" data-wpstg-update-options-divider<?php echo !$advAnyEnabled ? ' style="display:none;"' : ''; ?> />
                        <?php foreach ($advSummaryOptions as $advOption) : ?>
                            <div class="wpstg-update-summary-row" data-wpstg-update-option-row="<?php echo esc_attr($advOption['id']); ?>"<?php echo !$advOption['checked'] ? ' style="display:none;"' : ''; ?>>
                                <dt><?php $setupRenderer->icon($advOption['icon'], 'wpstg-update-summary__icon'); ?><?php echo esc_html($advOption['sumLabel']); ?></dt>
                                <dd class="<?php echo esc_attr($advOption['risk'] ? 'wpstg-update-summary-on-risk' : ''); ?>"><?php esc_html_e('On', 'wp-staging'); ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                    <a href="#" id="wpstg-check-space" class="wpstg-hidden" aria-hidden="true" tabindex="-1"><?php esc_html_e('Recalculate size', 'wp-staging'); ?></a>
                    <div id="wpstg-disk-space-result" class="wpstg-update-summary-disk-msg" style="display:none;"><div id="wpstg-disk-space-result-msg"></div></div>

                    <div class="wpstg-update-backup-card">
                        <div class="wpstg-update-backup-card__head">
                            <?php $setupRenderer->icon('archive', 'wpstg-update-backup-card__icon'); ?>
                            <strong><?php esc_html_e('Back up staging first', 'wp-staging'); ?></strong>
                            <span class="wpstg-update-backup-card__pill"><?php esc_html_e('Recommended', 'wp-staging'); ?></span>
                        </div>
                        <p class="wpstg-update-backup-card__body">
                            <?php esc_html_e('WP STAGING can\'t back up automatically. Open the staging site and create a backup first.', 'wp-staging'); ?>
                        </p>
                        <a href="<?php echo esc_url($stagingSiteUrl); ?>" target="_blank" rel="noopener noreferrer" class="wpstg-update-backup-card__button">
                            <?php $setupRenderer->icon('external', 'wpstg-h-[13px] wpstg-w-[13px]'); ?><?php esc_html_e('Open staging site', 'wp-staging'); ?>
                        </a>
                    </div>
                </div>
            </aside>
        </div>

        <footer class="wpstg-update-setup-modal__footer">
            <label class="wpstg-update-confirm-row" for="wpstg-update-review-confirmation">
                <?php Checkbox::render('wpstg-update-review-confirmation', 'wpstg-update-review-confirmation', '1', false, ['classes' => 'wpstg-update-review-confirmation-checkbox', 'usePrimitive' => true]); ?>
                <span class="wpstg-update-confirm-row__text">
                    <?php echo wp_kses_post(sprintf(
                        /* translators: %1$s: the word "overwrite" (emphasized); %2$s: staging site name */
                        __('I understand this will %1$s selected data on %2$s.', 'wp-staging'),
                        '<span class="wpstg-update-confirm-strong">' . esc_html__('overwrite', 'wp-staging') . '</span>',
                        '<span class="wpstg-update-confirm-site">' . esc_html($stagingSiteName) . '</span>'
                    )); ?>
                </span>
            </label>
            <div class="wpstg-update-footer-actions">
                <button type="button" class="wpstg-update-modal-cancel wpstg-btn wpstg-btn-md wpstg-h-11 wpstg-rounded-lg wpstg-py-0 wpstg-leading-none wpstg-btn-secondary wpstg-px-5"><?php esc_html_e('Cancel', 'wp-staging'); ?></button>
                <button type="button" class="wpstg--update--staging-site wpstg-setup-cta wpstg-setup-cta--red" data-url="<?php echo esc_attr($stagingSiteUrl); ?>" data-wpstg-update-confirmed="true" disabled>
                    <?php $setupRenderer->icon('refresh', 'wpstg-h-4 wpstg-w-4', 2); ?><?php esc_html_e('Update Staging Site', 'wp-staging'); ?>
                </button>
            </div>
        </footer>
    </div>
<?php elseif ($isReset) :
    $stagingSiteDisplay = preg_replace('#^https?://#', '', untrailingslashit($stagingSiteUrl));
    $hasSavedSelection  = !empty($stagingSiteDto->getIncludedTables()) || !empty($stagingSiteDto->getExcludedDirectories());
    ?>
    <div class="wpstg-reset-setup-modal wpstg-update-setup-modal wpstg-create-setup-modal wpstg-staging-setup-modal wpstg-text-left" role="dialog" aria-modal="true" aria-labelledby="wpstg-reset-modal-title" data-update-none="<?php esc_attr_e('None', 'wp-staging'); ?>" data-update-empty-summary="<?php esc_attr_e('Nothing selected — open to choose what to reset', 'wp-staging'); ?>" data-update-tables-count="<?php esc_attr_e('%1$s of %2$s tables · %3$s', 'wp-staging'); ?>" data-update-folders-count="<?php esc_attr_e('%1$s of %2$s folders', 'wp-staging'); ?>" data-update-of="<?php esc_attr_e('%1$s of %2$s', 'wp-staging'); ?>" data-update-summary-line="<?php esc_attr_e('%1$s tables · %2$s folders', 'wp-staging'); ?>">
        <?php $setupRenderer->closeButton('wpstg-reset-modal-close wpstg-staging-modal-close'); ?>

        <header class="wpstg-update-setup-modal__header">
            <span class="wpstg-update-header-badge" aria-hidden="true"><?php $setupRenderer->icon('refresh', 'wpstg-h-5 wpstg-w-5'); ?></span>
            <div class="wpstg-min-w-0 wpstg-flex-1">
                <h1 id="wpstg-reset-modal-title" class="wpstg-update-header-title"><?php esc_html_e('Reset Staging Site', 'wp-staging'); ?></h1>
                <p class="wpstg-update-header-subtitle"><?php esc_html_e('Rebuild this staging site from the current state of your production site.', 'wp-staging'); ?></p>
            </div>
        </header>

        <div class="wpstg-update-setup-modal__body">
            <main class="wpstg-update-setup-modal__main">

                <section class="wpstg-update-section">
                    <h2 class="wpstg-update-section__title"><?php esc_html_e('Staging site to reset', 'wp-staging'); ?></h2>
                    <div class="wpstg-update-direction">
                        <div class="wpstg-update-direction__node">
                            <span class="wpstg-update-direction__label"><?php esc_html_e('Source — production', 'wp-staging'); ?></span>
                            <span class="wpstg-update-direction__name"><?php $setupRenderer->icon('globe', 'wpstg-update-direction__icon'); ?><span class="wpstg-truncate"><?php esc_html_e('Production Site', 'wp-staging'); ?></span></span>
                            <span class="wpstg-update-direction__url"><?php echo esc_html($productionSiteHost); ?></span>
                        </div>
                        <div class="wpstg-update-direction__arrow" aria-hidden="true">
                            <?php $setupRenderer->icon('arrow-right', 'wpstg-h-[18px] wpstg-w-[18px]', 2); ?>
                            <span class="wpstg-update-direction__arrow-label"><?php esc_html_e('resets', 'wp-staging'); ?></span>
                        </div>
                        <div class="wpstg-update-direction__node wpstg-update-direction__node--target">
                            <span class="wpstg-update-direction__label"><?php esc_html_e('Target — staging', 'wp-staging'); ?></span>
                            <span class="wpstg-update-direction__name"><?php $setupRenderer->icon('server', 'wpstg-update-direction__icon wpstg-update-direction__icon--target'); ?><span class="wpstg-truncate"><?php echo esc_html($stagingSiteName); ?></span></span>
                            <span class="wpstg-update-direction__url"><?php echo esc_html($stagingSiteDisplay); ?></span>
                        </div>
                    </div>
                </section>

                <section class="wpstg-update-section">
                    <div class="wpstg-update-hero">
                        <span class="wpstg-update-hero__icon" aria-hidden="true"><?php $setupRenderer->icon('warning', 'wpstg-h-4 wpstg-w-4', 2.2); ?></span>
                        <div class="wpstg-min-w-0">
                            <h3 class="wpstg-update-hero__title"><?php esc_html_e('This rebuilds the staging site from scratch', 'wp-staging'); ?></h3>
                            <p class="wpstg-update-hero__body">
                                <?php echo wp_kses_post(sprintf(
                                    /* translators: %s: staging site name */
                                    __('%s is recreated from the current production state using its original name and URL. The entire staging database is deleted, then only the tables you select are copied over. Every change on the staging site — including its settings and wp-config.php — is lost. This cannot be undone.', 'wp-staging'),
                                    '<span class="wpstg-font-semibold">' . esc_html($stagingSiteName) . '</span>'
                                )); ?>
                            </p>
                        </div>
                    </div>
                </section>

                <section class="wpstg-update-section">
                    <div class="wpstg-create-accordion wpstg-staging-accordion">
                    <div class="wpstg-create-accordion-card wpstg-staging-accordion-card wpstg-update-overwrite-card">
                        <a href="#" class="wpstg-tab-header wpstg-create-accordion-header wpstg-staging-accordion-header" data-id="#wpstg-reset-overwrite-panel" data-collapsed="true" role="button" aria-expanded="false" aria-controls="wpstg-reset-overwrite-panel">
                            <span class="wpstg-create-accordion-icon wpstg-staging-accordion-icon" aria-hidden="true"><?php $setupRenderer->icon('copy'); ?></span>
                            <span class="wpstg-min-w-0 wpstg-flex-1">
                                <span class="wpstg-flex wpstg-flex-wrap wpstg-items-center wpstg-gap-2">
                                    <strong><?php esc_html_e('What to copy', 'wp-staging'); ?></strong>
                                    <?php if ($hasSavedSelection) : ?>
                                        <span class="wpstg-create-pill wpstg-create-pill--soft"><?php esc_html_e('Saved selection', 'wp-staging'); ?></span>
                                    <?php else : ?>
                                        <span class="wpstg-create-pill wpstg-create-pill--slate"><?php esc_html_e('Preselected defaults', 'wp-staging'); ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="wpstg-create-accordion-description" data-wpstg-update-overwrite-summary><?php esc_html_e('Open to choose what to reset', 'wp-staging'); ?></span>
                            </span>
                            <span class="wpstg-create-accordion-chevron wpstg-staging-accordion-chevron" aria-hidden="true"><?php $setupRenderer->icon('chevron', 'wpstg-h-4 wpstg-w-4'); ?></span>
                        </a>
                        <div class="wpstg-create-accordion-panel wpstg-staging-accordion-panel wpstg-collapse-panel" id="wpstg-reset-overwrite-panel" style="display: none;" aria-hidden="true">
                            <p class="wpstg-update-overwrite-helper">
                                <?php if ($hasSavedSelection) : ?>
                                    <?php esc_html_e('Your previous selection is saved. Select which live site tables and folders should be copied to the staging site. Change this only if you want to overwrite different staging tables or files.', 'wp-staging'); ?>
                                <?php else : ?>
                                    <?php esc_html_e('Everything is selected by default. Narrow it down if you only want to reset specific tables or folders.', 'wp-staging'); ?>
                                <?php endif; ?>
                            </p>

                            <div class="wpstg-update-subblock">
                                <div class="wpstg-update-subblock__label"><?php $setupRenderer->icon('database', 'wpstg-h-[15px] wpstg-w-[15px]'); ?><?php esc_html_e('Database tables', 'wp-staging'); ?></div>
                                <div class="wpstg-update-list-head">
                                    <span class="wpstg-update-list-count" data-wpstg-update-tables-count></span>
                                    <span class="wpstg-update-list-actions">
                                        <button type="button" class="wpstg-update-select-all-tables"><?php esc_html_e('Select all live tables', 'wp-staging'); ?></button>
                                        <span class="wpstg-update-list-sep" aria-hidden="true">·</span>
                                        <button type="button" class="wpstg-update-deselect-all-tables"><?php esc_html_e('Deselect all', 'wp-staging'); ?></button>
                                    </span>
                                </div>
                                <fieldset id="wpstg-setup-tables" class="wpstg-update-selection wpstg-update-selection--tables">
                                    <?php $tableScanner->renderTablesSelection(); ?>
                                </fieldset>
                                <p class="wpstg-update-critical-note" data-wpstg-update-critical-note data-note-both="<?php esc_attr_e('wp_users and wp_options affect logins, users, and site settings.', 'wp-staging'); ?>" data-note-users="<?php esc_attr_e('wp_users affects logins and user accounts.', 'wp-staging'); ?>" data-note-options="<?php esc_attr_e('wp_options affects site settings and configuration.', 'wp-staging'); ?>" style="display:none;"><?php $setupRenderer->icon('warning', 'wpstg-h-[13px] wpstg-w-[13px]'); ?><span data-wpstg-update-critical-text></span></p>
                            </div>

                            <div class="wpstg-update-subblock">
                                <div class="wpstg-update-subblock__label"><?php $setupRenderer->icon('folder', 'wpstg-h-[15px] wpstg-w-[15px]'); ?><?php esc_html_e('Files & folders', 'wp-staging'); ?></div>
                                <div class="wpstg-update-list-head">
                                    <span class="wpstg-update-list-count" data-wpstg-update-folders-count></span>
                                    <span class="wpstg-update-list-actions">
                                        <button type="button" class="wpstg-update-select-all-files"><?php esc_html_e('Select all', 'wp-staging'); ?></button>
                                        <span class="wpstg-update-list-sep" aria-hidden="true">·</span>
                                        <button type="button" class="wpstg-update-deselect-all-files"><?php esc_html_e('Deselect all', 'wp-staging'); ?></button>
                                    </span>
                                </div>
                                <fieldset id="wpstg-setup-files" class="wpstg-update-selection wpstg-update-selection--files">
                                    <?php $directoryScanner->renderFilesSelection(); ?>
                                </fieldset>
                                <p class="wpstg-m-0 wpstg-mt-2 wpstg-flex wpstg-items-start wpstg-gap-1.5 wpstg-text-[12px] wpstg-leading-snug wpstg-text-gray-500 dark:wpstg-text-slate-400">
                                    <?php $setupRenderer->icon('folder', 'wpstg-h-[13px] wpstg-w-[13px] wpstg-mt-0.5 wpstg-flex-shrink-0'); ?>
                                    <span><?php echo wp_kses_post(sprintf(
                                        /* translators: %s: the file name wp-config.php, shown in a monospace font. */
                                        __('Root files including %s are reset too.', 'wp-staging'),
                                        '<span class="wpstg-font-mono">wp-config.php</span>'
                                    )); ?></span>
                                </p>
                            </div>

                            <p class="wpstg-update-saved-line"><?php $setupRenderer->icon('check', 'wpstg-h-3 wpstg-w-3'); ?><?php esc_html_e('Selection saved for the next reset.', 'wp-staging'); ?></p>
                        </div>
                    </div>
                    </div>
                </section>

                <section class="wpstg-update-section">
                    <div class="wpstg-create-accordion wpstg-staging-accordion">
                        <?php $setupRenderer->engineSection($setupMode, $enginePanelId); ?>
                    </div>
                </section>
            </main>

            <aside class="wpstg-update-setup-modal__summary" aria-label="<?php esc_attr_e('Reset Summary', 'wp-staging'); ?>">
                <div class="wpstg-update-summary-sticky">
                    <h2 class="wpstg-update-summary__heading"><?php $setupRenderer->icon('clipboard', 'wpstg-update-summary__heading-icon'); ?><?php esc_html_e('Reset Summary', 'wp-staging'); ?></h2>
                    <dl class="wpstg-update-summary-list">
                        <div class="wpstg-update-summary-row">
                            <dt><?php $setupRenderer->icon('server', 'wpstg-update-summary__icon'); ?><?php esc_html_e('Target', 'wp-staging'); ?></dt>
                            <dd class="wpstg-update-summary-mono"><?php echo esc_html($stagingSiteName); ?></dd>
                        </div>
                        <div class="wpstg-update-summary-row">
                            <dt><?php $setupRenderer->icon('globe', 'wpstg-update-summary__icon'); ?><?php esc_html_e('Source', 'wp-staging'); ?></dt>
                            <dd><?php esc_html_e('Production site', 'wp-staging'); ?></dd>
                        </div>
                        <hr class="wpstg-update-summary-divider" />
                        <div class="wpstg-update-summary-row">
                            <dt><?php $setupRenderer->icon('database', 'wpstg-update-summary__icon'); ?><?php esc_html_e('Tables', 'wp-staging'); ?></dt>
                            <dd data-wpstg-update-summary-tables></dd>
                        </div>
                        <div class="wpstg-update-summary-row">
                            <dt><?php $setupRenderer->icon('folder', 'wpstg-update-summary__icon'); ?><?php esc_html_e('Folders', 'wp-staging'); ?></dt>
                            <dd data-wpstg-update-summary-folders></dd>
                        </div>
                        <div class="wpstg-update-summary-row">
                            <dt><?php $setupRenderer->icon('disk', 'wpstg-update-summary__icon'); ?><?php esc_html_e('Data to reset', 'wp-staging'); ?></dt>
                            <dd class="wpstg-update-summary-mono wpstg-update-summary-size">
                                <span class="wpstg-create-disk-status">
                                    <span class="wpstg-create-disk-spinner" data-wpstg-disk-spinner aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.2"></circle><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path></svg>
                                    </span>
                                    <strong data-wpstg-disk-space-status data-status-checking="<?php esc_attr_e('Calculating…', 'wp-staging'); ?>" data-status-failed="<?php esc_attr_e('Check failed', 'wp-staging'); ?>">—</strong>
                                </span>
                                <span class="wpstg-create-disk-total" style="display:none;"><span data-wpstg-disk-space-value></span></span>
                            </dd>
                        </div>
                    </dl>
                    <a href="#" id="wpstg-check-space" class="wpstg-hidden" aria-hidden="true" tabindex="-1"><?php esc_html_e('Recalculate size', 'wp-staging'); ?></a>
                    <div id="wpstg-disk-space-result" class="wpstg-update-summary-disk-msg" style="display:none;"><div id="wpstg-disk-space-result-msg"></div></div>

                    <div class="wpstg-update-backup-card">
                        <div class="wpstg-update-backup-card__head">
                            <?php $setupRenderer->icon('archive', 'wpstg-update-backup-card__icon'); ?>
                            <strong><?php esc_html_e('Back up staging first', 'wp-staging'); ?></strong>
                            <span class="wpstg-update-backup-card__pill"><?php esc_html_e('Recommended', 'wp-staging'); ?></span>
                        </div>
                        <p class="wpstg-update-backup-card__body">
                            <?php echo wp_kses_post(sprintf(
                                /* translators: %s: staging site name */
                                __('This reset cannot create a backup automatically. Open %s and create a WP STAGING backup before continuing.', 'wp-staging'),
                                '<span class="wpstg-font-semibold">' . esc_html($stagingSiteName) . '</span>'
                            )); ?>
                        </p>
                        <a href="<?php echo esc_url($stagingSiteUrl); ?>" target="_blank" rel="noopener noreferrer" class="wpstg-update-backup-card__button">
                            <?php $setupRenderer->icon('external', 'wpstg-h-[13px] wpstg-w-[13px]'); ?><?php esc_html_e('Open staging site', 'wp-staging'); ?>
                        </a>
                    </div>
                </div>
            </aside>
        </div>

        <footer class="wpstg-update-setup-modal__footer">
            <label class="wpstg-update-confirm-row" for="wpstg-update-review-confirmation">
                <?php Checkbox::render('wpstg-update-review-confirmation', 'wpstg-update-review-confirmation', '1', false, ['classes' => 'wpstg-update-review-confirmation-checkbox', 'usePrimitive' => true]); ?>
                <span class="wpstg-update-confirm-row__text">
                    <?php echo wp_kses_post(sprintf(
                        /* translators: %1$s: the word "rebuilds" (emphasized); %2$s: staging site name */
                        __('I understand this %1$s %2$s and permanently deletes my staging changes.', 'wp-staging'),
                        '<span class="wpstg-update-confirm-strong">' . esc_html__('rebuilds', 'wp-staging') . '</span>',
                        '<span class="wpstg-update-confirm-site">' . esc_html($stagingSiteName) . '</span>'
                    )); ?>
                </span>
            </label>
            <div class="wpstg-update-footer-actions">
                <button type="button" class="wpstg-reset-modal-cancel wpstg-btn wpstg-btn-md wpstg-h-11 wpstg-rounded-lg wpstg-py-0 wpstg-leading-none wpstg-btn-secondary wpstg-px-5"><?php esc_html_e('Cancel', 'wp-staging'); ?></button>
                <button type="button" class="wpstg--reset--staging-site wpstg-setup-cta wpstg-setup-cta--red" data-url="<?php echo esc_attr($stagingSiteUrl); ?>" disabled>
                    <?php $setupRenderer->icon('refresh', 'wpstg-h-4 wpstg-w-4', 2); ?><?php esc_html_e('Reset Staging Site', 'wp-staging'); ?>
                </button>
            </div>
        </footer>
    </div>
<?php else : ?>
    <div
        class="wpstg-create-setup-modal wpstg-staging-setup-modal wpstg-text-left"
        role="dialog"
        aria-modal="true"
        aria-labelledby="wpstg-create-modal-title"
        data-engine-legacy-label="<?php esc_attr_e('Classic', 'wp-staging'); ?>"
        data-engine-next-gen-label="<?php esc_attr_e('Next-Gen', 'wp-staging'); ?>"
        data-engine-legacy-suffix="<?php esc_attr_e(' Engine - faster one available', 'wp-staging'); ?>"
        data-engine-next-gen-suffix="<?php esc_attr_e(' Engine - recommended', 'wp-staging'); ?>"
        data-summary-enabled="<?php esc_attr_e('Enabled', 'wp-staging'); ?>"
        data-summary-disabled="<?php esc_attr_e('Disabled', 'wp-staging'); ?>"
        data-summary-emails-enabled="<?php esc_attr_e('Enabled', 'wp-staging'); ?>"
        data-summary-emails-disabled="<?php esc_attr_e('Disabled', 'wp-staging'); ?>"
        data-summary-emails-enabled-tooltip="<?php echo esc_attr($runtimeSummaryTooltips['emails']['enabled']); ?>"
        data-summary-emails-disabled-tooltip="<?php echo esc_attr($runtimeSummaryTooltips['emails']['disabled']); ?>"
        data-summary-cron-enabled="<?php esc_attr_e('Enabled', 'wp-staging'); ?>"
        data-summary-cron-disabled="<?php esc_attr_e('Disabled', 'wp-staging'); ?>"
        data-summary-cron-enabled-tooltip="<?php echo esc_attr($runtimeSummaryTooltips['cron']['enabled']); ?>"
        data-summary-cron-disabled-tooltip="<?php echo esc_attr($runtimeSummaryTooltips['cron']['disabled']); ?>"
        data-summary-woo-enabled="<?php esc_attr_e('Enabled', 'wp-staging'); ?>"
        data-summary-woo-disabled="<?php esc_attr_e('Disabled', 'wp-staging'); ?>"
        data-summary-woo-enabled-tooltip="<?php echo esc_attr($runtimeSummaryTooltips['woo']['enabled']); ?>"
        data-summary-woo-disabled-tooltip="<?php echo esc_attr($runtimeSummaryTooltips['woo']['disabled']); ?>"
        data-copy-tables-selected-automatically="<?php esc_attr_e('%s tables selected automatically.', 'wp-staging'); ?>"
        data-copy-tables-summary="<?php esc_attr_e('%s tables', 'wp-staging'); ?>"
        data-copy-files-summary="<?php esc_attr_e('%s core folders excluded', 'wp-staging'); ?>"
        data-copy-files-summary-empty="<?php esc_attr_e('All folders selected', 'wp-staging'); ?>"
    >
        <?php $setupRenderer->closeButton('wpstg-create-modal-close wpstg-staging-modal-close'); ?>
        <?php $setupRenderer->modalHeader(__('Create Staging Site', 'wp-staging'), __('Create a safe copy of your live site for testing changes.', 'wp-staging'), 'wpstg-create-modal-title', '', '', '', 'copy'); ?>
        <?php
        $setupRenderer->configurationBody([
            'isCreate'                 => $isCreate,
            'isUpdate'                 => $isUpdate,
            'isReset'                  => $isReset,
            'isProLicenseActive'       => $isProLicenseActive,
            'isProBuild'               => $isPro,
            'setupMode'                => $setupMode,
            'stagingSetup'             => $stagingSetup,
            'stagingSiteDto'           => $stagingSiteDto,
            'directoryScanner'         => $directoryScanner,
            'tableScanner'             => $tableScanner,
            'stagingSiteName'          => $stagingSiteName,
            'defaultSiteName'          => $defaultSiteName,
            'productionSiteUrl'        => $productionSiteUrl,
            'selectedEngineName'       => $selectedEngineName,
            'enginePanelId'            => $enginePanelId,
            'showWooSchedulerSettings' => $showWooSchedulerSettings,
            'runtimeSummaryTooltips'   => $runtimeSummaryTooltips,
            'defaultPathBase'          => $defaultPathBase,
        ]);
        ?>
        <?php $setupRenderer->createSetupFooter($previewSiteUrl); ?>
    </div>
<?php endif; ?>
