<?php

/**
 * "Upgrade to Pro" card below the local backup list.
 *
 * @see views/backup/listing.php
 * @see \WPStaging\Basic\Notices\BackupProCardNotice
 */

use WPStaging\Basic\Notices\BackupProCardNotice;
use WPStaging\Framework\Language\Language;

$proUpgradeCard = [
    'cssClass'       => 'wpstg-backup-pro-card',
    'noticeId'       => BackupProCardNotice::NOTICE_ID,
    'headline'       => __('Keep a copy of your site off this server, automatically', 'wp-staging'),
    'body'           => __('Your backups stay on this server until you download them. WP Staging Pro sends each backup off-site on your schedule and restores it wherever you need: on this site, another domain or a new host.', 'wp-staging'),
    'features'       => [
        __('Off-site backups to Google Drive, Dropbox, Amazon S3, SFTP and more (Business and higher)', 'wp-staging'),
        __('Multiple backup schedules at the times you choose', 'wp-staging'),
        __('Restore or move the site to another domain or server', 'wp-staging'),
    ],
    'trustLine'      => __('Fast backups and restores, developed in Germany since 2015. Every release runs through hundreds of automated tests.', 'wp-staging'),
    'upgradeUrl'     => Language::getUpgradeUrl('backup_dashboard_upgrade_card'),
    'upgradeContext' => 'backup_dashboard_upgrade_card',
    'compareUrl'     => Language::addClientAttribution(Language::localizeUrl('https://wp-staging.com/pro-features/?utm_source=wp-admin&utm_medium=backup_dashboard&utm_campaign=pro_card')),
];

require WPSTG_VIEWS_DIR . 'ads/pro-upgrade-card.php';
