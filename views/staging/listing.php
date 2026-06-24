<?php

/**
 * This view is used to list all staging sites, when create a new staging site button
 * @see \WPStaging\Staging\Ajax\Listing::ajaxListing
 *
 * @var array  $stagingSites
 * @var string $iconPath
 * @var bool   $error - true when staging site option is corrupted
 * @var        $license
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Language\Language;
use WPStaging\Framework\Notices\CliIntegrationNotice;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

$isPro = WPStaging::isPro();
/** @var CliIntegrationNotice $cliNotice */
$cliNotice = WPStaging::make(CliIntegrationNotice::class);
include WPSTG_VIEWS_DIR . 'job/modal/success.php';
include WPSTG_VIEWS_DIR . 'job/modal/process.php';

// Will show a locked message if the process is locked
require WPSTG_VIEWS_DIR . 'job/locked.php';
$assets = WPStaging::make(Assets::class);
?>
<div id="wpstg-step-1">
    <div class="wpstg-staging-actions">
        <button
            id="wpstg-new-staging"
            class="wpstg-btn wpstg-btn-md wpstg-btn-primary wpstg-px-3 wpstg-new-staging-btn"
            <?php echo $error ? 'disabled' : '' ?>
        >
            <svg class="wpstg-btn-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <?php echo esc_html__("Create Staging Site", "wp-staging") ?>
        </button>

        <!-- CLI Dock Slot - populated after banner collapse or server-side when banner was dismissed -->
        <div id="wpstg-cli-dock-slot" class="wpstg-cli-dock-slot"><?php $cliNotice->maybeRenderDockCta(); ?></div>
    </div>
</div>

<?php if (WPStaging::isOnWordPressPlayground()) : ?>
<div class="wpstg-callout wpstg-callout-warning wpstg-mt-5">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
    <p class="wpstg-m-0 wpstg-text-sm">
        <?php esc_html_e("A staging site may not work properly due to limitations in WordPress Playground. However, creating one can still be valuable for demonstrating the functionality.", "wp-staging"); ?>
    </p>
</div>
<?php endif; ?>

<?php if (!empty($stagingSites)) : ?>
    <!-- Existing Clones -->
    <div id="wpstg-existing-clones">
        <h3>
            <?php esc_html_e("Your Staging Sites:", "wp-staging"); ?>
        </h3>
        <?php foreach ($stagingSites as $stagingSite) :
            $stagingSiteItem = $stagingSite->toListableItem();
            include WPSTG_VIEWS_DIR . 'staging/staging-site-list-item.php';
        endforeach ?>
        <div class="wpstg-fs-14" id="info-block-how-to-push">
            <?php esc_html_e("How to:", "wp-staging"); ?> <a href="https://wp-staging.com/docs/copy-staging-site-to-live-site/" target="_blank"><?php esc_html_e("Push staging site to production", "wp-staging"); ?></a>
        </div>
    </div>
    <!-- /Existing Clones -->
<?php endif ?>

<div id="wpstg-no-staging-site-results" class="wpstg-clone wpstg-mt-5 wpstg-text-center" <?php echo ($stagingSites !== [] || $error) ? 'style="display: none;"' : ''; ?> >
    <svg width="36" height="36" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="wpstg-mb-3 wpstg-text-gray-400 dark:wpstg-text-slate-500" style="display:inline-block;">
        <path d="M14.9 9c0-.3.1-.6.1-1 0-2.2-1.8-4-4-4-1.6 0-2.9.9-3.6 2.2-.2-.1-.6-.2-.9-.2C5.1 6 4 7.1 4 8.5c0 .2 0 .4.1.5-1.8.3-3.1 1.7-3.1 3.5C1 14.4 2.6 16 4.5 16h10c1.9 0 3.5-1.6 3.5-3.5 0-1.8-1.3-3.3-3.1-3.5z"></path>
    </svg>
    <h3 class="wpstg-mt-0 wpstg-mb-2 wpstg-text-lg wpstg-font-semibold"><?php esc_html_e('No staging site yet', 'wp-staging'); ?></h3>
    <p class="wpstg-mt-0 wpstg-mb-4 wpstg-mx-auto wpstg-max-w-xl wpstg-text-[15px] wpstg-leading-relaxed wpstg-text-gray-600 dark:wpstg-text-slate-300">
        <?php esc_html_e('Create a safe copy of this website before testing updates, changing themes, editing content, or deploying custom code.', 'wp-staging'); ?>
    </p>
    <button
        class="wpstg-btn wpstg-btn-md wpstg-btn-primary wpstg-px-3 wpstg-new-staging-btn"
        <?php echo $error ? 'disabled' : '' ?>
    >
        <svg class="wpstg-btn-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <?php echo esc_html__('Create Staging Site', 'wp-staging'); ?>
    </button>
    <p class="wpstg-mt-3 wpstg-mb-0 wpstg-mx-auto wpstg-max-w-xl wpstg-text-[13px] wpstg-text-gray-500 dark:wpstg-text-slate-400">
        <?php esc_html_e('Start with one-click staging on this server. Advanced workflows are available in Pro.', 'wp-staging'); ?>
        <?php if (!$isPro) : ?>
            <br>
            <a class="wpstg-text-gray-500 hover:wpstg-text-gray-600 dark:wpstg-text-slate-400 dark:hover:wpstg-text-slate-300"
               href="<?php echo esc_url(Language::localizeUrl('https://wp-staging.com/pro-features/?utm_source=wp-admin&utm_medium=staging_empty_state&utm_campaign=compare')); ?>"
               target="_blank" rel="noopener noreferrer"><?php esc_html_e('Compare Free vs Pro', 'wp-staging'); ?></a>
        <?php endif; ?>
    </p>
</div>

<?php if ($error) : ?>
    <div class="wpstg-clone wpstg-clone-error wpstg--error">
        <h4><?php echo esc_html__("Staging Sites Error: ", "wp-staging"); ?></h4>
        <p><?php esc_html_e('Staging sites data is corrupted. See the option below to fix it. Contact WP Staging support for more info!', 'wp-staging'); ?></p>
        <button id="wpstg-fix-staging-sites-option" class="wpstg-button wpstg-mr-10px">
            <?php echo esc_html__("Report and fix this issue", "wp-staging"); ?>
        </button>
        <button id="wpstg-report-corrupted-staging-sites" class="wpstg-button">
            <?php echo esc_html__("Report this issue only", "wp-staging"); ?>
        </button>
        <p>
        <?php echo sprintf(
            esc_html__("Note: This fix will create a backup of the corrupted staging site options and clean the listed staging sites. You will still be able to access your existing staging sites and can %s.", "wp-staging"),
            "<a href='https://wp-staging.com/docs/reconnect-staging-site-to-production-website/' target='_blank'>" . esc_html__('reconnect them to the production website', 'wp-staging') . "</a>"
        ); ?>
        </p>
    </div>
<?php endif; ?>

<?php Hooks::doAction(TemplateEngine::HOOK_RENDER_PRO_TEMPLATES); ?>

<?php
// Compact general "Upgrade to Pro" card. Rendered below the staging list for
// any Free build (including when the Pro plugin is installed but inactive) when
// the admin can manage settings and has not snoozed it.
//
// It is intentionally NOT shown on the zero-staging-sites first-run screen: that
// state is owned by the empty-state card so the user can focus on creating the
// first staging site instead of being pulled toward an upsell. This only gates
// rendering; the 90-day snooze/dismissal state is left untouched in this state.
// The card never appears above the staging list/empty state, on the Upgrade
// page, or alongside a loose review prompt.
if (
    !empty($stagingSites)
    && !$isPro
    && current_user_can('manage_options')
    && !WPStaging::make(\WPStaging\Basic\Notices\GeneralProCardNotice::class)->isSnoozed()
) {
    require WPSTG_VIEWS_DIR . 'ads/pro-upgrade-card.php';
}

// The review prompt is intentionally NOT rendered on the dashboard. It is a
// success-based, in-modal ask shown right after a staging site or backup is
// created (see views/notices/review-prompt-modal.php), never loose dashboard text.
?>

<!-- Remove Clone -->
<div id="wpstg-removing-clone">

</div>
<!-- /Remove Clone -->
