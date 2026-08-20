<?php

namespace WPStaging\Framework;

use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Staging\CloneOptions;








class SiteInfo
{




    const IS_CLONEABLE_KEY = 'isCloneable';






    const CLONEABLE_FILE = '.wp-staging-cloneable';





    const IS_STAGING_KEY = 'wpstg_is_staging_site';





    const STAGING_FILE = '.wp-staging';

 
    const HOSTED_ON_WP = 'wp.com';

 
    const HOSTED_ON_FLYWHEEL = 'flywheel';

 
    const HOSTED_ON_BITNAMI = 'bitnami';

 
    const OTHER_HOST = 'other';







    const LOCAL_HOSTNAME_SUFFIXES = [
        '.local',
        '.test',
        '.localhost',
        '.dev',
    ];

 
    const LOCAL_HOSTNAMES = [
        'localhost',
    ];

 
    const LOCAL_IP_PREFIXES = [
        '10.0.0.',
        '172.16.0.',
        '192.168.0.',
    ];




    private $cloneOptions;




    private $errors = [];

    public function __construct()
    {
 
        $this->cloneOptions = new CloneOptions();
    }




    public function isStagingSite(): bool
    {
 
        return wpstgIsStagingSite(self::STAGING_FILE, self::IS_STAGING_KEY);
    }




    public function isCloneable(): bool
    {
 
        if (!$this->isStagingSite()) {
            return true;
        }

 
        if (file_exists(ABSPATH . self::CLONEABLE_FILE)) {
            return true;
        }

 
        return $this->cloneOptions->get(self::IS_CLONEABLE_KEY, false);
    }








    public function isInstalledInSubDir(): bool
    {
        $siteUrl = get_option('siteurl');
        $homeUrl = get_option('home');

 
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






    public function enableStagingSiteCloning(): bool
    {
 
        if (!$this->isStagingSite()) {
            return false;
        }

 
        if ($this->isCloneable()) {
            return true;
        }

        return $this->cloneOptions->set(self::IS_CLONEABLE_KEY, true);
    }






    public function disableStagingSiteCloning(): bool
    {
 
        if (!$this->isStagingSite()) {
            return false;
        }

 
        if (!$this->isCloneable()) {
            return true;
        }

 
        $cloneableFile = trailingslashit(ABSPATH) . self::CLONEABLE_FILE;
        if (file_exists($cloneableFile) && !unlink($cloneableFile)) {
 
            return false;
        }

 
 
        return (!file_exists($cloneableFile) && $this->cloneOptions->delete(self::IS_CLONEABLE_KEY));
    }




    public function isPhpShortTagsEnabled(): bool
    {
        return in_array(strtolower(ini_get('short_open_tags')), ['1', 'on', 'true']);
    }






    public function isWpBakeryActive(): bool
    {
        return defined('WPB_VC_VERSION');
    }






    public function isJetpackActive(): bool
    {
        return class_exists('Jetpack');
    }




    public function getErrors(): array
    {
        return $this->errors;
    }




    public function isBitnami(): bool
    {
        return ABSPATH === '/opt/bitnami/wordpress/';
    }




    public function isWpContentOutsideAbspath(): bool
    {
        $wpContentDir = wp_normalize_path(WP_CONTENT_DIR);
        $abspath      = wp_normalize_path(ABSPATH);

        return !(strpos($wpContentDir, $abspath) === 0);
    }




    public function isUploadsOutsideAbspath(): bool
    {
        $uploadDir = wp_normalize_path(wp_upload_dir()['basedir']);
        $abspath   = wp_normalize_path(ABSPATH);

        return strpos($uploadDir, $abspath) !== 0;
    }




    public function isFlywheel(): bool
    {
        if (!$this->isWpContentOutsideAbspath()) {
            return false;
        }

        return file_exists(trailingslashit(wp_normalize_path(ABSPATH)) . '.fw-config.php');
    }




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









    public function isLocal(): bool
    {
        $host = strtolower((string)wp_parse_url(get_site_url(), PHP_URL_HOST));

        return apply_filters('wpstg.tests.is_local_site', $this->isLocalHost($host));
    }

    private function isLocalHost(string $host): bool
    {
        if ($host === '') {
            return false;
        }

        if (in_array($host, self::LOCAL_HOSTNAMES, true)) {
            return true;
        }

        foreach (self::LOCAL_HOSTNAME_SUFFIXES as $suffix) {
            if (substr($host, -strlen($suffix)) === $suffix) {
                return true;
            }
        }

        return $this->isPrivateIp($host);
    }





    private function isPrivateIp(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        foreach (self::LOCAL_IP_PREFIXES as $prefix) {
            if (strpos($host, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }





    public function isMultisite(): bool
    {
        return is_multisite();
    }
}
