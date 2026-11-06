<?php

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Staging\Renderer\SetupRenderer;

if (!defined("WPINC")) {
    die();
}

/**
 * @var BackupMetadata $info
 * @var bool[] $excluded
 * @var bool[] $replaced
 * @var int $excludedSize
 * @var string $maskedBackupFileName
 * @var bool $isMultipartBackup
 *
 * This file is called from WPStaging\Backup\Ajax\FileInfo.
 */

$renderer = new SetupRenderer();
$urlAssets = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';

$backupContainsIcons = [
    'plugins'   => 'admin-plugins',
    'muPlugins' => 'plugins-checked',
    'themes'    => 'layout',
    'uploads'   => 'images-alt',
    'lang'      => 'file',
    'dropins'   => 'settings',
    'wpContent' => 'admin-generic',
    'wpRoot'    => 'root-folder',
];

$backupName                 = trim((string)$info->getName());
$hasBackupName              = $backupName !== '';
$backupSourceSiteUrl        = untrailingslashit((string)$info->getSiteUrl());
$currentSiteUrl             = untrailingslashit(site_url('/'));
$backupSourceSiteComparable = (string)preg_replace('#^https?://#i', '', strtolower($backupSourceSiteUrl));
$currentSiteComparable      = (string)preg_replace('#^https?://#i', '', strtolower($currentSiteUrl));
$isSameSiteRestore          = $backupSourceSiteComparable === $currentSiteComparable;
$restoreSiteRelationLabel   = $isSameSiteRestore ? __('same site', 'wp-staging') : __('different site', 'wp-staging');
$restoreSiteRelationClass   = $isSameSiteRestore
    ? 'wpstg-restore-summary-site-badge--same'
    : 'wpstg-restore-summary-site-badge--different';

$isNetworkRestore = $info->getBackupType() === BackupMetadata::BACKUP_TYPE_MULTISITE;
$isSubsiteRestore = is_multisite() && !$isNetworkRestore;
$databaseRestored = $info->getIsExportingDatabase() && !$excluded['database'];
$backupSize       = (int)$info->getBackupSize();
$indexPartSize    = $info->getIndexPartSize();

$fileCards = [
    [
        'icon'     => $backupContainsIcons['plugins'],
        'title'    => __('Plugins', 'wp-staging'),
        'contains' => $info->getIsExportingPlugins(),
        'excluded' => $excluded['plugins'],
        'meta'     => $replaced['plugins'] ? __('Plugins will be replaced.', 'wp-staging') : __('Plugins will be added.', 'wp-staging'),
    ],
    [
        'icon'     => $backupContainsIcons['muPlugins'],
        'title'    => __('MU-Plugins', 'wp-staging'),
        'contains' => $info->getIsExportingMuPlugins(),
        'excluded' => $excluded['muPlugins'],
        'meta'     => $replaced['muPlugins'] ? __('Mu-plugins will be replaced.', 'wp-staging') : __('Mu-plugins will be added.', 'wp-staging'),
    ],
    [
        'icon'     => $backupContainsIcons['themes'],
        'title'    => __('Themes', 'wp-staging'),
        'contains' => $info->getIsExportingThemes(),
        'excluded' => $excluded['themes'],
        'meta'     => $replaced['themes'] ? __('Themes will be replaced.', 'wp-staging') : __('Themes will be added.', 'wp-staging'),
    ],
    [
        'icon'     => $backupContainsIcons['uploads'],
        'title'    => __('Uploads & media', 'wp-staging'),
        'contains' => $info->getIsExportingUploads(),
        'excluded' => $excluded['uploads'],
        'meta'     => $replaced['uploads'] ? __('Media files and images will be replaced.', 'wp-staging') : __('Media files and images will be added.', 'wp-staging'),
    ],
    [
        'icon'     => $backupContainsIcons['lang'],
        'title'    => __('Language files', 'wp-staging'),
        'contains' => !empty($indexPartSize['langSize']),
        'excluded' => $excluded['lang'],
        'meta'     => __('Language files included', 'wp-staging'),
    ],
    [
        'icon'     => $backupContainsIcons['dropins'],
        'title'    => __('WordPress drop-ins', 'wp-staging'),
        'contains' => !empty($indexPartSize['dropinsSize']),
        'excluded' => $excluded['wpContent'],
        'meta'     => __('Drop-in files included', 'wp-staging'),
    ],
    [
        'icon'     => $backupContainsIcons['wpContent'],
        'title'    => __('wp-content files', 'wp-staging'),
        'contains' => $info->getIsExportingOtherWpContentFiles(),
        'excluded' => $excluded['wpContent'],
        'meta'     => $replaced['wpContent'] ? __('Other files in wp-content folder will be replaced.', 'wp-staging') : __('Other files in wp-content folder will be added.', 'wp-staging'),
    ],
    [
        'icon'     => $backupContainsIcons['wpRoot'],
        'title'    => __('WP root files', 'wp-staging'),
        'contains' => $info->getIsExportingOtherWpRootFiles(),
        'excluded' => $excluded['wpRoot'],
        'meta'     => __('Other files in WP root folder will replace matching files or be added.', 'wp-staging'),
    ],
];

$fileCardsInBackup = array_values(array_filter($fileCards, function (array $fileCard): bool {
    return $fileCard['contains'];
}));
$hasFilesInBackup = count($fileCardsInBackup) > 0;
$hasFilesRestored = count(array_filter($fileCardsInBackup, function (array $fileCard): bool {
    return !$fileCard['excluded'];
})) > 0;
$hasExcludedFileParts = count(array_filter($fileCardsInBackup, function (array $fileCard): bool {
    return $fileCard['excluded'];
})) > 0;

if (!$info->getIsExportingDatabase()) {
    $databaseSummaryLabel = __('Not included', 'wp-staging');
} elseif ($excluded['database']) {
    $databaseSummaryLabel = __('Excluded by filter', 'wp-staging');
} else {
    $databaseSummaryLabel = __('Included', 'wp-staging');
}

if (!$hasFilesInBackup) {
    $filesSummaryLabel = __('Not included', 'wp-staging');
} elseif (!$hasFilesRestored) {
    $filesSummaryLabel = __('Excluded by filter', 'wp-staging');
} elseif ($hasExcludedFileParts) {
    $filesSummaryLabel = __('Some excluded by filter', 'wp-staging');
} else {
    $filesSummaryLabel = __('Included', 'wp-staging');
}
$restoreActionSummary = __('No backup contents will be restored.', 'wp-staging');
if ($databaseRestored && $hasFilesRestored) {
    $restoreActionSummary = __('Database tables and files will be restored on this site.', 'wp-staging');
} elseif ($databaseRestored) {
    $restoreActionSummary = __('Database tables will be restored on this site.', 'wp-staging');
} elseif ($hasFilesRestored) {
    $restoreActionSummary = __('Files will be restored on this site.', 'wp-staging');
}

$restoreImpactWarnings = [];
if ($databaseRestored) {
    $restoreImpactWarnings[] = __("This site's database will be replaced. You may need to log in again with the backup's credentials.", 'wp-staging');
}

if ($hasFilesRestored) {
    $restoreImpactWarnings[] = __('Matching files will be replaced.', 'wp-staging');
}

$removedFileGroups = [];
if ($info->getIsExportingPlugins() && !$excluded['plugins'] && $replaced['plugins']) {
    $removedFileGroups[] = __('plugins', 'wp-staging');
}

if ($info->getIsExportingThemes() && !$excluded['themes'] && $replaced['themes']) {
    $removedFileGroups[] = __('themes', 'wp-staging');
}

if ($info->getIsExportingMuPlugins() && !$excluded['muPlugins'] && $replaced['muPlugins']) {
    $removedFileGroups[] = __('MU-plugins', 'wp-staging');
}

if ($info->getIsExportingUploads() && !$excluded['uploads'] && $replaced['uploads']) {
    $removedFileGroups[] = __('media', 'wp-staging');
}

if ($info->getIsExportingOtherWpContentFiles() && !$excluded['wpContent'] && $replaced['wpContent']) {
    $removedFileGroups[] = __('other wp-content files', 'wp-staging');
}

if ($removedFileGroups !== []) {
    $restoreImpactWarnings[] = sprintf(
        /* translators: %s: List of restored file groups whose destination is cleaned before restore. */
        __('Existing %s not in the backup will be deleted.', 'wp-staging'),
        wp_sprintf('%l', $removedFileGroups)
    );
}

$restoreSize = $excludedSize > 0 ? max(0, $backupSize - $excludedSize) : $backupSize;
$restoreSizeLabel = $restoreSize > 0 ? '~ ' . size_format($restoreSize, 2) : __('Unknown', 'wp-staging');

$restoreScopeWarning = '';
if ($databaseRestored) {
    if ($isSubsiteRestore) {
        $restoreScopeWarning = __("Restoring this backup will only replace your current subsite's database tables. Users will be imported from the backup which doesn't already exists in your site. After the process has finished, you will need to log in again using the username/password combination for your backed up site.", 'wp-staging');
    } elseif ($isNetworkRestore) {
        $restoreScopeWarning = __('Also all current network sites will be deleted and replaced by the sites in the backup.', 'wp-staging');
    }
}

?>
<div
    id="wpstg-confirm-backup-restore-wrapper"
    class="wpstg-restore-setup-modal wpstg-staging-setup-modal wpstg-text-left"
    role="dialog"
    aria-modal="true"
    aria-labelledby="wpstg-restore-modal-title"
>
    <button type="button" class="wpstg-restore-modal-close wpstg-staging-modal-close" data-action="close-modal" aria-label="<?php esc_attr_e('Close', 'wp-staging'); ?>">
        <?php $renderer->icon('close'); ?>
    </button>

    <header class="wpstg-restore-setup-modal__header wpstg-staging-setup-modal__header">
        <span class="wpstg-restore-header-badge" aria-hidden="true"><?php $renderer->icon('refresh', 'wpstg-h-5 wpstg-w-5'); ?></span>
        <div class="wpstg-min-w-0 wpstg-flex-1">
            <h1 id="wpstg-restore-modal-title" class="wpstg-restore-header-title"><?php esc_html_e('Restore Backup', 'wp-staging'); ?></h1>
            <p class="wpstg-restore-header-subtitle"><?php esc_html_e('Review what will be replaced before restoring to this site.', 'wp-staging'); ?></p>
        </div>
    </header>

    <div class="wpstg-restore-setup-modal__body wpstg-staging-setup-modal__body">
        <main class="wpstg-restore-setup-modal__main wpstg-staging-setup-modal__main">
            <section>
                <div class="wpstg-restore-direction">
                    <div class="wpstg-restore-direction__node">
                        <span class="wpstg-restore-direction__label"><?php esc_html_e('Backup', 'wp-staging'); ?></span>
                        <span class="wpstg-restore-direction__name"><?php $renderer->icon('archive', 'wpstg-restore-direction__icon'); ?><span class="wpstg-truncate"><?php echo esc_html($maskedBackupFileName); ?></span></span>
                        <span class="wpstg-restore-direction__url"><?php echo esc_html($backupSourceSiteUrl); ?></span>
                    </div>
                    <div class="wpstg-restore-direction__arrow" aria-hidden="true">
                        <?php $renderer->icon('arrow-right', 'wpstg-h-[18px] wpstg-w-[18px]', 2); ?>
                        <span class="wpstg-restore-direction__arrow-label"><?php esc_html_e('restores to', 'wp-staging'); ?></span>
                    </div>
                    <div class="wpstg-restore-direction__node wpstg-restore-direction__node--target">
                        <span class="wpstg-restore-direction__label"><?php esc_html_e('Restore target', 'wp-staging'); ?></span>
                        <span class="wpstg-restore-direction__name"><?php $renderer->icon('globe', 'wpstg-restore-direction__icon wpstg-restore-direction__icon--target'); ?><span class="wpstg-truncate"><?php esc_html_e('Current site', 'wp-staging'); ?></span></span>
                        <span class="wpstg-restore-direction__url"><?php echo esc_html($currentSiteUrl); ?></span>
                    </div>
                </div>
            </section>

            <section>
                <div class="wpstg-restore-hero">
                    <div class="wpstg-restore-hero__head">
                        <span class="wpstg-restore-hero__icon" aria-hidden="true"><?php $renderer->icon('check', 'wpstg-h-4 wpstg-w-4', 2.6); ?></span>
                        <div class="wpstg-min-w-0">
                            <h2 class="wpstg-restore-hero__title"><?php esc_html_e('Ready to restore', 'wp-staging'); ?></h2>
                            <p class="wpstg-restore-hero__body"><?php echo esc_html($restoreActionSummary); ?></p>
                        </div>
                    </div>
                    <div class="wpstg-restore-file-grid">
                        <?php if ($info->getIsExportingDatabase()) : ?>
                            <div class="wpstg-restore-file-card<?php echo $excluded['database'] ? ' wpstg-restore-file-card--excluded' : ''; ?>">
                                <?php $renderer->icon('database', 'wpstg-restore-file-card__icon'); ?>
                                <span class="wpstg-min-w-0 wpstg-flex-1">
                                    <span class="wpstg-restore-file-card__title"><?php esc_html_e('Database', 'wp-staging'); ?></span>
                                    <small><?php echo esc_html($excluded['database'] ? __('Excluded by filter', 'wp-staging') : __('Database tables included', 'wp-staging')); ?></small>
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($fileCardsInBackup as $fileCard) : ?>
                            <div class="wpstg-restore-file-card<?php echo $fileCard['excluded'] ? ' wpstg-restore-file-card--excluded' : ''; ?>">
                                <img class="wpstg-restore-file-card__icon wpstg--dashicons" src="<?php echo esc_url($urlAssets . 'svg/' . $fileCard['icon'] . '.svg'); ?>" alt="" aria-hidden="true" />
                                <span class="wpstg-min-w-0 wpstg-flex-1">
                                    <span class="wpstg-restore-file-card__title"><?php echo esc_html($fileCard['title']); ?></span>
                                    <small><?php echo esc_html($fileCard['excluded'] ? __('Excluded by filter', 'wp-staging') : $fileCard['meta']); ?></small>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section>
                <div class="wpstg-restore-warning">
                    <span class="wpstg-restore-warning__icon" aria-hidden="true"><?php $renderer->icon('warning', 'wpstg-h-4 wpstg-w-4'); ?></span>
                    <p class="wpstg-restore-warning__text">
                        <?php echo esc_html(implode(' ', $restoreImpactWarnings)); ?>
                        <a href="https://wp-staging.com/docs/backup-faq/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Read the FAQ', 'wp-staging'); ?></a>
                        <?php if ($restoreScopeWarning !== '') : ?>
                            <br><span class="wpstg-restore-warning__scope"><?php echo esc_html($restoreScopeWarning); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </section>
        </main>

        <aside class="wpstg-restore-setup-modal__summary wpstg-staging-setup-modal__summary" aria-label="<?php esc_attr_e('Restore Summary', 'wp-staging'); ?>">
            <div class="wpstg-restore-summary-sticky">
                <section class="wpstg-restore-summary-block">
                    <h2 class="wpstg-restore-summary__heading"><?php $renderer->icon('refresh', 'wpstg-restore-summary__heading-icon'); ?><?php esc_html_e('Restore Summary', 'wp-staging'); ?></h2>
                    <dl class="wpstg-restore-summary-list">
                        <?php if ($hasBackupName) : ?>
                            <div class="wpstg-restore-summary-row wpstg-restore-summary-row--stacked">
                                <dt><?php esc_html_e('Backup Name', 'wp-staging'); ?></dt>
                                <dd>
                                    <?php echo esc_html($backupName); ?>
                                    <?php if ($isMultipartBackup) : ?>
                                        <span class="wpstg-restore-summary-multipart-badge"><?php esc_html_e('Multipart Backup', 'wp-staging'); ?></span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        <?php elseif ($isMultipartBackup) : ?>
                            <div class="wpstg-restore-summary-row wpstg-restore-summary-row--stacked">
                                <dt><?php esc_html_e('Backup', 'wp-staging'); ?></dt>
                                <dd><span class="wpstg-restore-summary-multipart-badge"><?php esc_html_e('Multipart Backup', 'wp-staging'); ?></span></dd>
                            </div>
                        <?php endif; ?>
                        <div class="wpstg-restore-summary-row wpstg-restore-summary-row--stacked">
                            <dt><?php esc_html_e('Source Site', 'wp-staging'); ?></dt>
                            <dd><?php echo esc_html($backupSourceSiteUrl); ?></dd>
                        </div>
                        <div class="wpstg-restore-summary-row wpstg-restore-summary-row--stacked wpstg-restore-summary-row--target">
                            <dt><?php esc_html_e('Target Site', 'wp-staging'); ?></dt>
                            <dd>
                                <span class="wpstg-restore-summary-url"><?php echo esc_html($currentSiteUrl); ?></span>
                                <span class="wpstg-restore-summary-site-badge <?php echo esc_attr($restoreSiteRelationClass); ?>"><?php echo esc_html($restoreSiteRelationLabel); ?></span>
                            </dd>
                        </div>
                    </dl>
                    <hr class="wpstg-restore-summary-divider" />
                    <dl class="wpstg-restore-summary-list wpstg-gap-3">
                        <div class="wpstg-restore-summary-row">
                            <dt><?php esc_html_e('Database', 'wp-staging'); ?></dt>
                            <dd><?php echo esc_html($databaseSummaryLabel); ?></dd>
                        </div>
                        <div class="wpstg-restore-summary-row">
                            <dt><?php esc_html_e('Files', 'wp-staging'); ?></dt>
                            <dd><?php echo esc_html($filesSummaryLabel); ?></dd>
                        </div>
                        <div class="wpstg-restore-summary-row">
                            <dt><?php esc_html_e('Restore Size', 'wp-staging'); ?></dt>
                            <dd><?php echo esc_html($restoreSizeLabel); ?></dd>
                        </div>
                    </dl>
                </section>
                <section class="wpstg-restore-summary-warning">
                    <h3><?php $renderer->icon('warning', 'wpstg-h-4 wpstg-w-4'); ?><?php esc_html_e('Cannot be undone', 'wp-staging'); ?></h3>
                    <p><?php esc_html_e('The current site data will be replaced. Make sure you have a recent backup before continuing.', 'wp-staging'); ?></p>
                </section>
            </div>
        </aside>
    </div>

    <footer class="wpstg-restore-setup-modal__footer wpstg-staging-setup-modal__footer">
        <label class="wpstg-restore-confirm-row" for="wpstg-restore-confirm-overwrite">
            <input type="checkbox" class="wpstg-checkbox wpstg-checkbox-sm wpstg-restore-confirm-checkbox" id="wpstg-restore-confirm-overwrite">
            <span class="wpstg-restore-confirm-row__text"><?php esc_html_e('I understand this will overwrite the current site and cannot be undone.', 'wp-staging'); ?></span>
        </label>
        <div class="wpstg-restore-footer-actions">
            <button type="button" class="wpstg-restore-modal-cancel wpstg-btn wpstg-btn-md wpstg-h-11 wpstg-rounded-lg wpstg-py-0 wpstg-leading-none wpstg-btn-secondary wpstg-px-5" data-action="close-modal"><?php esc_html_e('Cancel', 'wp-staging'); ?></button>
            <button type="button" class="wpstg-setup-cta wpstg-setup-cta--blue" id="wpstg-restore-confirm-btn" data-action="start-restore" disabled>
                <?php $renderer->icon('refresh', 'wpstg-h-4 wpstg-w-4', 2.2); ?><?php esc_html_e('Confirm restore', 'wp-staging'); ?>
            </button>
        </div>
    </footer>
</div>
