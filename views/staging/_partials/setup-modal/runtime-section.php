<?php

use WPStaging\Framework\Language\Language;

/**
 * Renders staging isolation/runtime controls.
 *
 * @var \WPStaging\Staging\Renderer\SetupRenderer $renderer
 * @var bool                                      $isCreate
 * @var bool                                      $isUpdate
 * @var bool                                      $isProLicenseActive
 * @var bool                                      $showWooSchedulerSettings
 * @var \WPStaging\Staging\Dto\StagingSiteDto     $stagingSiteDto
 * @var array                                     $runtimeSummaryTooltips
 */

if (!$isCreate && !$isUpdate) {
    return;
}

$panelId            = $isCreate ? 'wpstg-runtime-settings' : 'wpstg-advanced-settings';
$runtimeBadgeLocked = !$isProLicenseActive;

$renderer->accordionSection([
    'badge'       => $runtimeBadgeLocked ? __('Available in Pro', 'wp-staging') : __('Safe defaults', 'wp-staging'),
    'badgeClass'  => $runtimeBadgeLocked ? 'wpstg-badge-amber' : 'wpstg-create-pill wpstg-create-pill--green',
    'badgeIcon'   => $runtimeBadgeLocked ? 'lock' : '',
    'cardClass'   => 'wpstg-create-accordion-card wpstg-staging-accordion-card wpstg-relative wpstg-z-20 wpstg-overflow-visible',
    'description' => __('Control whether the staging site can send emails, run scheduled tasks, or trigger WooCommerce background actions.', 'wp-staging'),
    'icon'        => 'shield',
    'panelClass'  => 'wpstg-create-accordion-panel wpstg-staging-accordion-panel wpstg-collapse-panel wpstg-overflow-visible',
    'panelId'     => $panelId,
    'title'       => __('Staging isolation', 'wp-staging'),
], function () use ($renderer, $isCreate, $isProLicenseActive, $showWooSchedulerSettings, $stagingSiteDto, $runtimeSummaryTooltips) {
    // Treat an unlicensed Pro install like the free build: lock the options.
    $isPro                      = $isProLicenseActive;
    $isCreateFreeOptionDisabled = $isCreate && !$isPro;
    $isUpdateFreeOptionDisabled = !$isCreate && !$isPro;
    $updateFreeDisabledOptions  = $isUpdateFreeOptionDisabled ? ['isDisabled' => true] : [];
    ?>
    <?php if ($isCreate) : ?>
        <?php if ($isCreateFreeOptionDisabled) : ?>
            <div class="wpstg-create-runtime-upgrade">
                <?php $renderer->icon('sparkles', 'wpstg-create-runtime-upgrade__icon wpstg-h-5 wpstg-w-5'); ?>
                <div class="wpstg-create-runtime-upgrade__text">
                    <strong><?php esc_html_e('Advanced isolation controls are available in Pro.', 'wp-staging'); ?></strong>
                    <span><?php esc_html_e('Prevent staging from sending real emails, running WordPress cron, or triggering WooCommerce background actions.', 'wp-staging'); ?></span>
                </div>
                <a class="wpstg-create-runtime-upgrade__button" href="<?php echo esc_url(Language::localizePricingUrl('https://wp-staging.com/#pricing')); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Unlock isolation controls', 'wp-staging'); ?></a>
            </div>
            <div class="wpstg-create-option-stack" data-wpstg-advanced-settings-panel>
                <?php
                // The three isolation rows always render in the free build so the
                // expanded section mirrors the sidebar and the Pro upsell card,
                // even on sites where WooCommerce is not active.
                $renderer->proControlRow('wpstg_allow_emails', true, esc_html__('Email delivery', 'wp-staging'), esc_html__('Pro lets you disable outgoing emails from staging.', 'wp-staging'), esc_html__('Enabled in Free', 'wp-staging'));
                $renderer->proControlRow('wpstg_enable_cron', true, esc_html__('WordPress cron', 'wp-staging'), esc_html__('Pro lets you stop scheduled tasks from running on staging.', 'wp-staging'), esc_html__('Enabled in Free', 'wp-staging'));
                $renderer->proControlRow('wpstg_woo_scheduler_enabled', true, esc_html__('WooCommerce actions', 'wp-staging'), esc_html__('Pro lets you prevent WooCommerce background actions from running on staging.', 'wp-staging'), esc_html__('Enabled in Free', 'wp-staging'));
                ?>
                <div class="wpstg-create-automation-heading">
                    <strong><?php esc_html_e('After creation automation', 'wp-staging'); ?></strong>
                    <span><?php esc_html_e('Optional tasks that run once the staging site is created.', 'wp-staging'); ?></span>
                </div>
                <?php
                $renderer->proControlRow('wpstg_reminder_emails', false, esc_html__('Staging reminder emails', 'wp-staging'), esc_html__('Get reminders that this staging site still exists.', 'wp-staging'), '', esc_html__('Pro', 'wp-staging'));
                $renderer->proControlRow('wpstg_auto_update_plugins', false, esc_html__('Update plugins after cloning', 'wp-staging'), esc_html__('Automatically update plugins once the staging site is created.', 'wp-staging'), '', esc_html__('Pro', 'wp-staging'));
                ?>
            </div>
        <?php else : ?>
            <div class="wpstg-create-option-stack" data-wpstg-advanced-settings-panel>
                <?php
                $renderer->setupOptionCard('wpstg_allow_emails', esc_html__('Allow staging site to send real emails', 'wp-staging'), esc_html__('Disable this if you want to prevent email delivery from staging.', 'wp-staging'), !$isPro, [], '', false);
                $renderer->setupOptionCard('wpstg_enable_cron', esc_html__('Run WordPress cron on staging', 'wp-staging'), esc_html__('Disable this to prevent scheduled tasks from running on staging.', 'wp-staging'), !$isPro, [], $runtimeSummaryTooltips['cron']['enabled'], false);
                ?>
                <?php if ($showWooSchedulerSettings) : ?>
                    <?php $renderer->setupOptionCard('wpstg_woo_scheduler_enabled', esc_html__('Run WooCommerce scheduled actions on staging', 'wp-staging'), esc_html__('Disable this to prevent WooCommerce background actions on staging.', 'wp-staging'), !$isPro, [], $runtimeSummaryTooltips['woo']['enabled'], false); ?>
                <?php endif; ?>
                <div class="wpstg-create-automation-heading">
                    <strong><?php esc_html_e('After creation automation', 'wp-staging'); ?></strong>
                    <span><?php esc_html_e('Optional tasks that run once the staging site is created.', 'wp-staging'); ?></span>
                </div>
                <?php
                $renderer->setupOptionCard('wpstg_reminder_emails', esc_html__('Send reminder emails for this staging site', 'wp-staging'), esc_html__('Reminds you that the staging site still exists.', 'wp-staging'), false, [], esc_html__('You will receive an email reminder every two weeks while this staging site exists, so you can clean up unused ones and avoid piling up test environments.', 'wp-staging'), false);
                $renderer->setupOptionCard('wpstg_auto_update_plugins', esc_html__('Update plugins after cloning', 'wp-staging'), esc_html__('Useful for testing updates on staging.', 'wp-staging'), false, [], esc_html__('Updates all plugins on the staging site to their latest versions right after it is created, so you can test that everything still works. You will also get an email and Slack notification when it finishes (if enabled in WP STAGING settings).', 'wp-staging'), false);
                ?>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <div class="wpstg-create-option-stack" data-wpstg-advanced-settings-panel>
            <?php
            $renderer->setupOptionCard('wpstg_allow_emails', esc_html__('Allow staging site to send real emails', 'wp-staging'), esc_html__('Disable this if you want to prevent email delivery from staging.', 'wp-staging'), !$isUpdateFreeOptionDisabled, $updateFreeDisabledOptions, '', $isUpdateFreeOptionDisabled);
            $renderer->setupOptionCard('wpstg_enable_cron', esc_html__('Run WordPress cron on staging', 'wp-staging'), esc_html__('Disable this to prevent scheduled tasks from running on staging.', 'wp-staging'), !$isUpdateFreeOptionDisabled, $updateFreeDisabledOptions, $runtimeSummaryTooltips['cron']['enabled'], $isUpdateFreeOptionDisabled);
            ?>
            <?php if ($showWooSchedulerSettings) : ?>
                <?php $renderer->setupOptionCard('wpstg_woo_scheduler_enabled', esc_html__('Run WooCommerce scheduled actions on staging', 'wp-staging'), esc_html__('Disable this to prevent WooCommerce background actions on staging.', 'wp-staging'), !$isUpdateFreeOptionDisabled, $updateFreeDisabledOptions, $runtimeSummaryTooltips['woo']['enabled'], $isUpdateFreeOptionDisabled); ?>
            <?php endif; ?>
            <?php
            $renderer->setupOptionCard('wpstg_reminder_emails', esc_html__('Send reminder emails for this staging site', 'wp-staging'), esc_html__('Reminds you that the staging site still exists.', 'wp-staging'), false, $updateFreeDisabledOptions, '', $isUpdateFreeOptionDisabled);
            $renderer->setupOptionCard('wpstg_auto_update_plugins', esc_html__('Update plugins after cloning', 'wp-staging'), esc_html__('Useful for testing updates on staging.', 'wp-staging'), false, $updateFreeDisabledOptions, '', $isUpdateFreeOptionDisabled);
            $renderer->setupOptionCard('wpstg-clean-plugins-themes', esc_html__('Clean Plugins/Themes', 'wp-staging'), esc_html__('Remove staging plugins/themes first.', 'wp-staging'));
            $renderer->setupOptionCard('wpstg-clean-uploads', esc_html__('Clean Uploads', 'wp-staging'), $stagingSiteDto->getUploadsSymlinked() ? esc_html__('Disabled because uploads are symlinked.', 'wp-staging') : esc_html__('Remove staging uploads first.', 'wp-staging'));
            ?>
        </div>
    <?php endif; ?>
    <?php
});
