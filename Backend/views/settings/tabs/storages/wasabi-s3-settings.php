<?php

/**
 * @var string $providerId
 */

$auth = \WPStaging\Core\WPStaging::make(\WPStaging\Pro\Backup\Storage\Storages\Wasabi\Auth::class);
$providerName = __('Wasabi S3', 'wp-staging');
$settingText = '';
$settingLink = '';
$settingText1 = '';
$settingLink1 = '';
$locationName = 'Wasabi Bucket';

$baseSettingsPath = $this->path . "views/settings/tabs/storages/base-s3-settings.php";
require_once($baseSettingsPath);
