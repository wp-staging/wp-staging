<?php

/**
 * Small, expandable hint that the permanent backup folder can be reached over HTTP.
 * @see \WPStaging\Backup\Security\BackupDirectoryNoticeService::getNoticeData
 */

use WPStaging\Backup\Security\BackupDirectoryNoticeService;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Core\WPStaging;

$backupDirectoryNotice = WPStaging::make(BackupDirectoryNoticeService::class);
if (!$backupDirectoryNotice->shouldShow()) {
    return;
}

$noticeData    = $backupDirectoryNotice->getNoticeData();
$isWarning     = $noticeData['level'] === BackupDirectoryNoticeService::LEVEL_WARNING;
$backupDirName = BackupsFinder::FILTER_BACKUP_DIRECTORY;
$noticeClasses = $isWarning
    ? 'wpstg-border-amber-300 wpstg-bg-amber-50 dark:wpstg-border-amber-500/40 dark:wpstg-bg-amber-400/[0.12]'
    : 'wpstg-border-dim wpstg-bg-gray-50 dark:wpstg-bg-slate-950/45';

$backupPathSnippet = [
    [[$noticeData['backupUrlPath'], 'value']],
];

$nginxSnippet = [
    [['# WP STAGING: block direct access to permanent backup files', 'comment']],
    [['location', 'keyword'], [' ^~ ', 'plain'], [$noticeData['backupUrlPath'], 'value'], [' {', 'plain']],
    [['    deny', 'keyword'], [' all;', 'plain']],
    [['    return', 'keyword'], [' ', 'plain'], ['403', 'value'], [';', 'plain']],
    [['}', 'plain']],
];

$nginxReloadSnippet = [
    [['$ ', 'prompt'], ['nginx', 'keyword'], [' -t', 'plain']],
    [['$ ', 'prompt'], ['systemctl', 'keyword'], [' reload nginx', 'plain']],
];

$filterSnippet = [
    [['<?php', 'keyword']],
    [],
    [['add_filter', 'keyword'], ['(', 'plain'], ["'" . $backupDirName . "'", 'value'], [', ', 'plain'], ['function', 'keyword'], [' ($directory) {', 'plain']],
    [['    return', 'keyword'], [' ', 'plain'], ["'/absolute/private/path/wp-staging-backups'", 'value'], [';', 'plain']],
    [['});', 'plain']],
];
?>
<div class="wpstg-backup-hardening-notice wpstg-mb-4 wpstg-rounded-md wpstg-border wpstg-border-solid wpstg-p-3 wpstg-text-sm <?php echo esc_attr($noticeClasses); ?>">
    <details>
        <summary class="wpstg-cursor-pointer wpstg-select-none wpstg-font-medium">
            <?php esc_html_e('Additional backup folder protection recommended', 'wp-staging'); ?>
        </summary>

        <div class="wpstg-mt-2 wpstg-space-y-3">
            <p class="wpstg-m-0">
                <?php esc_html_e('Our check found that files in your backup folder can be opened by anyone who knows their exact web address. WP STAGING gives every backup an unpredictable file name. Blocking direct web access to the folder adds a second layer of protection.', 'wp-staging'); ?>
            </p>

            <?php if ($noticeData['directoryListing']) : ?>
                <p class="wpstg-m-0 wpstg-font-medium">
                    <?php esc_html_e('This server also shows visitors a list of the files in the backup folder, so the backup file names are not secret. Block the folder as soon as possible.', 'wp-staging'); ?>
                </p>
            <?php endif; ?>

            <p class="wpstg-m-0">
                <?php if ($noticeData['isTransferSessionEnabled']) : ?>
                    <?php esc_html_e('Downloads you start in WP STAGING never use this folder\'s address. Each download gets a temporary link that is created when you click and deleted automatically after it expires. If no temporary link can be created, the download stops with an error message; it never falls back to the folder\'s address.', 'wp-staging'); ?>
                <?php else : ?>
                    <?php esc_html_e('WP STAGING offers no browser download while temporary links are switched off.', 'wp-staging'); ?>
                <?php endif; ?>
            </p>

            <div>
                <p class="wpstg-m-0 wpstg-mb-1 wpstg-font-medium"><?php esc_html_e('Ask your hosting provider to block direct web access to this folder:', 'wp-staging'); ?></p>
                <?php
                $snippetId    = 'wpstg-backup-hardening-path';
                $snippetLines = $backupPathSnippet;
                require WPSTG_VIEWS_DIR . 'backup/partials/code-snippet.php';
                ?>
            </div>

            <?php if ($noticeData['isNginx']) : ?>
                <div>
                    <p class="wpstg-m-0 wpstg-mb-1">
                        <?php esc_html_e('This server runs Nginx, which does not read protection rules from inside a folder. Send your hosting provider this rule for the server { ... } block of this site. If you manage the server yourself, add it there, then test and reload Nginx.', 'wp-staging'); ?>
                    </p>
                    <div class="wpstg-my-2 wpstg-space-y-2">
                        <?php
                        $snippetId    = 'wpstg-backup-hardening-nginx-rule';
                        $snippetLines = $nginxSnippet;
                        require WPSTG_VIEWS_DIR . 'backup/partials/code-snippet.php';

                        $snippetId    = 'wpstg-backup-hardening-nginx-reload';
                        $snippetLines = $nginxReloadSnippet;
                        require WPSTG_VIEWS_DIR . 'backup/partials/code-snippet.php';
                        ?>
                    </div>
                    <?php if ($noticeData['isTransferSessionEnabled']) : ?>
                    <p class="wpstg-m-0 wpstg-font-medium">
                        <?php
                        printf(
                            /* translators: %s: URL path of the temporary transfer folder */
                            esc_html__('Do not block %s. WP STAGING uses this separate folder for the temporary download links.', 'wp-staging'),
                            esc_html($noticeData['transferUrlPath'])
                        );
                        ?>
                    </p>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <p class="wpstg-m-0">
                    <?php esc_html_e('WP STAGING has already added protection rules to the folder. Your server may need additional configuration for these rules to take effect.', 'wp-staging'); ?>
                </p>
            <?php endif; ?>

            <p class="wpstg-m-0">
                <?php esc_html_e('Once your hosting provider has made the change, click Check protection again.', 'wp-staging'); ?>
            </p>

            <details>
                <summary class="wpstg-cursor-pointer wpstg-select-none wpstg-font-medium">
                    <?php esc_html_e('Advanced: store backups outside the public web folder', 'wp-staging'); ?>
                </summary>
                <div class="wpstg-mt-2">
                    <p class="wpstg-m-0 wpstg-mb-1">
                        <?php esc_html_e('Backups in a folder outside the public web folder cannot be opened through a web address at all. Pick an absolute path outside the public web folder that the web server can read and write, for example a folder next to your WordPress folder, and add this code to a small plugin in wp-content/mu-plugins/. WP STAGING does not move existing backups; move them to the new folder yourself.', 'wp-staging'); ?>
                    </p>
                    <?php
                    $snippetId    = 'wpstg-backup-hardening-filter';
                    $snippetLines = $filterSnippet;
                    require WPSTG_VIEWS_DIR . 'backup/partials/code-snippet.php';
                    ?>
                </div>
            </details>

            <div class="wpstg-flex wpstg-flex-wrap wpstg-gap-2">
                <button type="button" class="wpstg-btn wpstg-btn-sm wpstg-btn-secondary" id="wpstg-run-backup-security-check">
                    <?php esc_html_e('Check protection again', 'wp-staging'); ?>
                </button>
                <button type="button" class="wpstg-btn wpstg-btn-sm wpstg-btn-ghost" id="wpstg-dismiss-backup-security-notice">
                    <?php esc_html_e('Ignore for now', 'wp-staging'); ?>
                </button>
            </div>

            <?php if ($noticeData['checkedAt'] > 0) : ?>
                <p class="wpstg-m-0 wpstg-text-xs wpstg-text-dim-foreground">
                    <?php
                    printf(
                        /* translators: %s: formatted date and time of the last security check */
                        esc_html__('Last checked: %s', 'wp-staging'),
                        esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $noticeData['checkedAt']))
                    );
                    ?>
                </p>
            <?php endif; ?>
        </div>
    </details>
</div>
