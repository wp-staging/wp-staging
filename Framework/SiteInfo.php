<?php

namespace WPStaging\Framework;

use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Staging\CloneOptions;

/**
 * Class SiteInfo
 *
 * Provides information about the current site.
 *
 * @package WPStaging\Site
 */
class SiteInfo
{
    /**
     * The key used in DB to store is cloneable feature in clone options
     * @var string
     */
    const IS_CLONEABLE_KEY = 'isCloneable';

    /**
     * The file which make staging site cloneable
     * This way is depreciated
     * @var string
     */
    const CLONEABLE_FILE = '.wp-staging-cloneable';

    /**
     * The key used in DB to store whether site is staging or not
     * @var string
     */
    const IS_STAGING_KEY = 'wpstg_is_staging_site';

    /**
     * The file which makes a site a staging site
     * @var string
     */
    const STAGING_FILE = '.wp-staging';

    /** @var string */
    const HOSTED_ON_WP = 'wp.com';

    /** @var string */
    const HOSTED_ON_FLYWHEEL = 'flywheel';

    /** @var string */
    const HOSTED_ON_BITNAMI = 'bitnami';

    /** @var string */
    const OTHER_HOST = 'other';

    /** @var string */
    const FILTER_IS_WPSTG_TESTS = 'wpstg.tests.is_local_site';

    /** @var string[] */
    const LOCAL_HOSTNAMES = [
        '.local',
        '.test',
        'localhost',
        '.dev',
        '10.0.0.',
        '172.16.0.',
        '192.168.0.'
    ];

    /**
     * @var CloneOptions
     */
    private $cloneOptions;

    /**
     * @var array
     */
    private $errors = [];

    public function __construct()
    {
        // TODO: inject using DI
        $this->cloneOptions = new CloneOptions();
    }

    /**
     * @return bool True if it is staging site. False otherwise.
     */
    public function isStagingSite(): bool
    {
        if (defined('WPSTAGING_DEV_SITE') && WPSTAGING_DEV_SITE === true) {
            return true;
        }

        if (get_option(self::IS_STAGING_KEY) === "true") {
            return true;
        }

        return file_exists(ABSPATH . self::STAGING_FILE);
    }

    /**
     * @return bool True if it is staging site. False otherwise.
     */
    public function isCloneable(): bool
    {
        // Site should be cloneable if not staging i.e. production site
        if (!$this->isStagingSite()) {
            return true;
        }

        // Old condition to check if staging site is cloneable
        if (file_exists(ABSPATH . self::CLONEABLE_FILE)) {
            return true;
        }

        // New condition for checking whether staging is cloneable or not
        return $this->cloneOptions->get(self::IS_CLONEABLE_KEY, false);
    }

    /**
     * Check if WP is installed in subdirectory
     * If siteurl and home are not identical we assume the site is located in a subdirectory
     * related to that instruction https://wordpress.org/support/article/giving-wordpress-its-own-directory/
     *
     * @return bool
     */
    public function isInstalledInSubDir(): bool
    {
        $siteUrl = get_option('siteurl');
        $homeUrl = get_option('home');

        //Get URL path e.g.https://example.com/path will return /path
        $siteUrlPath = wp_parse_url($siteUrl, PHP_URL_PATH);
        $homeUrlPath = wp_parse_url($homeUrl, PHP_URL_PATH);

        if ($siteUrlPath === null && $homeUrlPath === null || $siteUrlPath === $homeUrlPath) {
            return false;
        }

        if ($siteUrlPath === null && $homeUrlPath !== null) {
            return true;
        }

        return false;
    }

    /**
     * Enable the cloning for current staging site.
     *
     * @return bool
     */
    public function enableStagingSiteCloning(): bool
    {
        // Early Bail: if site is not staging
        if (!$this->isStagingSite()) {
            return false;
        }

        // Early Bail: if cloning already enabled
        if ($this->isCloneable()) {
            return true;
        }

        return $this->cloneOptions->set(self::IS_CLONEABLE_KEY, true);
    }

    /**
     * Enable the cloning for current staging site.
     *
     * @return bool
     */
    public function disableStagingSiteCloning(): bool
    {
        // Early Bail: if site is not staging
        if (!$this->isStagingSite()) {
            return false;
        }

        // Early Bail: if cloning already disabled
        if (!$this->isCloneable()) {
            return true;
        }

        // First try disabling if cloneable feature exist due to old way.
        $cloneableFile = trailingslashit(ABSPATH) . self::CLONEABLE_FILE;
        if (file_exists($cloneableFile) && !unlink($cloneableFile)) {
            // Error if files exists but unable to unlink
            return false;
        }

        // Staging site may have been made cloneable through both ways
        // So now try disabling through new way
        return (!file_exists($cloneableFile) && $this->cloneOptions->delete(self::IS_CLONEABLE_KEY));
    }

    /**
     * @return bool True if "short_open_tags" is enabled, false if disabled.
     */
    public function isPhpShortTagsEnabled(): bool
    {
        return in_array(strtolower(ini_get('short_open_tags')), ['1', 'on', 'true']);
    }

    /**
     * Is WP Bakery plugin active?
     *
     * @return bool
     */
    public function isWpBakeryActive(): bool
    {
        return defined('WPB_VC_VERSION');
    }

    /**
     * Is Jetpack plugin active?
     *
     * @return bool
     */
    public function isJetpackActive(): bool
    {
        return class_exists('Jetpack');
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function isBitnami(): bool
    {
        return ABSPATH === '/opt/bitnami/wordpress/';
    }

    /**
     * @return bool
     */
    public function isWpContentOutsideAbspath(): bool
    {
        $wpContentDir = wp_normalize_path(WP_CONTENT_DIR);
        $abspath      = wp_normalize_path(ABSPATH);

        return !(strpos($wpContentDir, $abspath) === 0);
    }

    /**
     * @return bool
     */
    public function isFlywheel(): bool
    {
        if (!$this->isWpContentOutsideAbspath()) {
            return false;
        }

        return file_exists(trailingslashit(wp_normalize_path(ABSPATH)) . '.fw-config.php');
    }

    /**
     * @return bool
     */
    public function isHostedOnWordPressCom(): bool
    {
        if (!$this->isWpContentOutsideAbspath()) {
            return false;
        }

        $parentDirectory = dirname(trailingslashit(wp_normalize_path(WP_CONTENT_DIR)));
        $wpcomDetection  = trailingslashit($parentDirectory) . '__wp__';
        if (!is_link($wpcomDetection)) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getHostingType(): string
    {
        if ($this->isFlywheel()) {
            return self::HOSTED_ON_FLYWHEEL;
        }

        if ($this->isHostedOnWordPressCom()) {
            return self::HOSTED_ON_WP;
        }

        if ($this->isBitnami()) {
            return self::HOSTED_ON_BITNAMI;
        }

        return self::OTHER_HOST;
    }

    public function getPhpArchitecture(): string
    {
        return PHP_INT_SIZE === 8 ? '64-bit' : '32-bit';
    }

    public function getOsArchitecture(): string
    {
        try {
            if (!function_exists('php_uname')) {
                return 'N/A';
            }

            if (in_array('php_uname', explode(',', ini_get('disable_functions')))) {
                return 'N/A';
            }

            return strpos(php_uname('m'), '64') !== false ? '64-bit' : '32-bit';
        } catch (\Throwable $ex) {
            return 'N/A';
        }
    }

    /**
     * @return bool
     */
    public function isHostedOnElementorCloud(): bool
    {
        $httpHost = !empty($_SERVER['HTTP_HOST']) ? Sanitize::sanitizeString($_SERVER['HTTP_HOST']) : '';
        if (strpos($httpHost, 'elementor.cloud') !== false) {
            return true;
        }

        $headers = headers_list();
        foreach ($headers as $header) {
            if (stripos($header, 'ec-source') !== false || stripos($header, 'ec-coldstart') !== false || stripos($header, 'EC-LB-OP-STATUS') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the website is installed locally.
     * @return bool
     */
    public function isLocal(): bool
    {
        $siteUrl = get_site_url();
        $isLocal = false;

        foreach (self::LOCAL_HOSTNAMES as $hostname) {
            if (strpos($siteUrl, $hostname) !== false) {
                $isLocal = true;
                break;
            }
        }

        return Hooks::applyFilters(self::FILTER_IS_WPSTG_TESTS, $isLocal);
    }
}
