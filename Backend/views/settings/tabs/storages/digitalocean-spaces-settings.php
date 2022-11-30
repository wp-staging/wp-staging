<?php

/**
 * @var string $providerId
 */

$auth = \WPStaging\Core\WPStaging::make(\WPStaging\Pro\Backup\Storage\Storages\DigitalOceanSpaces\Auth::class);
$providerName = __('DigitalOcean Spaces', 'wp-staging');
$settingText = __('Create tokens on DigitalOcean', 'wp-staging');
$settingLink = 'https://cloud.digitalocean.com/account/api/tokens';
$settingText1 = __('Create Space on DigitalOcean', 'wp-staging');
$settingLink1 = 'https://cloud.digitalocean.com/spaces/new';
$locationName = 'Space';

$baseSettingsPath = $this->path . "views/settings/tabs/storages/base-s3-settings.php";
require_once($baseSettingsPath);
