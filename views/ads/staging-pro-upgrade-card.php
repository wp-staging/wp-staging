<?php

/**
 * "Upgrade to Pro" card below the staging site list.
 *
 * @see views/staging/listing.php
 * @see \WPStaging\Basic\Notices\GeneralProCardNotice
 */

use WPStaging\Basic\Notices\GeneralProCardNotice;
use WPStaging\Framework\Language\Language;

$proUpgradeCard = [
    'cssClass'       => 'wpstg-general-pro-card',
    'noticeId'       => GeneralProCardNotice::NOTICE_ID,
    'headline'       => __('Need push-to-live, cloud backups, or migrations?', 'wp-staging'),
    'body'           => __('WP Staging Pro adds one-click deployment, off-site cloud backups, unlimited schedules, site migrations, Multisite support, and developer workflows.', 'wp-staging'),
    'features'       => [
        __('Push to production', 'wp-staging'),
        __('Cloud backups', 'wp-staging'),
        __('Unlimited schedules', 'wp-staging'),
        __('Migrations', 'wp-staging'),
        __('Multisite', 'wp-staging'),
        __('CLI and local Docker', 'wp-staging'),
    ],
    'upgradeUrl'     => Language::getUpgradeUrl('dashboard_upgrade_card'),
    'upgradeContext' => 'dashboard_upgrade_card',
    'compareUrl'     => Language::addClientAttribution(Language::localizeUrl('https://wp-staging.com/pro-features/?utm_source=wp-admin&utm_medium=staging_dashboard&utm_campaign=pro_card')),
];

require WPSTG_VIEWS_DIR . 'ads/pro-upgrade-card.php';
