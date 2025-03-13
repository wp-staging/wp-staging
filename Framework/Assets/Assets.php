<?php

namespace WPStaging\Framework\Assets;

use WPStaging\Backup\BackupServiceProvider;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Core\DTO\Settings;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\Security\Nonce;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Analytics\AnalyticsConsent;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Notices\Notices;

class Assets
{
    use ResourceTrait;

    /**
     * Default admin bar background color for staging site
     * @var string
     */
    const DEFAULT_ADMIN_BAR_BG = "#ff8d00";

    /** @var string */
    const FILTER_BACKUP_STATUS_REQUEST_INTERVAL = 'wpstg.backup.interval.status_request';

    private $accessToken;

    private $settings;

    private $analyticsConsent;

    /** @var I18n */
    private $i18n;

    public function __construct(AccessToken $accessToken, Settings $settings, AnalyticsConsent $analyticsConsent, I18n $i18n)
    {
        $this->accessToken      = $accessToken;
        $this->settings         = $settings;
        $this->analyticsConsent = $analyticsConsent;
        $this->i18n             = $i18n;
    }

    /**
     * Prepend the URL to the assets to the given file
     *
     * @param string $assetsFile optional
     * @return string
     */
    public function getAssetsUrl($assetsFile = '')
    {
        return WPSTG_PLUGIN_URL . "assets/$assetsFile";
    }

    /**
     * Get minified or non minified css file name based on debug mode
     * @param string $cssFileNameWithoutExtension path without extension relative to wpstgPluginDir/assets/css/dist/
     * @return string
     */
    public function getCssAssetsFileName(string $cssFileNameWithoutExtension): string
    {
        // If in debug mode, get non-minified css file name if it exists
        $nonMinCssFile = $this->getAssetsPath("css/dist/$cssFileNameWithoutExtension.css");
        if ($this->isDebugOrDevMode() && file_exists($nonMinCssFile)) {
            return "css/dist/$cssFileNameWithoutExtension.css";
        }

        return "css/dist/$cssFileNameWithoutExtension.min.css";
    }

    /**
     * Get minified or non minified js file name based on debug mode
     * @param string $jsFileNameWithoutExtension path without extension relative to wpstgPluginDir/assets/js/dist/
     * @return string
     */
    public function getJsAssetsFileName(string $jsFileNameWithoutExtension): string
    {
        // If in debug mode, get non-minified js file name if it exists
        $nonMinJsFile = $this->getAssetsPath("js/dist/$jsFileNameWithoutExtension.js");
        if ($this->isDebugOrDevMode() && file_exists($nonMinJsFile)) {
            return "js/dist/$jsFileNameWithoutExtension.js";
        }

        return "js/dist/$jsFileNameWithoutExtension.min.js";
    }

    /**
     * Get the version the given file. Use for caching
     *
     * @param string $assetsFile
     * @param string $assetsVersion use WPStaging::getVersion() instead if not given
     * @return string
     */
    public function getAssetsUrlWithVersion($assetsFile, $assetsVersion = '')
    {
        $url = $this->getAssetsUrl($assetsFile);
        $ver = empty($assetsVersion) ? $this->getAssetsVersion($assetsFile, $assetsVersion) : $assetsVersion;
        return $url . '?v=' . $ver;
    }

    /**
     * Prepend the Path to the assets to the given file
     *
     * @param string $assetsFile optional
     * @return string
     */
    public function getAssetsPath($assetsFile = '')
    {
        return WPSTG_PLUGIN_DIR . "assets/$assetsFile";
    }

    /**
     * Get the version the given file. Use for caching
     *
     * @param string $assetsFile
     * @param string $assetsVersion Optional, use WPStaging::getVersion() instead if not given
     * @return string|int
     */
    public function getAssetsVersion($assetsFile, $assetsVersion = '')
    {
        $filename  = $this->getAssetsPath($assetsFile);
        $filemtime = file_exists($filename) ? @filemtime($filename) : false;

        if ($filemtime !== false) {
            return $filemtime;
        } else {
            return $assetsVersion !== '' ? $assetsVersion : WPStaging::getVersion();
        }
    }

    /**
     * @action admin_enqueue_scripts 100 1
     * @action wp_enqueue_scripts 100 1
     */
    public function enqueueElements($hook)
    {
        $this->loadGlobalAssets($hook);

        add_action(Notices::INJECT_ANALYTICS_CONSENT_ASSETS_ACTION, [$this, 'enqueueAnalyticsConsentAssets'], 10, 0);

        // Load this css file on frontend and backend on all pages if current site is a staging site
        if ((new SiteInfo())->isStagingSite()) {
            wp_register_style('wpstg-admin-bar', false);
            wp_enqueue_style('wpstg-admin-bar');
            wp_add_inline_style('wpstg-admin-bar', $this->getStagingAdminBarColor());
        }

        // Load feedback form js file on page plugins.php in free version or in free dev version
        if (!WPStaging::isPro() && $this->isPluginsPage()) {
            $asset = $this->getJsAssetsFileName('wpstg-admin-plugins');
            wp_enqueue_script(
                "wpstg-admin-script",
                $this->getAssetsUrl($asset),
                ["jquery"],
                $this->getAssetsVersion($asset),
                false
            );

            $asset = $this->getCssAssetsFileName('wpstg-admin-feedback');
            wp_enqueue_style(
                "wpstg-admin-feedback",
                $this->getAssetsUrl($asset),
                [],
                $this->getAssetsVersion($asset)
            );
        }

        // Load js file on admin pages for pro version
        if (WPStaging::isPro() && is_admin()) {
            $asset = $this->getJsAssetsFileName('pro/wpstg-admin-all-pages');
            wp_enqueue_script(
                "wpstg-admin-all-pages-script",
                $this->getAssetsUrl($asset),
                ["jquery"],
                $this->getAssetsVersion($asset),
                false
            );

            $asset = $this->getCssAssetsFileName('wpstg-admin-all-pages');
            wp_enqueue_style(
                "wpstg-admin-all-pages-style",
                $this->getAssetsUrl($asset),
                [],
                $this->getAssetsVersion($asset)
            );
        }

        // Load below assets only on WP Staging admin pages
        if ($this->isNotWPStagingAdminPage($hook)) {
            return;
        }

        // Load wpstg js files
        $asset = $this->getJsAssetsFileName('wpstg');
        wp_enqueue_script(
            "wpstg-common",
            $this->getAssetsUrl($asset),
            ["jquery"],
            $this->getAssetsVersion($asset),
            false
        );

        // Load admin js files
        $asset = $this->getJsAssetsFileName('wpstg-admin');
        wp_enqueue_script(
            "wpstg-admin-script",
            $this->getAssetsUrl($asset),
            ["wpstg-common", "wpstg-admin-notyf", "wpstg-admin-sweetalerts"],
            $this->getAssetsVersion($asset),
            false
        );

        // Sweet Alert
        $asset = $this->getJsAssetsFileName('wpstg-sweetalert2');
        wp_enqueue_script(
            'wpstg-admin-sweetalerts',
            $this->getAssetsUrl($asset),
            [],
            $this->getAssetsVersion($asset),
            true
        );

        $asset = $this->getCssAssetsFileName('wpstg-sweetalert2');
        wp_enqueue_style(
            'wpstg-admin-sweetalerts',
            $this->getAssetsUrl($asset),
            [],
            $this->getAssetsVersion($asset)
        );

        // Notyf Toast Notification
        $asset = 'js/vendor/notyf.min.js';
        wp_enqueue_script(
            'wpstg-admin-notyf',
            $this->getAssetsUrl($asset),
            [],
            $this->getAssetsVersion($asset),
            true
        );

        $asset = 'css/vendor/notyf.min.css';
        wp_enqueue_style(
            'wpstg-admin-notyf',
            $this->getAssetsUrl($asset),
            [],
            $this->getAssetsVersion($asset)
        );

        // Internal hook to enqueue backup scripts, used by the backup addon
        Hooks::doAction(BackupServiceProvider::BACKUP_SCRIPTS_ENQUEUE_ACTION);

        // Load admin js pro files
        if (WPStaging::isPro()) {
            $asset = $this->getJsAssetsFileName('pro/wpstg-admin-pro');
            wp_enqueue_script(
                "wpstg-admin-pro-script",
                $this->getAssetsUrl($asset),
                ["jquery", "wpstg-admin-notyf", "wpstg-admin-sweetalerts"],
                $this->getAssetsVersion($asset),
                false
            );
        }

        // Load admin css files
        $asset = $this->getCssAssetsFileName('wpstg-admin');
        wp_enqueue_style(
            "wpstg-admin",
            $this->getAssetsUrl($asset),
            [],
            $this->getAssetsVersion($asset)
        );

        $wpstgConfig = [
            "delayReq"               => 0,
            // The interval in milliseconds between each request of backup status
            'backupStatusInterval'   => Hooks::applyFilters(self::FILTER_BACKUP_STATUS_REQUEST_INTERVAL, 8000),
            "settings"               => (object)[
                "directorySeparator" => ScanConst::DIRECTORIES_SEPARATOR
            ],
            "tblprefix"              => WPStaging::getTablePrefix(),
            "isMultisite"            => is_multisite(),
            AccessToken::REQUEST_KEY => (string)$this->accessToken->getToken() ?: (string)$this->accessToken->generateNewToken(),
            'nonce'                  => wp_create_nonce(Nonce::WPSTG_NONCE),
            'assetsUrl'              => $this->getAssetsUrl(),
            'ajaxUrl'                => admin_url('admin-ajax.php'),
            'wpstgIcon'              => $this->getAssetsUrl('img/wpstg-loader.gif'),
            'maxUploadChunkSize'     => $this->getMaxUploadChunkSize(),
            'backupDBExtension'      => PartIdentifier::DATABASE_PART_IDENTIFIER . '.' . DatabaseImporter::FILE_FORMAT,
            'analyticsConsentAllow'  => esc_url($this->analyticsConsent->getConsentLink(true)),
            'analyticsConsentDeny'   => esc_url($this->analyticsConsent->getConsentLink(false)),
            'isPro'                  => WPStaging::isPro(),
            'maxFailedRetries'       => Hooks::applyFilters(AbstractJob::TEST_FILTER_MAXIMUM_RETRIES, 10),
            'i18n'                   => $this->i18n->getTranslations(),
        ];

        // We need some wpstgConfig vars in the wpstg.js file (loaded with wpstg-common scripts) as well
        wp_localize_script("wpstg-common", "wpstg", $wpstgConfig);
    }

    public function enqueueAnalyticsConsentAssets()
    {
        $asset = $this->getJsAssetsFileName('analytics-consent-modal');
        wp_enqueue_script(
            "wpstg-show-analytics-modal",
            $this->getAssetsUrl($asset),
            [],
            $this->getAssetsVersion($asset)
        );

        $asset = $this->getCssAssetsFileName('analytics-consent-modal');
        wp_enqueue_style(
            'wpstg-plugin-activation',
            $this->getAssetsUrl($asset),
            [],
            $this->getAssetsVersion($asset)
        );
    }

    /**
     * Load js vars globally but NOT on WP Staging admin pages or frontend
     *
     * @param string $pageSlug
     * @return void
     */
    private function loadGlobalAssets($pageSlug)
    {
        if (!$this->isNotWPStagingAdminPage($pageSlug) || !is_admin()) {
            return;
        }

        $asset = $this->getJsAssetsFileName('wpstg-blank-loader');
        wp_enqueue_script('wpstg-global', $this->getAssetsUrl($asset), [], [], false);

        $vars = [
            'nonce' => wp_create_nonce(Nonce::WPSTG_NONCE),
        ];

        wp_localize_script("wpstg-global", "wpstg", $vars);
    }

    /**
     * @return int The max upload size for a file.
     */
    protected function getMaxUploadChunkSize()
    {
        $lowerLimit = 64 * KB_IN_BYTES;
        $upperLimit = 16 * MB_IN_BYTES;

        $maxPostSize       = wp_convert_hr_to_bytes(ini_get('post_max_size'));
        $uploadMaxFileSize = wp_convert_hr_to_bytes(ini_get('upload_max_filesize'));

        // The real limit, read from the PHP context.
        $limit = min($maxPostSize, $uploadMaxFileSize) * 0.90;

        // Do not allow going over upper limit.
        $limit = min($limit, $upperLimit);

        // Do not allow going under lower limit.
        $limit = max($lowerLimit, $limit);

        return (int)$limit;
    }

    /**
     * Check given slug is not WP Staging admin page
     *
     * @param string $slug slug of the current page
     *
     * @return bool
     */
    private function isNotWPStagingAdminPage($slug)
    {
        if (WPStaging::isPro() || WPStaging::isDevBasic()) {
            $availableSlugs = [
                "toplevel_page_wpstg_clone",
                "toplevel_page_wpstg_backup",
                "wp-staging-pro_page_wpstg_clone",
                "wp-staging-pro_page_wpstg_backup",
                "wp-staging-pro_page_wpstg-settings",
                "wp-staging-pro_page_wpstg-tools",
                "wp-staging-pro_page_wpstg-license",
                "wp-staging-pro_page_wpstg-restorer",
            ];
        } else {
            $availableSlugs = [
                "toplevel_page_wpstg_clone",
                "toplevel_page_wpstg_backup",
                "wp-staging_page_wpstg_clone",
                "wp-staging_page_wpstg_backup",
                "wp-staging_page_wpstg-settings",
                "wp-staging_page_wpstg-tools",
                "wp-staging_page_wpstg-welcome",
            ];
        }

        return !in_array($slug, $availableSlugs);
    }

    /**
     * Remove heartbeat api and user login check
     *
     * @action admin_enqueue_scripts 100 1
     * @see AssetServiceProvider.php
     *
     * @param bool $hook
     */
    public function removeWPCoreJs($hook)
    {
        if ($this->isNotWPStagingAdminPage($hook)) {
            return;
        }

        // Disable user login status check
        // Todo: Can we remove this now that we have AccessToken?
        remove_action('admin_enqueue_scripts', 'wp_auth_check_load');

        // Disable heartbeat check for cloning and pushing
        wp_deregister_script('heartbeat');
    }

    /**
     * Check if current page is plugins.php
     * @global array $pagenow
     * @return bool
     */
    private function isPluginsPage()
    {
        global $pagenow;

        return ($pagenow === 'plugins.php');
    }

    /**
     * @return string
     */
    public function getStagingAdminBarColor()
    {
        $barColor = $this->settings->getAdminBarColor();
        if (!preg_match("/#([a-f0-9]{3}){1,2}\b/i", $barColor)) {
            $barColor = self::DEFAULT_ADMIN_BAR_BG;
        }

        return "#wpadminbar { background-color: {$barColor} !important; }";
    }

    /**
     * Check whether app is in debug mode or in dev mode
     *
     * @return bool
     */
    private function isDebugOrDevMode()
    {
        return ($this->settings->isDebugMode() || (defined('WPSTG_IS_DEV') && WPSTG_IS_DEV === true) || (defined('WPSTG_DEBUG') && WPSTG_DEBUG === true));
    }

    /**
     * Change admin_bar site_name
     *
     * @return void
     * @global object $wp_admin_bar
     */
    public function changeSiteName()
    {
        if (!(new SiteInfo())->isStagingSite()) {
            return;
        }

        global $wp_admin_bar;
        $blogName  = get_bloginfo('name');
        if (empty($blogName)) {
            $siteUrl   = get_site_url();
            $parsedUrl = parse_url($siteUrl);
            $blogName  = $parsedUrl['host'];
        }

        $siteTitle = apply_filters('wpstg_staging_site_title', 'STAGING');
        $title     = (strlen($blogName) > 20) ? substr($blogName, 0, 20) . '...' : $blogName;
        $wp_admin_bar->add_menu(
            [
                'id'    => 'site-name',
                'title' => $siteTitle . ' - ' . $title,
                'href'  => is_admin() ? home_url('/') : admin_url(),
            ]
        );
    }

    /**
     * @param string $hook
     * @return void
     */
    public function dequeueNonWpstgElements($hook)
    {
        if ($this->isNotWPStagingAdminPage($hook)) {
            return;
        }

        $stylesToRemove  = ['wp-reset-sweetalert2'];
        $scriptsToRemove = [
            'wp-reset-sweetalert2',
            'wp-reset'
        ];

        foreach ($stylesToRemove as $style) {
            wp_dequeue_style($style);
        }

        foreach ($scriptsToRemove as $script) {
            wp_dequeue_script($script);
        }
    }
}
