<?php

/**
 * Markup shared by the dismissible "Upgrade to Pro" cards; the caller supplies the copy, links and notice id.
 *
 * @see views/ads/staging-pro-upgrade-card.php
 * @see views/ads/backup-pro-upgrade-card.php
 *
 * @var array{cssClass: string, noticeId: string, headline: string, body: string, features: string[], upgradeUrl: string, upgradeContext: string, compareUrl: string, trustLine?: string} $proUpgradeCard
 */

?>
<div class="<?php echo esc_attr($proUpgradeCard['cssClass']); ?> wpstg-card wpstg-mt-5" style="position:relative;max-width:777px;">
    <a href="javascript:void(0);"
       class="wpstg-pro-card-dismiss wpstg-text-gray-400"
       data-wpstg-notice="<?php echo esc_attr($proUpgradeCard['noticeId']); ?>"
       aria-label="<?php esc_attr_e('Dismiss Pro upgrade card', 'wp-staging'); ?>"
       title="<?php esc_attr_e('Dismiss Pro upgrade card', 'wp-staging'); ?>"
       style="position:absolute;top:12px;right:16px;font-size:18px;line-height:1;text-decoration:none;">&times;</a>
    <div class="wpstg-card-body">
        <p class="wpstg-text-lg wpstg-font-semibold wpstg-mt-0 wpstg-mb-1">
            <?php echo esc_html($proUpgradeCard['headline']); ?>
        </p>
        <p class="wpstg-mt-0 wpstg-mb-4 wpstg-text-sm wpstg-text-gray-500 dark:wpstg-text-slate-400">
            <?php echo esc_html($proUpgradeCard['body']); ?>
        </p>
        <ul class="wpstg-flex wpstg-flex-wrap wpstg-gap-x-4 wpstg-gap-y-1 wpstg-mb-3" style="list-style:none;padding:0;margin:0 0 16px;max-width:640px;">
            <?php foreach ($proUpgradeCard['features'] as $feature) : ?>
                <li class="wpstg-flex wpstg-items-start wpstg-gap-1 wpstg-text-sm" style="flex:1 1 240px;">
                    <span class="wpstg-text-blue-700" aria-hidden="true" style="font-weight:700;">&#10003;</span>
                    <?php echo esc_html($feature); ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="wpstg-flex wpstg-flex-wrap wpstg-gap-3">
            <a class="wpstg-btn wpstg-btn-md wpstg-btn-pro" href="<?php echo esc_url($proUpgradeCard['upgradeUrl']); ?>" target="_blank" rel="noopener noreferrer" data-wpstg-upgrade-cta="<?php echo esc_attr($proUpgradeCard['upgradeContext']); ?>">
                <?php esc_html_e('Upgrade to Pro', 'wp-staging'); ?>
            </a>
            <a class="wpstg-btn wpstg-btn-md wpstg-btn-outline" href="<?php echo esc_url($proUpgradeCard['compareUrl']); ?>" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e('Compare Free vs Pro', 'wp-staging'); ?>
            </a>
        </div>
        <?php if (!empty($proUpgradeCard['trustLine'])) : ?>
            <p class="wpstg-mt-4 wpstg-mb-0 wpstg-text-xs wpstg-text-gray-500 dark:wpstg-text-slate-400">
                <?php echo esc_html($proUpgradeCard['trustLine']); ?>
            </p>
        <?php endif; ?>
    </div>
</div>
