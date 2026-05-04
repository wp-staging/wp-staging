<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Traits\NoticesTrait;

/**
 * Displays a dismissible banner promoting the WP Staging CLI tool
 *
 * The banner appears on both Staging and Backup tabs for both free and Pro version users.
 * "Later" dismissal uses client-side localStorage (24 hours). Permanent dismissal uses a wp_option.
 */
class CliIntegrationNotice
{
    use NoticesTrait;

    const IS_ENABLED = true;

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
        if (!self::IS_ENABLED) {
            return;
        }

        if (!$this->isWPStagingAdminPage()) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (get_option(self::OPTION_CLI_NOTICE_HIDDEN_FOREVER)) {
            return;
        }

        $notice = WPSTG_VIEWS_DIR . 'notices/cli-integration-notice.php';

        if (!file_exists($notice)) {
            return;
        }

        $isDeveloperOrHigher = $this->isDeveloperOrHigherLicense();
        $hasActiveLicense    = $this->hasActiveLicense();
        $planName            = $this->getLicensePlanName();
        $backups             = $this->fetchSortedBackups($isDeveloperOrHigher);
        $urlAssets           = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';
        $licenseType         = $this->getLicenseTypeSlug();
        $licenseId           = $this->getLicenseId();

        include $notice;
    }

    /**
     * AJAX handler to dismiss the CLI notice temporarily.
     * The 24-hour hiding is handled client-side via localStorage.
     * This endpoint persists the dock CTA flag.
     *
     * @return void
     */
    public function ajaxCliNoticeClose()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

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
     * Check if the dock CTA should be shown (banner was dismissed).
     * Shows for all users after banner dismissal. Non-developer users see a "Pro" badge
     * and an upgrade notice inside the modal.
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

        if (!current_user_can('manage_options')) {
            return false;
        }

        if (!get_option(self::OPTION_CLI_DOCK_CTA_SHOWN)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the user has a Developer or higher license plan.
     * For Basic version, always returns false.
     *
     * @return bool
     */
    public function isDeveloperOrHigherLicense(): bool
    {
        return $this->checkLicensingCondition('isActiveAgencyOrDeveloperPlan');
    }

    /**
     * Check if the user has an expired Developer or Agency license plan
     *
     * @return bool
     */
    public function isExpiredDeveloperOrHigherLicense(): bool
    {
        return $this->checkLicensingCondition('isExpiredDeveloperOrAgencyPlan');
    }

    /**
     * Whether the user has a valid, active pro license (not free, not expired, not unregistered).
     * Used to decide if the upgrade button should link to the internal license page or external checkout.
     */
    private function hasActiveLicense(): bool
    {
        return $this->checkLicensingCondition('isValidOrExpiredLicenseKey');
    }

    /**
     * Get the license plan name for the current license.
     * For Basic version or invalid licenses, returns "Unregistered".
     *
     * @return string
     */
    public function getLicensePlanName(): string
    {
        if (WPStaging::isBasic()) {
            return __('Free', 'wp-staging');
        }

        if (!class_exists('\WPStaging\Pro\License\Licensing')) {
            return __('Unregistered', 'wp-staging');
        }

        $licensing = WPStaging::make(\WPStaging\Pro\License\Licensing::class);

        if (!$licensing->isValidOrExpiredLicenseKey()) {
            return __('Unregistered', 'wp-staging');
        }

        $licenseData = $this->getLicenseData();

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

        $this->renderCliModalContent();
    }

    /**
     * Render the CLI modal content with all required variables
     *
     * @return void
     */
    private function renderCliModalContent()
    {
        if (!empty($GLOBALS['wpstg_cli_modal_rendered'])) {
            return;
        }

        $isDeveloperOrHigher = $this->isDeveloperOrHigherLicense();
        $backups             = $this->fetchSortedBackups($isDeveloperOrHigher);
        $urlAssets           = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';
        $licenseType         = $this->getLicenseTypeSlug();
        $licenseId           = $this->getLicenseId();

        $modalView = WPSTG_VIEWS_DIR . 'cli/cli-integration-modal.php';
        if (file_exists($modalView)) {
            include $modalView;
            $GLOBALS['wpstg_cli_modal_rendered'] = true;
        }
    }

    /**
     * Get the license type slug (e.g. 'free', 'personal', 'business', 'developer', 'agency')
     *
     * @return string
     */
    private function getLicenseTypeSlug(): string
    {
        if (!WPStaging::isPro() || !class_exists('\WPStaging\Pro\License\Licensing')) {
            return 'free';
        }

        $licensing = WPStaging::make(\WPStaging\Pro\License\Licensing::class);
        $type      = $licensing->getLicenseType();

        return $type === 'basic' ? 'free' : $type;
    }

    /**
     * Get the license ID from stored license status
     * Returns empty string when unavailable.
     *
     * @return string
     */
    private function getLicenseId(): string
    {
        $licenseData = $this->getLicenseData();
        if (!$licenseData) {
            return '';
        }

        return !empty($licenseData->license_id) ? (string)$licenseData->license_id : '';
    }

    /**
     * @return object|null
     */
    private function getLicenseData()
    {
        if (!WPStaging::isPro()) {
            return null;
        }

        $license = get_option('wpstg_license_status', false);
        return $license ? (object)$license : null;
    }

    /**
     * Check a condition on the Licensing class, returning false for Basic version
     *
     * @param string $method The Licensing method name to call
     * @return bool
     */
    private function checkLicensingCondition(string $method): bool
    {
        if (WPStaging::isBasic()) {
            return false;
        }

        if (!class_exists('\WPStaging\Pro\License\Licensing')) {
            return false;
        }

        $licensing = WPStaging::make(\WPStaging\Pro\License\Licensing::class);
        return $licensing->$method();
    }

    /**
     * Fetch sorted listable backups, returning an empty array on failure
     *
     * @param bool $isDeveloperOrHigher Whether the user has a Developer+ license
     * @return array
     */
    private function fetchSortedBackups(bool $isDeveloperOrHigher = true): array
    {
        if (!$isDeveloperOrHigher || !class_exists('\WPStaging\Backup\Ajax\FileList\ListableBackupsCollection')) {
            return [];
        }

        try {
            /** @var \WPStaging\Backup\Ajax\FileList\ListableBackupsCollection $listableBackupsCollection */
            $listableBackupsCollection = WPStaging::make(\WPStaging\Backup\Ajax\FileList\ListableBackupsCollection::class);
            return $listableBackupsCollection->getSortedListableBackups();
        } catch (\Exception $e) {
            return [];
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

        $isDeveloperOrHigher = $this->isDeveloperOrHigherLicense();
        $backups             = $this->fetchSortedBackups($isDeveloperOrHigher);
        $urlAssets           = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';

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
