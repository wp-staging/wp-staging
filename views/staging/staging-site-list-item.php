<?php

/**
 * This view is used to list a single item of staging site
 * @see src/views/staging/listing.php
 *
 * @var WPStaging\Staging\Dto\StagingSiteDto      $stagingSite
 * @var WPStaging\Staging\Dto\ListableStagingSite $stagingSiteItem
 * @var mixed                                     $license
 * @var WPStaging\Framework\Assets\Assets         $assets
 */

use WPStaging\Framework\Hosting\StagingSiteHttpDetector;
use WPStaging\Framework\Language\Language;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Staging\Dto\StagingSiteDto;

$stagingSiteElementId   = empty($stagingSiteItem->directoryName) ? $stagingSiteItem->cloneName : $stagingSiteItem->directoryName;
$isStagingSiteUnhealthy = $stagingSiteItem->health === StagingSiteHttpDetector::HEALTH_UNHEALTHY;
$isStagingSiteBroken    = !empty($stagingSiteItem->status) && $stagingSiteItem->status !== StagingSiteDto::STATUS_FINISHED;
$diagnostics            = $stagingSiteItem->healthDiagnostics;
$answeredByLiveSite     = isset($diagnostics['reason']) && $diagnostics['reason'] === StagingSiteHttpDetector::REASON_LIVE_SITE;

?>

<div id="<?php echo esc_attr($stagingSiteElementId); ?>" data-clone-id="<?php echo esc_attr($stagingSiteItem->cloneId); ?>" class="wpstg-clone">
    <div class="wpstg-clone-header">
        <a href="javascript:void(0);" class="wpstg-clone-title wpstg-open-staging-site" data-clone="<?php echo esc_attr($stagingSiteItem->cloneId); ?>" data-url="<?php echo esc_url($stagingSiteItem->urlToOpen); ?>">
            <?php echo esc_html($stagingSiteItem->siteName); ?>
        </a>
        <?php if (is_multisite()) : ?>
        <div class="wpstg-clone-labels">
            <span class="wpstg-clone-label"><?php echo $stagingSiteItem->isNetworkClone ? esc_html__('Network Site', 'wp-staging') : esc_html__('Single Site', 'wp-staging'); ?></span>
        </div>
        <?php endif; ?>
        <div class="wpstg-clone-health wpstg-inline-flex wpstg-items-center wpstg-flex-wrap wpstg-gap-2" data-clone-id="<?php echo esc_attr($stagingSiteItem->cloneId); ?>">
            <?php if ($isStagingSiteUnhealthy || $isStagingSiteBroken) : ?>
                <span class="wpstg-badge wpstg-badge-warning wpstg-health-badge"><?php esc_html_e('Unhealthy', 'wp-staging'); ?></span>
            <?php elseif ($stagingSiteItem->health === StagingSiteHttpDetector::HEALTH_REACHABLE) : ?>
                <span class="wpstg-badge wpstg-badge-info wpstg-health-badge"><?php esc_html_e('Running', 'wp-staging'); ?></span>
            <?php endif; ?>
            <?php if ($isStagingSiteUnhealthy) : ?>
                <button type="button" aria-haspopup="dialog" class="wpstg--diagnose--staging-site wpstg-btn wpstg-btn-sm wpstg-btn-ghost" data-clone-id="<?php echo esc_attr($stagingSiteItem->cloneId); ?>" data-close="<?php esc_attr_e('Close', 'wp-staging'); ?>">
                    <?php esc_html_e('Diagnose Issue', 'wp-staging'); ?>
                </button>
            <?php endif; ?>
            <button type="button" class="wpstg--recheck--staging-site wpstg-btn wpstg-btn-sm wpstg-btn-ghost" data-clone-id="<?php echo esc_attr($stagingSiteItem->cloneId); ?>" data-checking="<?php esc_attr_e('Checking…', 'wp-staging'); ?>">
                <?php esc_html_e('Re-Check health', 'wp-staging'); ?>
            </button>
        </div>
        <?php if ($isStagingSiteUnhealthy) : ?>
        <div class="wpstg-diagnose-content" data-clone-id="<?php echo esc_attr($stagingSiteItem->cloneId); ?>" hidden>
            <div class="wpstg-text-left">
                <div class="wpstg-flex wpstg-gap-4">
                    <div class="wpstg-flex wpstg-h-14 wpstg-w-14 wpstg-flex-shrink-0 wpstg-items-center wpstg-justify-center wpstg-rounded-full wpstg-border wpstg-border-solid wpstg-border-amber-200 dark:wpstg-border-amber-800 wpstg-bg-amber-100 dark:wpstg-bg-amber-900">
                        <?php $assets->renderSvg('alert', 'wpstg-h-7 wpstg-w-7 wpstg-text-amber-500'); ?>
                    </div>
                    <div>
                        <span class="wpstg-inline-flex wpstg-items-center wpstg-gap-1.5 wpstg-rounded-full wpstg-bg-amber-100 dark:wpstg-bg-amber-900 wpstg-px-3 wpstg-py-1 wpstg-text-sm wpstg-font-semibold wpstg-text-amber-700 dark:wpstg-text-amber-300">
                            <?php esc_html_e('Unreachable', 'wp-staging'); ?>
                        </span>
                        <h2 class="wpstg-mt-1 wpstg-text-xl wpstg-font-bold wpstg-text-slate-900 dark:wpstg-text-slate-100">
                            <?php echo $answeredByLiveSite
                                ? sprintf(esc_html__('Staging site "%s" is not served at this address', 'wp-staging'), esc_html($stagingSiteItem->siteName))
                                : sprintf(esc_html__('Staging site "%s" isn\'t responding', 'wp-staging'), esc_html($stagingSiteItem->siteName)); ?>
                        </h2>
                    </div>
                </div>
                <a href="<?php echo esc_url($stagingSiteItem->urlToOpen); ?>" target="_blank" rel="noopener noreferrer" class="wpstg-mt-4 wpstg-inline-flex wpstg-max-w-full wpstg-items-center wpstg-gap-1.5 wpstg-break-all wpstg-rounded-lg wpstg-border wpstg-border-solid wpstg-border-slate-200 dark:wpstg-border-slate-700 wpstg-bg-slate-50 dark:wpstg-bg-slate-800 wpstg-px-3 wpstg-py-1.5 wpstg-text-sm wpstg-font-mono wpstg-text-blue-600 hover:wpstg-text-blue-700">
                    <?php echo esc_html($stagingSiteItem->urlToOpen); ?>
                    <span class="screen-reader-text"><?php esc_html_e('(opens in a new tab)', 'wp-staging'); ?></span>
                </a>
            </div>
            <?php if (!empty($diagnostics['body'])) : ?>
            <div class="wpstg-mt-6 wpstg-rounded-2xl wpstg-border wpstg-border-solid wpstg-border-slate-200 dark:wpstg-border-slate-700 wpstg-p-4 wpstg-text-left">
                <div class="wpstg-text-xs wpstg-font-semibold wpstg-uppercase wpstg-tracking-wider wpstg-text-slate-500 dark:wpstg-text-slate-400"><?php esc_html_e('Server response', 'wp-staging'); ?></div>
                <?php if ($answeredByLiveSite) : ?>
                    <p class="wpstg-mt-2 wpstg-text-sm wpstg-text-slate-500 dark:wpstg-text-slate-400"><?php esc_html_e('This is the live site, not the staging site we expected at this address.', 'wp-staging'); ?></p>
                <?php endif; ?>
                <pre role="region" tabindex="0" aria-label="<?php echo esc_attr__('Server response', 'wp-staging'); ?>" class="wpstg-mt-3 wpstg-max-h-72 wpstg-overflow-auto wpstg-rounded-2xl wpstg-bg-slate-900 wpstg-p-5 wpstg-text-sm wpstg-font-mono wpstg-leading-relaxed wpstg-text-slate-100 wpstg-whitespace-pre-wrap wpstg-break-all"><?php echo esc_html($diagnostics['body']); ?></pre>
            </div>
            <?php endif; ?>
            <p class="wpstg-mt-6 wpstg-text-center wpstg-text-sm wpstg-text-slate-500 dark:wpstg-text-slate-400"><?php echo sprintf(esc_html__('Still stuck? %s and we\'ll help you resolve this issue.', 'wp-staging'), '<a href="https://wp-staging.com/support/" target="_blank" rel="noopener noreferrer" class="wpstg-font-medium wpstg-text-blue-600 hover:wpstg-text-blue-700">' . esc_html__('Contact WP STAGING Support', 'wp-staging') . '</a>'); ?></p>
        </div>
        <?php endif; ?>
        <div class="wpstg-clone-actions">
            <div class="wpstg-dropdown wpstg-action-dropdown">
                <a href="#" class="wpstg-dropdown-toggler">
                    <?php esc_html_e("Actions", "wp-staging"); ?>
                    <span class="wpstg-caret"></span>
                </a>
                <div class="wpstg-dropdown-menu">
                    <?php do_action('wpstg.views.single_overview.before_existing_clones_actions', $stagingSiteItem->cloneId, $stagingSite->toArray(), $license); ?>
                    <a href="javascript:void(0)" class="wpstg-open-clone wpstg-clone-action" data-clone="<?php echo esc_attr($stagingSiteItem->cloneId); ?>" data-url="<?php echo esc_url($stagingSiteItem->urlToOpen); ?>" title="<?php echo esc_html__("Open the staging site in a new tab", "wp-staging"); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('open-site'); ?>
                        </div>
                        <?php esc_html_e("Open", "wp-staging"); ?>
                    </a>
                    <a href="#" class="wpstg--update--staging-site--setup wpstg-clone-action" data-cloneId="<?php echo esc_attr($stagingSiteItem->cloneId); ?>" data-url="<?php echo esc_url($stagingSiteItem->url); ?>" title="<?php echo esc_html__("Update and overwrite the selected staging site with the production site. You can select files and database tables on the next page. This action will not replace nor modify the wp-config.php on the staging site!", "wp-staging"); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('update-site'); ?>
                        </div>
                        <?php esc_html_e("Update", "wp-staging"); ?>
                    </a>
                    <a href="#" class="wpstg--reset--staging-site--setup wpstg-clone-action" data-cloneId="<?php echo esc_attr($stagingSiteItem->cloneId); ?>" data-url="<?php echo esc_url($stagingSiteItem->url); ?>" data-network="<?php echo is_multisite() && !empty($stagingSiteItem->isNetworkClone) ? 'yes' : 'no'; ?>" title="<?php echo esc_attr__("Replace the selected staging site with the production site completely. This includes replacing the wp-config.php and all files and data. Confirm to proceed on the next page.", "wp-staging"); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('reset'); ?>
                        </div>
                        <?php esc_html_e("Reset", "wp-staging"); ?>
                    </a>
                    <?php
                    do_action(TemplateEngine::ACTION_AFTER_EXISTING_CLONES, $stagingSiteItem->cloneId, $stagingSite->toArray(), $license);

                    if (!$isPro) :?>
                    <a href="<?php echo esc_url(Language::getUpgradeUrl('edit_data')); ?>" target="_blank" class="wpstg-pro-clone-feature wpstg-clone-action"  title="<?php echo esc_html__("Edit Data", "wp-staging"); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('edit'); ?>
                        </div>
                        <?php esc_html_e("Edit Data", "wp-staging"); ?>
                        <span>(Pro)</span>
                    </a>
                    <a href="<?php echo esc_url(Language::getUpgradeUrl('push_changes')); ?>" target="_blank" class="wpstg-pro-clone-feature wpstg-clone-action" title="<?php echo esc_html__("Push Changes", "wp-staging"); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('push'); ?>
                        </div>
                        <?php esc_html_e("Push Changes", "wp-staging"); ?>
                        <span>(Pro)</span>
                    </a>
                    <a href="<?php echo esc_url(Language::getUpgradeUrl('share_login')); ?>" target="_blank" class="wpstg-pro-clone-feature wpstg-clone-action"  title="<?php echo esc_html__("Share Login Link", "wp-staging"); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('user-plus'); ?>
                        </div>
                        <?php esc_html_e("Share Login Link", "wp-staging"); ?>
                        <span>(Pro)</span>
                    </a>
                    <a href="<?php echo esc_url(Language::getUpgradeUrl('sync_user')); ?>" target="_blank" class="wpstg-pro-clone-feature wpstg-clone-action" title="<?php echo esc_html__("Sync User Account", "wp-staging"); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('sync-user'); ?>
                        </div>
                        <?php esc_html_e("Sync User Account", "wp-staging"); ?>
                        <span>(Pro)</span>
                    </a>
                    <?php endif; ?>
                    <div class="wpstg--mx-1 wpstg-my-1 wpstg-h-px wpstg-bg-dim"></div>
                    <a href="#" class="wpstg--delete--staging-site wpstg-clone-action wpstg-dropdown-delete" data-cloneId="<?php echo esc_attr($stagingSiteItem->cloneId); ?>" title="<?php echo esc_html__("Delete the selected staging site. Select specific folders and database tables in the next step.", "wp-staging"); ?>" data-name="<?php echo esc_attr($stagingSiteItem->cloneName); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('trash'); ?>
                        </div>
                        <?php esc_html_e("Delete", "wp-staging"); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="wpstg-staging-info">
        <ul class="wpstg-u-m-0">
            <li><span><?php esc_html_e('Database Name', 'wp-staging'); ?>: </span><span class="wpstg-bold wpstg-staging-site-database-name"><?php echo esc_html($stagingSiteItem->databaseName); ?></span></li>
            <li><span><?php esc_html_e('Database Prefix', 'wp-staging'); ?>: </span><span class="wpstg-bold wpstg-staging-site-database-prefix"><?php echo esc_html($stagingSiteItem->databasePrefix); ?></span></li>
            <li><span><?php esc_html_e('Directory Path', 'wp-staging'); ?>: </span><span class="wpstg-bold wpstg-staging-site-path"><?php echo esc_html($stagingSiteItem->path); ?></span></li>
            <li><span><?php esc_html_e('URL', 'wp-staging'); ?>: </span><span class="wpstg-bold wpstg-staging-site-url"><?php echo empty($stagingSiteItem->urlToOpen) ? '' : sprintf('<a href="%1$s" id="wpstg-staging-site-url" target="_blank">%1$s</a>', esc_url($stagingSiteItem->urlToOpen)); ?></span></li>
            <li><span><?php esc_html_e('Created By', 'wp-staging'); ?>: </span><span class="wpstg-bold wpstg-staging-site-created-by"><?php echo esc_html($stagingSiteItem->createdBy); ?></span></li>
            <li>
            <?php if ($isStagingSiteBroken) : ?>
                <span><?php esc_html_e('Status', 'wp-staging'); ?>: </span>
                <span class="wpstg-staging-status wpstg-bold"
                    title="<?php esc_attr_e("This clone is incomplete and does not work. Clone or update it again! \n\n" .
                    "Important: Keep the browser open until the cloning is finished. \n" .
                    "It will not proceed if your browser is not open.\n\n" .
                    "If you have an unstable internet connection and cloning breaks due to that, clone again only the folders wp-admin, wp-includes, and all database tables.\n\n" .
                    "That will not take much time. Then, you can proceed with the wp-content folder that usually needs the most disk space. " .
                    "If it interrupts again, at least it will not break the existing staging site again, and you can repeat and resume the last operation.", 'wp-staging'); ?>">
                    <?php echo esc_html($stagingSiteItem->status); ?>
                </span>
            <?php else : ?>
                &nbsp;&nbsp;&nbsp;
            <?php endif; ?>
            </li>
            <li><span><?php esc_html_e('Updated', 'wp-staging'); ?>: </span><span class="wpstg-bold wpstg-staging-site-updated"><?php echo esc_html($stagingSiteItem->modifiedAt); ?></span></li>
        </ul>
    </div>
</div>
