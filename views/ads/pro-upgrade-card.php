<?php

/**
 * Compact, dismissible general "Upgrade to Pro" card.
 *
 * Shown below the main operational UI on the Staging dashboard for any Free
 * build (including when the Pro plugin is installed but inactive), when the
 * admin can manage settings and has not snoozed it. The caller
 * (views/staging/listing.php) handles the eligibility gate.
 *
 * Dismissal reuses the shared wpstg_dismiss_notice AJAX via the notice id
 * "general_pro_card" (handled by the page-level delegated handler in
 * views/clone/index.php, since the listing is injected via innerHTML). It
 * snoozes the card for 90 days per admin; it never disables contextual Pro
 * prompts, Pro badges, the Upgrade navigation, or the permanent Compare card.
 *
 * @see \WPStaging\Staging\Ajax\Listing::ajaxListing
 * @see \WPStaging\Basic\Notices\GeneralProCardNotice
 */

use WPStaging\Framework\Language\Language;

$upgradeUrl = Language::getUpgradeUrl('dashboard_upgrade_card');
$compareUrl = Language::localizeUrl('https://wp-staging.com/pro-features/?utm_source=wp-admin&utm_medium=staging_dashboard&utm_campaign=pro_card');

$proFeatures = [
    esc_html__('Push to production', 'wp-staging'),
    esc_html__('Cloud backups', 'wp-staging'),
    esc_html__('Unlimited schedules', 'wp-staging'),
    esc_html__('Migrations', 'wp-staging'),
    esc_html__('Multisite', 'wp-staging'),
    esc_html__('CLI and local Docker', 'wp-staging'),
];
?>
<div class="wpstg-general-pro-card wpstg-card wpstg-mt-5" style="position:relative;max-width:777px;">
    <a href="javascript:void(0);"
       class="wpstg-pro-card-dismiss wpstg-text-gray-400"
       aria-label="<?php esc_attr_e('Dismiss Pro upgrade card', 'wp-staging'); ?>"
       title="<?php esc_attr_e('Dismiss Pro upgrade card', 'wp-staging'); ?>"
       style="position:absolute;top:12px;right:16px;font-size:18px;line-height:1;text-decoration:none;">&times;</a>
    <div class="wpstg-card-body">
        <p class="wpstg-text-lg wpstg-font-semibold wpstg-mt-0 wpstg-mb-1">
            <?php esc_html_e('Need push-to-live, cloud backups, or migrations?', 'wp-staging'); ?>
        </p>
        <p class="wpstg-mt-0 wpstg-mb-4 wpstg-text-sm wpstg-text-gray-500 dark:wpstg-text-slate-400">
            <?php esc_html_e('WP Staging Pro adds one-click deployment, off-site cloud backups, unlimited schedules, site migrations, Multisite support, and developer workflows.', 'wp-staging'); ?>
        </p>
        <ul class="wpstg-flex wpstg-flex-wrap wpstg-gap-x-4 wpstg-gap-y-1 wpstg-mb-3" style="list-style:none;padding:0;margin:0 0 16px;max-width:640px;">
            <?php foreach ($proFeatures as $feature) : ?>
                <li class="wpstg-flex wpstg-items-center wpstg-gap-1 wpstg-text-sm" style="flex:0 0 calc(50% - 16px);">
                    <span class="wpstg-text-blue-700" aria-hidden="true" style="font-weight:700;">&#10003;</span>
                    <?php echo esc_html($feature); ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="wpstg-flex wpstg-flex-wrap wpstg-gap-3">
            <a class="wpstg-btn wpstg-btn-md wpstg-btn-pro" href="<?php echo esc_url($upgradeUrl); ?>" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e('Upgrade to Pro', 'wp-staging'); ?>
            </a>
            <a class="wpstg-btn wpstg-btn-md wpstg-btn-outline" href="<?php echo esc_url($compareUrl); ?>" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e('Compare Free vs Pro', 'wp-staging'); ?>
            </a>
        </div>
    </div>
</div>
