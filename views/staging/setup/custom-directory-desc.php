<?php

use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Framework\Facades\Escape;

/**
 * This file is currently used in both FREE and PRO version.
 * @var SystemInfo $systemInfo
 */

?>

<strong> <?php esc_html_e('You can copy the staging site to a custom directory and can use a different hostname.', 'wp-staging'); ?></strong>
<br/> <br/>
<?php echo sprintf(
    Escape::escapeHtml(__('<strong>Destination Path:</strong> An absolute path like <code>/www/public_html/dev</code>. File permissions should be 755 and it must be writeable by php user <code>%s</code>', 'wp-staging')),
    esc_html($systemInfo->getPHPUser())
); ?>
<br/> <br/>
<?php echo Escape::escapeHtml(__('<strong>Target Hostname:</strong> The hostname of the destination site, for instance <code>https://subdomain.example.com</code> or <code>https://example.com/staging</code>', 'wp-staging')) ?>
<br/> <br/>
<?php esc_html_e('Make sure the hostname points to the destination directory from above.', 'wp-staging'); ?>
