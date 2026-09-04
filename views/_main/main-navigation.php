<?php

$licenseMessage = '';
if (defined('WPSTGPRO_VERSION')) {
    $licenseMessage = isset($license->license) && $license->license === 'valid' ? '' : __('(Unregistered)', 'wp-staging');
}

$isCalledFromIndex = false;
$isBackupPage      = false;
$isStagingPage     = false;
require_once(WPSTG_VIEWS_DIR . 'navigation/web-template.php');
