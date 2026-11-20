<?php

/**
 * Nginx rules for the folders holding WP STAGING logs and working files, while the check finds them readable.
 * @see \WPStaging\Backup\Security\PrivateDirectorySecurityCheck
 */

use WPStaging\Backup\Security\BackupDirectorySecurityCheck;
use WPStaging\Backup\Security\PrivateDirectorySecurityCheck;
use WPStaging\Core\WPStaging;

$privateDirectoryCheck = WPStaging::make(PrivateDirectorySecurityCheck::class);
if ($privateDirectoryCheck->getStatus() !== BackupDirectorySecurityCheck::STATUS_PUBLICLY_REACHABLE) {
    return;
}

$snippetId    = 'wpstg-private-directory-nginx-rule';
$snippetLines = [[['# WP STAGING: block web access to logs and working files', 'comment']]];
foreach ($privateDirectoryCheck->getUrlPaths() as $privateUrlPath) {
    $snippetLines[] = [['location', 'keyword'], [' ^~ ', 'plain'], [$privateUrlPath, 'value'], [' { ', 'plain'], ['deny', 'keyword'], [' all; }', 'plain']];
}
?>
<div id="wpstg-private-directory-notice" class="wpstg-mb-4 wpstg-rounded-md wpstg-border wpstg-border-solid wpstg-p-3 wpstg-text-sm wpstg-border-amber-300 wpstg-bg-amber-50 dark:wpstg-border-amber-500/40 dark:wpstg-bg-amber-400/[0.12]">
    <details open>
        <summary class="wpstg-cursor-pointer wpstg-select-none wpstg-font-medium">
            <?php esc_html_e('WP STAGING logs and working files are publicly accessible', 'wp-staging'); ?>
        </summary>
        <div class="wpstg-mt-2 wpstg-space-y-3">
            <p class="wpstg-m-0"><?php esc_html_e('This server runs Nginx, which does not read the protection rules WP STAGING puts inside these folders. Add these rules to the server { ... } block of this site, test and reload Nginx, then click Check protection again.', 'wp-staging'); ?></p>
            <?php require WPSTG_VIEWS_DIR . 'backup/partials/code-snippet.php'; ?>
            <button type="button" class="wpstg-btn wpstg-btn-sm wpstg-btn-secondary" id="wpstg-run-private-directory-check"><?php esc_html_e('Check protection again', 'wp-staging'); ?></button>
        </div>
    </details>
</div>
