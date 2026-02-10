<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Traits\NoticesTrait;

/**
 * Displays a dismissible banner promoting the WP Staging CLI tool
 *
 * The banner appears on both Staging and Backup tabs for Pro version users.
 * When dismissed, it stays hidden for 24 hours using WordPress transients.
 */
class CliIntegrationNotice
{
    use NoticesTrait;

    /**
     * @var bool Set to true to enable the CLI integration banner
     * @todo Enable this in next version when the feature is ready
     */
    const IS_ENABLED = true;

    /**
     * @var string Transient key for 24-hour dismissal
     */
    const TRANSIENT_CLI_NOTICE_DISMISSED = 'wpstg_cli_notice_dismissed';

    /**
     * @var string Option key for permanent dismissal
     */
    const OPTION_CLI_NOTICE_HIDDEN_FOREVER = 'wpstg_cli_notice_hidden_forever';

    /**
     * @var string Option key for showing dock CTA after banner dismissal
     */
    const OPTION_CLI_DOCK_CTA_SHOWN = 'wpstg_cli_dock_cta_shown';

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @param Auth $auth
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Display the CLI integration banner if conditions are met
     *
     * @return void
     */
    public function maybeShowCliNotice()
    {
        // Feature is temporarily disabled
        if (!self::IS_ENABLED) {
            return;
        }

        // Only show on WP Staging admin pages
        if (!$this->isWPStagingAdminPage()) {
            return;
        }

        // Only show in Pro version
        if (WPStaging::isBasic()) {
            return;
        }

        // Don't show if user cannot manage options
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check if notice was dismissed in last 24 hours
        if (get_transient(self::TRANSIENT_CLI_NOTICE_DISMISSED)) {
            return;
        }

        // Check if notice was dismissed forever
        if (get_option(self::OPTION_CLI_NOTICE_HIDDEN_FOREVER)) {
            return;
        }

        $notice = WPSTG_VIEWS_DIR . 'notices/cli-integration-notice.php';

        if (!file_exists($notice)) {
            return;
        }

        // Prepare variables for the view
        $isDeveloperOrHigher = $this->isDeveloperOrHigherLicense();
        $planName            = $this->getLicensePlanName();

        // Get backup list for modal step 3 (sorted by newest first)
        $backups = [];
        if ($isDeveloperOrHigher && class_exists('\WPStaging\Backup\Ajax\FileList\ListableBackupsCollection')) {
            try {
                /** @var \WPStaging\Backup\Ajax\FileList\ListableBackupsCollection $listableBackupsCollection */
                $listableBackupsCollection = WPStaging::make(\WPStaging\Backup\Ajax\FileList\ListableBackupsCollection::class);
                $backups                   = $listableBackupsCollection->getSortedListableBackups();
            } catch (\Exception $e) {
                $backups = [];
            }
        }

        $urlAssets = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';

        include $notice;
    }

    /**
     * AJAX handler to dismiss the CLI notice for 24 hours
     *
     * @return void
     */
    public function ajaxCliNoticeClose()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        set_transient(self::TRANSIENT_CLI_NOTICE_DISMISSED, true, DAY_IN_SECONDS);
        update_option(self::OPTION_CLI_DOCK_CTA_SHOWN, true, false);
        wp_send_json_success();
    }

    /**
     * AJAX handler to permanently dismiss the CLI notice
     *
     * @return void
     */
    public function ajaxCliNoticeHideForever()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        update_option(self::OPTION_CLI_NOTICE_HIDDEN_FOREVER, true);
        update_option(self::OPTION_CLI_DOCK_CTA_SHOWN, true, false);
        wp_send_json_success();
    }

    /**
     * Check if the dock CTA should be shown (banner dismissed but user has developer license)
     *
     * @return bool
     */
    public function shouldShowDockCta(): bool
    {
        if (!self::IS_ENABLED) {
            return false;
        }

        if (!$this->isWPStagingAdminPage()) {
            return false;
        }

        if (WPStaging::isBasic()) {
            return false;
        }

        if (!current_user_can('manage_options')) {
            return false;
        }

        // Only show if the dock CTA option is set (banner was dismissed)
        if (!get_option(self::OPTION_CLI_DOCK_CTA_SHOWN)) {
            return false;
        }

        // Check if user has developer or higher license
        return $this->isDeveloperOrHigherLicense();
    }

    /**
     * Check if the user has a Developer or higher license plan
     *
     * Uses Licensing::isActiveAgencyOrDeveloperPlan() when available (Pro version).
     * For Basic version, always returns false.
     *
     * @return bool
     */
    public function isDeveloperOrHigherLicense(): bool
    {
        // In basic version, Licensing class is not available
        if (WPStaging::isBasic()) {
            return false;
        }

        // Use the Licensing class method
        if (class_exists('\WPStaging\Pro\License\Licensing')) {
            $licensing = WPStaging::make(\WPStaging\Pro\License\Licensing::class);
            return $licensing->isActiveAgencyOrDeveloperPlan();
        }

        return false;
    }

    /**
     * Check if the user has an expired Developer or Agency license plan
     *
     * @return bool
     */
    public function isExpiredDeveloperOrHigherLicense(): bool
    {
        if (WPStaging::isBasic()) {
            return false;
        }

        if (class_exists('\WPStaging\Pro\License\Licensing')) {
            $licensing = WPStaging::make(\WPStaging\Pro\License\Licensing::class);
            return $licensing->isExpiredDeveloperOrAgencyPlan();
        }

        return false;
    }

    /**
     * Get the license plan name for the current license
     *
     * Uses Licensing::getAvailableLicensePlansByPriceId() when available (Pro version).
     * For Basic version or invalid licenses, returns "Unregistered".
     *
     * @return string
     */
    public function getLicensePlanName(): string
    {
        // In basic version, Licensing class is not available
        if (WPStaging::isBasic()) {
            return __('Unregistered', 'wp-staging');
        }

        // Use the Licensing class method
        if (!class_exists('\WPStaging\Pro\License\Licensing')) {
            return __('Unregistered', 'wp-staging');
        }

        $licensing = WPStaging::make(\WPStaging\Pro\License\Licensing::class);

        // Check if license is valid or expired before getting plan name
        if (!$licensing->isValidOrExpiredLicenseKey()) {
            return __('Unregistered', 'wp-staging');
        }

        $license     = get_option('wpstg_license_status', false);
        $licenseData = $license ? (object)$license : null;

        if (!$licenseData || empty($licenseData->price_id)) {
            return __('Unregistered', 'wp-staging');
        }

        $priceId   = (string)$licenseData->price_id;
        $planNames = $licensing->getAvailableLicensePlansByPriceId();

        if (isset($planNames[$priceId]['name'])) {
            return $planNames[$priceId]['name'];
        }

        return __('Unregistered', 'wp-staging');
    }

    /**
     * Render the dock CTA if conditions are met (called from staging listing view)
     *
     * @return void
     */
    public function maybeRenderDockCta()
    {
        if (!$this->shouldShowDockCta()) {
            return;
        }

        $dockCtaView = WPSTG_VIEWS_DIR . 'cli/cli-dock-cta.php';
        if (!file_exists($dockCtaView)) {
            return;
        }

        include $dockCtaView;
    }

    /**
     * Render the CLI modal content if the dock CTA should be shown
     *
     * This ensures the modal is available when the dock CTA is rendered
     * server-side (when the banner was previously dismissed).
     *
     * @return void
     */
    public function maybeRenderCliModalForDockCta()
    {
        if (!$this->shouldShowDockCta()) {
            return;
        }

        // Prepare the same variables used by cli-integration-notice.php
        $this->renderCliModalContent();
    }

    /**
     * Render the CLI modal content with all required variables
     *
     * @return void
     */
    private function renderCliModalContent()
    {
        // Check Developer plan status
        $isDeveloperOrHigher = $this->isDeveloperOrHigherLicense();

        // Get backup list for modal step 3 (sorted by newest first)
        $backups = [];
        if ($isDeveloperOrHigher && class_exists('\WPStaging\Backup\Ajax\FileList\ListableBackupsCollection')) {
            try {
                /** @var \WPStaging\Backup\Ajax\FileList\ListableBackupsCollection $listableBackupsCollection */
                $listableBackupsCollection = WPStaging::make(\WPStaging\Backup\Ajax\FileList\ListableBackupsCollection::class);
                $backups                   = $listableBackupsCollection->getSortedListableBackups();
            } catch (\Exception $e) {
                $backups = [];
            }
        }

        $urlAssets = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';

        $modalView = WPSTG_VIEWS_DIR . 'cli/cli-integration-modal.php';
        if (file_exists($modalView)) {
            include $modalView;
        }
    }

    /**
     * AJAX handler to get updated CLI modal backup list HTML
     *
     * @return void
     */
    public function ajaxGetCliBackupList()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        $backups   = [];
        $urlAssets = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';

        if (class_exists('\WPStaging\Backup\Ajax\FileList\ListableBackupsCollection')) {
            try {
                /** @var \WPStaging\Backup\Ajax\FileList\ListableBackupsCollection $listableBackupsCollection */
                $listableBackupsCollection = \WPStaging\Core\WPStaging::make(\WPStaging\Backup\Ajax\FileList\ListableBackupsCollection::class);
                $backups                   = $listableBackupsCollection->getSortedListableBackups();
            } catch (\Exception $e) {
                $backups = [];
            }
        }

        // Check if there are valid (non-corrupt, non-legacy) backups
        $hasBackups = false;
        foreach ($backups as $backup) {
            if (!$backup->isCorrupt && !$backup->isLegacy) {
                $hasBackups = true;
                break;
            }
        }

        ob_start();
        include WPSTG_VIEWS_DIR . 'cli/cli-backup-list.php';
        $html = ob_get_clean();

        wp_send_json_success([
            'html'       => $html,
            'hasBackups' => $hasBackups,
        ]);
    }
}
