<?php

/**
 * This view is used to list all staging sites, when create a new staging site button
 * @see \WPStaging\Staging\Ajax\Listing::ajaxListing
 *
 * @var array  $stagingSites
 * @var string $iconPath
 * @var        $license
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

$isPro = WPStaging::isPro();
include WPSTG_VIEWS_DIR . 'job/modal/success.php';
include WPSTG_VIEWS_DIR . 'job/modal/process.php';

?>
<div id="wpstg-step-1">
    <button id="wpstg-new-clone" class="wpstg-next-step-link wpstg-blue-primary wpstg-button" data-action="wpstg_scanning">
        <?php echo esc_html__("Create Staging Site", "wp-staging") ?>
    </button>
</div>

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

<div id="wpstg-no-staging-site-results" class="wpstg-clone" <?php echo $stagingSites !== [] ? 'style="display: none;"' : '' ?> >
    <img class="wpstg--dashicons" src="<?php echo esc_url($iconPath); ?>" alt="cloud">
    <div class="no-staging-site-found-text">
        <?php esc_html_e('No Staging Site found. Create your first Staging Site above!', 'wp-staging'); ?>
    </div>
</div>

<?php Hooks::doAction(TemplateEngine::HOOK_RENDER_PRO_TEMPLATES); ?>

<!-- Remove Clone -->
<div id="wpstg-removing-clone">

</div>
<!-- /Remove Clone -->
