<?php

use WPStaging\Core\WPStaging;

$numberOfLoadingBars      = 15;
$canCreateTemporaryLogins = false;
$upgradeUrl               = 'https://wp-staging.com/#pricing';
$isValidLicense           = WPStaging::isValidLicense();

if ($isValidLicense && class_exists('WPStaging\Pro\License\Licensing', false)) {
    $licenseData              = get_option('wpstg_license_status');
    $licensing                = new \WPStaging\Pro\License\Licensing();
    $licenseId                = empty($licenseData->license_id) ? '' : $licenseData->license_id;
    $canCreateTemporaryLogins = $licensing->isAgencyOrDeveloperPlan();
    $upgradeUrl               = "https://wp-staging.com/checkout/?nocache=true&edd_action=sl_license_upgrade&license_id=$licenseId&upgrade_id=" . \WPStaging\Pro\License\Licensing::DEVELOPER_LICENSE_UPGRADE_PLAN_KEY;
}

include(WPSTG_PLUGIN_DIR . 'views/_main/loading-placeholder.php');
?>

<div class="wpstg-tab-temporary-logins">
    <div class="wpstg-temporary-logins-heading">
        <strong class="wpstg-fs-18"><?php esc_html_e('Temporary Logins Without Password', 'wp-staging');?></strong>
        <?php if (!$isValidLicense) :?>
            <a href="https://wp-staging.com/docs/create-magic-login-links/" target="_blank" class="wpstg-button wpstg-button--primary">
                <?php echo esc_html__('Open Preview - ', 'wp-staging'); ?>
                <span class="wpstg--red-warning"><?php echo esc_html('Get WP Staging Pro'); ?> </span>
            </a>
        <?php endif;?>
    </div>
    <p>
        <?php esc_html_e('Create and share temporary login links for this website that do not require a password and automatically expire after a specific time.', 'wp-staging') ?>
        <br>
        <?php esc_html_e('These links can be used to give developers or clients access to your website, ensuring the link becomes invalid after the given period.', 'wp-staging') ?>
        <?php if (!$isValidLicense) :?>
            <br>
            <?php echo sprintf(esc_html__('That is a %s feature.', 'wp-staging'), '<a href="https://wp-staging.com/#pricing" target="_blank" rel="noopener">WP Staging Pro</a>') ?>
        <?php endif;?>
    </p>
    <?php if ($isValidLicense && !$canCreateTemporaryLogins) :?>
        <p class="wpstg--red-warning">
            <?php echo sprintf(
                esc_html__('You need a WP Staging Developer plan or higher. Please %s your license.', 'wp-staging'),
                '<a href="' . esc_url($upgradeUrl) . '" target="_blank"> ' . esc_html__("upgrade", "wp-staging") . '</a>'
            );?>
        </p>
    <?php endif;?>
    <div class="wpstg-temp-login-header-container">
        <?php if ($isValidLicense) :?>
        <button class="wpstg-button wpstg-blue-primary" id="wpstg-create-temp-login" <?php echo  $canCreateTemporaryLogins ? '' : 'disabled'; ?> >
            <?php esc_html_e('Create Temporary Login', 'wp-staging') ?>
        </button>
        <?php endif;?>
    </div>
    <div id="wpstg-temporary-logins-wrapper"></div>
</div>
