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
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

$isPro = WPStaging::isPro();
include WPSTG_VIEWS_DIR . 'job/modal/success.php';
include WPSTG_VIEWS_DIR . 'job/modal/process.php';

$processLock = WPStaging::make(ProcessLock::class);
try {
    $processLock->checkProcessLocked();
    $isLocked = false;
} catch (ProcessLockedException $e) {
    $isLocked = true;
}
?>

<?php if ($isLocked) : ?>
    <div id="wpstg-backup-locked">
        <div class="wpstg-locked-backup-loader"></div>
        <div class="text"><?php esc_html_e('There is backup work in progress...', 'wp-staging'); ?></div>
    </div>
<?php endif; ?>

<div id="wpstg-step-1">
    <?php if (defined('WPSTG_NEW_STAGING')) : ?>
        <button id="wpstg-new-staging" class="wpstg-blue-primary wpstg-button" <?php echo $error ? 'disabled' : '' ?>>
            <?php echo esc_html__("Create Staging Site", "wp-staging") ?>
        </button>
    <?php else : ?>
        <button id="wpstg-new-clone" class="wpstg-next-step-link wpstg-blue-primary wpstg-button" data-action="wpstg_scanning" <?php echo $error ? 'disabled' : '' ?>>
            <?php echo esc_html__("Create Staging Site", "wp-staging") ?>
        </button>
    <?php endif; ?>
</div>

<?php if (WPStaging::isOnWordPressPlayground()) : ?>
<span class="wpstg-error-msg" id="wpstg-clone-id-error">
    <?php
    esc_html_e("A staging site may not work properly due to limitations in WordPress Playground. However, creating one can still be valuable for demonstrating the functionality.", "wp-staging");
    ?>
</span>
<?php endif; ?>

<?php if (!empty($stagingSites)) : ?>
    <!-- Existing Clones -->
    <div id="wpstg-existing-clones">
        <h3>
            <?php esc_html_e("Your Staging Sites:", "wp-staging") ?>
        </h3>
        <?php foreach ($stagingSites as $stagingSite) :
            $stagingSiteItem = $stagingSite->toListableItem();
            include WPSTG_VIEWS_DIR . 'staging/staging-site-list-item.php';
        endforeach ?>
        <div class="wpstg-fs-14" id="info-block-how-to-push">
            <?php esc_html_e("How to:", "wp-staging") ?> <a href="https://wp-staging.com/docs/copy-staging-site-to-live-site/" target="_blank"><?php esc_html_e("Push staging site to production", "wp-staging") ?></a>
        </div>
    </div>
    <!-- /Existing Clones -->
<?php endif ?>

<div id="wpstg-no-staging-site-results" class="wpstg-clone" <?php echo ($stagingSites !== [] || $error) ? 'style="display: none;"' : '' ?> >
    <img class="wpstg--dashicons" src="<?php echo esc_url($iconPath); ?>" alt="cloud">
    <div class="no-staging-site-found-text">
        <?php esc_html_e('No Staging Site found. Create your first Staging Site above!', 'wp-staging'); ?>
    </div>
</div>

<?php if ($error) : ?>
    <div class="wpstg-clone wpstg-clone-error wpstg--error">
        <h4><?php echo esc_html__("Staging Sites Error: ", "wp-staging") ?></h4>
        <p><?php esc_html_e('Staging sites data is corrupted. See the option below to fix it. Contact WP Staging support for more info!', 'wp-staging'); ?></p>
        <button id="wpstg-fix-staging-sites-option" class="wpstg-button wpstg-mr-10px">
            <?php echo esc_html__("Report and fix this issue", "wp-staging") ?>
        </button>
        <button id="wpstg-report-corrupted-staging-sites" class="wpstg-button">
            <?php echo esc_html__("Report this issue only", "wp-staging") ?>
        </button>
        <p>
        <?php echo sprintf(
            esc_html__("Note: This fix will create a backup of the corrupted staging site options and clean the listed staging sites. You will still be able to access your existing staging sites and can %s.", "wp-staging"),
            "<a href='https://wp-staging.com/docs/reconnect-staging-site-to-production-website/' target='_blank'>" . esc_html__('reconnect them to the production website', 'wp-staging') . "</a>"
        ) ?>
        </p>
    </div>
<?php endif; ?>

<?php Hooks::doAction(TemplateEngine::HOOK_RENDER_PRO_TEMPLATES); ?>

<!-- Remove Clone -->
<div id="wpstg-removing-clone">

</div>
<!-- /Remove Clone -->
