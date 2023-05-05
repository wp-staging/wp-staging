<?php

/**
 * @var $viewsNoticesPath
 * @see \WPStaging\Framework\Support\ThirdParty\WordFence::showNotice
 */

use WPStaging\Framework\Notices\Notices;
use WPStaging\Framework\Support\ThirdParty\WordFence;

?>
<div class="notice notice-warning wpstg-wordfence-userini-renamed-notice">
    <p>
        <strong><?php esc_html_e('WP STAGING - Wordfence firewall deactivated', 'wp-staging'); ?></strong> <br/>
        <?php esc_html_e('We`ve disabled the WordFence Web Application Firewall on this site by renaming the WordFence file user.ini to make sure this staging site will work perfectly fine.', 'wp-staging'); ?> <br/>
        <?php esc_html_e('When you push this site to live, your WordFence firewall settings on the live site will not be affected by this step.', 'wp-staging'); ?> <br/>
    </p>
        <ul>
          <li>- <?php echo sprintf(__('<a href="%s" target="_blank">Read this</a> why we had to disable the firewall and how you can activate it if you like to.', 'wp-staging'), 'https://wp-staging.com/docs/wordfence-fatal-error-after-migration/'); ?></li>
        </ul>
    <p>
    <?php Notices::renderNoticeDismissAction(
        $viewsNoticesPath,
        WordFence::NOTICE_NAME,
        '.wpstg_dismiss_wordfence_userini_renamed_notice',
        '.wpstg-wordfence-userini-renamed-notice'
    ) ?>
    </p>
</div>
