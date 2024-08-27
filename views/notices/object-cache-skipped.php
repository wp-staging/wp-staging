<?php

/**
 * @var $this
 * @see \WPStaging\Framework\ThirdParty\WordFence::showNotice
 */

use WPStaging\Framework\Notices\Notices;
use WPStaging\Framework\Notices\ObjectCacheNotice;

$linkToArticle = "https://wp-staging.com/docs/object-caching-and-how-to-activate-it/";

?>
<div class="notice notice-warning wpstg-skipped-object-cache-notice">
    <p>
        <?php esc_html_e('The WP_CONTENT/object-cache.php file in the backup is different from the installed version.', 'wp-staging') ?><br/>
        <?php esc_html_e('It had to be deleted when restoring the backup so as not to cause an error.', 'wp-staging') ?> <br/>
        <?php esc_html_e('You can reactivate the object cache in the corresponding caching plugin.', 'wp-staging') ?> <br/>
        <a target="_blank" href="<?php echo esc_attr($linkToArticle); ?>"><b><?php esc_html_e('Learn More', 'wp-staging') ?></b></a>
    </p>
    <p>
    <?php Notices::renderNoticeDismissAction(
        $this->viewsNoticesPath,
        ObjectCacheNotice::NOTICE_DISMISS_ACTION,
        '.wpstg_dismiss_skipped_object_cache_notice',
        '.wpstg-skipped-object-cache-notice'
    ) ?>
    </p>
</div>
