<?php

/**
 * This view is used to list a single item of staging site
 * @see src/views/staging/listing.php
 *
 * @var WPStaging\Staging\Dto\StagingSiteDto      $stagingSite
 * @var WPStaging\Staging\Dto\ListableStagingSite $stagingSiteItem
 * @var mixed                                     $license
 * @var WPStaging\Framework\Assets\Assets $assets
 */

use WPStaging\Framework\TemplateEngine\TemplateEngine;

?>

<div id="<?php echo esc_attr($stagingSiteItem->directoryName); ?>" data-clone-id="<?php echo esc_attr($stagingSiteItem->cloneId); ?>" class="wpstg-clone">
    <div class="wpstg-clone-header">
        <a href="javascript:void(0);" class="wpstg-clone-title wpstg-open-staging-site" data-clone="<?php echo esc_attr($stagingSiteItem->cloneId); ?>" data-url="<?php echo esc_url($stagingSiteItem->url); ?>">
            <?php echo esc_html($stagingSiteItem->siteName); ?>
        </a>
        <?php if (is_multisite()) : ?>
        <div class="wpstg-clone-labels">
            <span class="wpstg-clone-label"><?php echo $stagingSiteItem->isNetworkClone ? esc_html__('Network Site', 'wp-staging') : esc_html__('Single Site', 'wp-staging'); ?></span>
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
                    <a href="javascript:void(0)" class="wpstg-open-clone wpstg-clone-action" data-clone="<?php echo esc_attr($stagingSiteItem->cloneId); ?>" data-url="<?php echo esc_url($stagingSiteItem->url); ?>" title="<?php echo esc_html__("Open the staging site in a new tab", "wp-staging"); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('open-site'); ?>
                        </div>
                        <?php esc_html_e("Open", "wp-staging"); ?>
                    </a>
                    <a href="#" class="wpstg-execute-clone wpstg-clone-action" data-clone="<?php echo esc_attr($stagingSiteItem->cloneId); ?>" data-url="<?php echo esc_url($stagingSiteItem->url); ?>" title="<?php echo esc_html__("Update and overwrite the selected staging site with the production site. You can select files and database tables on the next page. This action will not replace nor modify the wp-config.php on the staging site!", "wp-staging"); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('update-site'); ?>
                        </div>
                        <?php esc_html_e("Update", "wp-staging"); ?>
                    </a>
                    <a href="#" class="wpstg-reset-clone wpstg-clone-action" data-clone="<?php echo esc_attr($stagingSiteItem->cloneId); ?>" data-network="<?php echo is_multisite() && !empty($stagingSiteItem->isNetworkClone)  ? 'yes' : 'no'; ?>" title="<?php echo esc_attr__("Replace the selected staging site with the production site completely. This includes replacing the wp-config.php and all files and data. Confirm to proceed on the next page.", "wp-staging"); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('reset'); ?>
                        </div>
                        <?php esc_html_e("Reset", "wp-staging"); ?>
                    </a>
                    <a href="#" class="wpstg--delete--staging-site wpstg-clone-action" data-cloneId="<?php echo esc_attr($stagingSiteItem->cloneId); ?>" title="<?php echo esc_html__("Delete the selected staging site. Select specific folders and database tables in the next step.", "wp-staging"); ?>" data-name="<?php echo esc_attr($stagingSiteItem->cloneName); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('trash'); ?>
                        </div>
                        <?php esc_html_e("Delete", "wp-staging"); ?>
                    </a>
                    <?php
                    do_action(TemplateEngine::ACTION_AFTER_EXISTING_CLONES, $stagingSiteItem->cloneId, $stagingSite->toArray(), $license);

                    if (!$isPro) :?>
                    <a href="https://wp-staging.com/pro-features/#edit-data" target="_blank" class="wpstg-pro-clone-feature wpstg-clone-action"  title="<?php echo esc_html__("Edit Data", "wp-staging"); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('edit'); ?>
                        </div>
                        <?php esc_html_e("Edit Data", "wp-staging"); ?>
                        <span>(Pro)</span>
                    </a>
                    <a href="https://wp-staging.com/pro-features/#push-changes" target="_blank" class="wpstg-pro-clone-feature wpstg-clone-action" title="<?php echo esc_html__("Push Changes", "wp-staging"); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('push'); ?>
                        </div>
                        <?php esc_html_e("Push Changes", "wp-staging"); ?>
                        <span>(Pro)</span>
                    </a>
                    <a href="https://wp-staging.com/pro-features/#share-login-link" target="_blank" class="wpstg-pro-clone-feature wpstg-clone-action"  title="<?php echo esc_html__("Share Login Link", "wp-staging"); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('user-plus'); ?>
                        </div>
                        <?php esc_html_e("Share Login Link", "wp-staging"); ?>
                        <span>(Pro)</span>
                    </a>
                    <a href="https://wp-staging.com/pro-features/#sync-user-account" target="_blank" class="wpstg-pro-clone-feature wpstg-clone-action" title="<?php echo esc_html__("Sync User Account", "wp-staging"); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $assets->renderSvg('sync-user'); ?>
                        </div>
                        <?php esc_html_e("Sync User Account", "wp-staging"); ?>
                        <span>(Pro)</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="wpstg-staging-info">
        <ul class="wpstg-m-0">
            <li><span><?php esc_html_e('Database Name', 'wp-staging'); ?>: </span><span class="wpstg-bold wpstg-staging-site-database-name"><?php echo esc_html($stagingSiteItem->databaseName); ?></span></li>
            <li><span><?php esc_html_e('Database Prefix', 'wp-staging'); ?>: </span><span class="wpstg-bold wpstg-staging-site-database-prefix"><?php echo esc_html($stagingSiteItem->databasePrefix); ?></span></li>
            <li><span><?php esc_html_e('Directory Path', 'wp-staging'); ?>: </span><span class="wpstg-bold wpstg-staging-site-path"><?php echo esc_html($stagingSiteItem->path); ?></span></li>
            <li><span><?php esc_html_e('URL', 'wp-staging'); ?>: </span><span class="wpstg-bold wpstg-staging-site-url"><?php echo empty($stagingSiteItem->url) ? '' : sprintf('<a href="%1$s" target="_blank">%1$s</a>', esc_url($stagingSiteItem->url)); ?></span></li>
            <li><span><?php esc_html_e('Created By', 'wp-staging'); ?>: </span><span class="wpstg-bold wpstg-staging-site-created-by"><?php echo esc_html($stagingSiteItem->createdBy); ?></span></li>
            <li>
            <?php if (!empty($stagingSiteItem->status) && $stagingSiteItem->status !== 'finished') : ?>
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
