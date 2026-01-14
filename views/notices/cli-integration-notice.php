<?php
/**
 * CLI Integration Notice - Banner promoting WP Staging CLI tool
 *
 * This file contains only the banner/notice HTML that appears in the WP admin.
 * The modal content is loaded from src/views/cli/cli-integration-modal.php
 *
 * @var \WPStaging\Framework\Notices\CliIntegrationNotice $this
 *
 * Note: This file is in basic namespace, so license checking logic is hardcoded here
 * based on Licensing::isAgencyOrDeveloperPlan() and Licensing::getAvailableLicensePlansByPriceId()
 */

const WPSTG_LICENSE_VALID   = 'valid';
const WPSTG_LICENSE_EXPIRED = 'expired';


// Get license data
$license     = get_option('wpstg_license_status', false);
$licenseData = $license ? (object)$license : null;

// Check if license is valid or expired (based on Licensing::isValidOrExpiredLicenseKey())
$isValidOrExpired = false;
if ($licenseData) {
    $licenseStatus = isset($licenseData->license) ? $licenseData->license : '';
    $licenseError = isset($licenseData->error) ? $licenseData->error : '';

    // Check if disabled (based on Licensing::isDisabled())
    $isDisabled = false;
    if ($licenseStatus === 'disabled' || $licenseStatus === 'inactive' || $licenseStatus === 'invalid' || $licenseError === 'disabled') {
        $isDisabled = true;
    }


    if (!$isDisabled && ($licenseStatus === WPSTG_LICENSE_VALID || $licenseStatus === WPSTG_LICENSE_EXPIRED || $licenseError === WPSTG_LICENSE_EXPIRED)) {
        $isValidOrExpired = true;
    }
}

// Check if Developer plan or higher (based on Licensing::isAgencyOrDeveloperPlan())
$isDeveloperOrHigher = false;
$planName            = '';

if (($isValidOrExpired && $licenseData && !empty($licenseData->price_id))) {
    $priceId               = $licenseData ? (string)$licenseData->price_id : '';
    $developerPlanOrHigher = ['13', '2','12', '6', '8', '3', '14'];

    if (in_array($priceId, $developerPlanOrHigher, true)) {
        $isDeveloperOrHigher = true;
    }

    $planNames = [
        '1'  => 'Personal License',
        '15' => 'Personal License',
        '10' => 'Personal License',
        '7'  => 'Business License',
        '11' => 'Business License',
        '4'  => 'Business License',
        '13' => 'Developer License',
        '12' => 'Developer License',
        '3'  => 'Agency License',
        '14' => 'Agency License',
        '6'  => 'Developer Legacy License',
        '2'  => 'Developer License',
        '8'  => 'Developer Legacy License',
    ];

    $planName = isset($planNames[$priceId]) ? $planNames[$priceId] : '';
}

if (empty($planName)) {
    $planName = __('Unregistered', 'wp-staging');
}

// Get backup list for modal step 3
$backups = [];
if ($isDeveloperOrHigher && class_exists('\WPStaging\Backup\Ajax\FileList\ListableBackupsCollection')) {
    try {
        /** @var \WPStaging\Backup\Ajax\FileList\ListableBackupsCollection $listableBackupsCollection */
        $listableBackupsCollection = \WPStaging\Core\WPStaging::make(\WPStaging\Backup\Ajax\FileList\ListableBackupsCollection::class);
        $backups = $listableBackupsCollection->getListableBackups();
    } catch (\Exception $e) {
        $backups = [];
    }
}

$urlAssets = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';
?>

<div class="wpstg-banner wpstg-banner-cli" id="wpstg-cli-integration-banner" data-is-developer="<?php echo $isDeveloperOrHigher ? '1' : '0'; ?>">
    <div class="wpstg-banner-content">
        <div class="wpstg-banner-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
        </div>
        <div class="wpstg-banner-text">
            <h3 class="wpstg-banner-title">
                <?php esc_html_e('New: Local Docker Environments', 'wp-staging'); ?>
                <span class="wpstg-badge-new"><?php esc_html_e('NEW', 'wp-staging'); ?></span>
            </h3>
            <p class="wpstg-banner-description">
                <?php esc_html_e('Turn your WP Staging backups into local Docker sites with a single command. Standardized WordPress stacks for testing, development and easy sharing across your team.', 'wp-staging'); ?>
            </p>
            <?php if ($isDeveloperOrHigher) : ?>
                <p class="wpstg-banner-plan-info">
                    <?php
                    printf(
                        esc_html__('Included in your current %s plan.', 'wp-staging'),
                        '<strong>' . esc_html($planName) . '</strong>'
                    );
                    ?>
                </p>
            <?php else : ?>
                <p class="wpstg-banner-plan-info">
                    <?php
                    printf(
                        esc_html__('Requires a WP Staging Developer plan or higher. Your current plan: %s.', 'wp-staging'),
                        '<strong>' . esc_html($planName) . '</strong>'
                    );
                    ?>
                </p>
            <?php endif; ?>
            <div class="wpstg-banner-buttons">
                <?php if ($isDeveloperOrHigher) : ?>
                    <button type="button" class="wpstg-button wpstg-blue-primary wpstg-banner-button-primary" id="wpstg-cli-install-button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                            <path d="M12 10v6"></path>
                            <path d="M9 13h6"></path>
                            <path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"></path>
                        </svg>
                        <?php esc_html_e('Create Local Site', 'wp-staging'); ?>
                    </button>
                <?php else : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpstg-license')); ?>" target="_self" class="wpstg-button wpstg-blue-primary wpstg-banner-button-primary">
                        <?php esc_html_e('Upgrade Your Plan', 'wp-staging'); ?>
                    </a>
                <?php endif; ?>
                <a href="https://wp-staging.com/docs/set-up-wp-staging-cli/" target="_blank" rel="noreferrer noopener" class="wpstg-button wpstg-banner-button-secondary">
                    <?php esc_html_e('Learn More', 'wp-staging'); ?>
                </a>
            </div>
            <p class="wpstg-banner-disclaimer">
                <?php esc_html_e('WP Staging CLI is an independent tool and is not affiliated with, authorized, sponsored, or endorsed by Docker Inc. "Docker" and the Docker logo are trademarks or registered trademarks of Docker Inc.', 'wp-staging'); ?>
            </p>
        </div>
    </div>
    <div class="wpstg-banner-close" id="wpstg-cli-notice-close" title="<?php esc_attr_e('Close', 'wp-staging'); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wpstg-banner-close-icon">
            <path d="M18 6 6 18"/>
            <path d="m6 6 12 12"/>
        </svg>
        <span class="wpstg-banner-close-text"><?php esc_html_e('Show again later', 'wp-staging'); ?></span>
    </div>
</div>

<?php
// Include the modal content (hidden until triggered by JS)
include __DIR__ . '/../cli/cli-integration-modal.php';
?>
