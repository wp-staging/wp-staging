<?php

/**
 * @var string $providerId
 */

$auth = \WPStaging\Core\WPStaging::make(\WPStaging\Pro\Backup\Storage\Storages\Amazon\S3::class);
$providerName = __('Amazon S3', 'wp-staging');
$settingText = __('How to create Amazon API keys and a S3 bucket', 'wp-staging');
$settingLink = 'https://wp-staging.com/docs/how-to-backup-website-to-amazon-s3-bucket/';
$settingText1 = '';
$settingLink1 = '';

$baseSettingsPath = $this->path . "views/settings/tabs/storages/base-s3-settings.php";
require_once($baseSettingsPath);
