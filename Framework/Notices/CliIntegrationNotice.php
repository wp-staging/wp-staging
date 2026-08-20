<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Traits\NoticesTrait;









class CliIntegrationNotice
{
    use NoticesTrait;

    const IS_ENABLED = true;




    const OPTION_CLI_NOTICE_HIDDEN_FOREVER = 'wpstg_cli_notice_hidden_forever';




    const OPTION_CLI_DOCK_CTA_SHOWN = 'wpstg_cli_dock_cta_shown';




    const OPTION_CLI_NOTICE_DISMISSED_UNTIL = 'wpstg_cli_notice_dismissed_until';




    private $auth;




    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }






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

        if ($this->isTemporarilyDismissed()) {
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







    private function isTemporarilyDismissed(): bool
    {
        $dismissedUntil = (int)get_option(self::OPTION_CLI_NOTICE_DISMISSED_UNTIL, 0);
        if ($dismissedUntil === 0) {
            return false;
        }

        if (time() < $dismissedUntil) {
            return true;
        }

        delete_option(self::OPTION_CLI_NOTICE_DISMISSED_UNTIL);
        return false;
    }








    public function ajaxCliNoticeClose()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        update_option(self::OPTION_CLI_NOTICE_DISMISSED_UNTIL, time() + DAY_IN_SECONDS, false);
        update_option(self::OPTION_CLI_DOCK_CTA_SHOWN, true, false);
        wp_send_json_success();
    }






    public function ajaxCliNoticeHideForever()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        update_option(self::OPTION_CLI_NOTICE_HIDDEN_FOREVER, true);
        update_option(self::OPTION_CLI_DOCK_CTA_SHOWN, true, false);
        wp_send_json_success();
    }








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

 
        if (!$this->isBannerDismissed()) {
            return false;
        }

        return true;
    }






    private function isBannerDismissed(): bool
    {
        return (bool)get_option(self::OPTION_CLI_NOTICE_HIDDEN_FOREVER) || $this->isTemporarilyDismissed();
    }







    public function isDeveloperOrHigherLicense(): bool
    {
        return $this->checkLicensingCondition('isActiveAgencyOrDeveloperPlan');
    }






    public function isExpiredDeveloperOrHigherLicense(): bool
    {
        return $this->checkLicensingCondition('isExpiredDeveloperOrAgencyPlan');
    }





    private function hasActiveLicense(): bool
    {
        return $this->checkLicensingCondition('isValidOrExpiredLicenseKey');
    }







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

        $planName = $licensing->getPlanDisplayName();

        return $planName !== '' ? $planName : __('Unregistered', 'wp-staging');
    }






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









    public function maybeRenderCliModalForDockCta()
    {
        if (!$this->shouldShowDockCta()) {
            return;
        }

        $this->renderCliModalContent();
    }






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






    private function getLicenseTypeSlug(): string
    {
        if (!WPStaging::isPro() || !class_exists('\WPStaging\Pro\License\Licensing')) {
            return 'free';
        }

        $licensing = WPStaging::make(\WPStaging\Pro\License\Licensing::class);
        $type      = $licensing->getLicenseType();

        return $type === 'basic' ? 'free' : $type;
    }







    private function getLicenseId(): string
    {
        $licenseData = $this->getLicenseData();
        if (!$licenseData) {
            return '';
        }

        return !empty($licenseData->license_id) ? (string)$licenseData->license_id : '';
    }




    private function getLicenseData()
    {
        if (!WPStaging::isPro()) {
            return null;
        }

        $license = get_option('wpstg_license_status', false);
        return $license ? (object)$license : null;
    }







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







    private function fetchSortedBackups(bool $isDeveloperOrHigher = true): array
    {
        if (!$isDeveloperOrHigher || !class_exists('\WPStaging\Backup\Ajax\FileList\ListableBackupsCollection')) {
            return [];
        }

        try {
 
            $listableBackupsCollection = WPStaging::make(\WPStaging\Backup\Ajax\FileList\ListableBackupsCollection::class);
            return $listableBackupsCollection->getSortedListableBackups();
        } catch (\Exception $e) {
            return [];
        }
    }






    public function ajaxGetCliBackupList()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        $isDeveloperOrHigher = $this->isDeveloperOrHigherLicense();
        $backups             = $this->fetchSortedBackups($isDeveloperOrHigher);
        $urlAssets           = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';

 
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
