<?php

namespace WPStaging\Framework\Utils;

use WPStaging\Backup\Exceptions\BackupRuntimeException;
use WPStaging\Core\WPStaging;
use WPStaging\Backup\Service\Archiver;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Framework\Adapter\Directory;

class Urls
{
    /**
     * Retrieves the URL for a given site where the front end is accessible.
     *
     * Returns the 'home' option with the appropriate protocol. The protocol will be 'https'
     * if is_ssl() evaluates to true; otherwise, it will be the same as the 'home' option.
     * If `$scheme` is 'http' or 'https', is_ssl() is overridden.
     * @param int|null $blogId
     * @param string|null $scheme
     * @return string
     */
    public function getHomeUrl($blogId = null, $scheme = null): string
    {
        if (empty($blogId) || !is_multisite()) {
            $url = get_option('home');
        } else {
            switch_to_blog($blogId);
            $url = get_option('home');
            restore_current_blog();
        }

        if (!in_array($scheme, ['http', 'https', 'relative'])) {
            if (is_ssl()) {
                $scheme = 'https';
            } else {
                $scheme = parse_url($url, PHP_URL_SCHEME);
            }
        }

        return set_url_scheme($url, $scheme);
    }

    /**
     * Return WordPress home url without scheme e.h. host.com or www.host.com
     * @return string
     */
    public function getHomeUrlWithoutScheme(): string
    {
        return preg_replace('#^https?://#', '', rtrim($this->getHomeUrl(), '/'));
    }

    /**
     * Retrieves the URL for a given site where the front end is accessible.
     *
     * Returns the 'home' option with the appropriate protocol. The protocol will be 'https'
     * if is_ssl() evaluates to true; otherwise, it will be the same as the 'home' option.
     * If `$scheme` is 'http' or 'https', is_ssl() is overridden.
     * @param int|null $blogId
     * @param string|null $scheme
     * @return string
     */
    public function getSiteUrl($blogId = null, $scheme = null): string
    {
        if (empty($blogId) || !is_multisite()) {
            $url = get_option('siteurl');
        } else {
            switch_to_blog($blogId);
            $url = get_option('siteurl');
            restore_current_blog();
        }

        if (!in_array($scheme, ['http', 'https', 'relative'])) {
            if ($this->sslAvailable()) {
                $scheme = 'https';
            } else {
                $scheme = parse_url($url, PHP_URL_SCHEME);
            }
        }

        return set_url_scheme($url, $scheme);
    }

    /**
     * Get raw base URL e.g. https://blog.domain.com or https://domain.com without any subfolder
     * @return string
     */
    public function getBaseUrl(): string
    {
        $result = parse_url($this->getHomeUrl());
        return $result['scheme'] . "://" . $result['host'];
    }

    /**
     * Return base URL (domain) without scheme e.g. blog.domain.com or domain.com
     * @return string
     */
    public function getBaseUrlWithoutScheme(): string
    {
        return preg_replace('#^https?://#', '', rtrim($this->getBaseUrl(), '/'));
    }

    /**
     * Get hostname of production site including scheme
     * @return string
     */
    public function getProductionHostname(): string
    {
        $connection = get_option('wpstg_connection');
        // Get the stored hostname
        if (!empty($connection['prodHostname'])) {
            return $connection['prodHostname'];
        }

        // Default. Try to get the hostname from the main domain (Workaround for WP Staging Pro older < 2.9.1)
        $siteurl = get_site_url();
        $result  = parse_url($siteurl);
        return $result['scheme'] . "://" . $result['host'];
    }

    /**
     * Get url of the uploads directory, e.g. http://example.com/wp-content/uploads
     * @return string
     */
    public function getUploadsUrl(): string
    {
        $upload_dir = wp_upload_dir(null, false, false);
        return trailingslashit($upload_dir['baseurl']);
    }

    /**
     * Get url of the backup directory, e.g. http://example.com/*
     * @return string
     * @throws BackupRuntimeException
     */
    public function getBackupUrl(): string
    {
        if (!WPStaging::make(Directory::class)->isBackupPathOutsideAbspath()) {
            return $this->getUploadsUrl() . WPSTG_PLUGIN_DOMAIN . '/' . Archiver::BACKUP_DIR_NAME . '/';
        }

        $backupDirAbsPath     = WPStaging::make(BackupsFinder::class)->getBackupsDirectory();
        $normalizedBackupPath = wp_normalize_path($backupDirAbsPath);
        $normalizedAbspath    = wp_normalize_path(ABSPATH);
        $uploads              = wp_upload_dir(null, false);
        if (strpos($normalizedBackupPath, wp_normalize_path($uploads['basedir'])) === 0) {
            $relativePath = str_replace(wp_normalize_path($uploads['basedir']), "", $normalizedBackupPath);
            return trailingslashit($uploads['baseurl']) . ltrim($relativePath, '/');
        }

        $relativePathToBackupDir = str_replace($normalizedAbspath, "", $normalizedBackupPath);
        $siteurl                 = $this->maybeUseProtocolRelative(get_option('siteurl'));
        return trailingslashit($siteurl) . ltrim($relativePathToBackupDir, '/');
    }

    /** @return bool */
    public function sslAvailable(): bool
    {
        // Cloudflare
        if (!empty($_SERVER['HTTP_CF_VISITOR'])) {
            // phpcs:ignore WPStagingCS.Security.SanitizeInput.InputNotSanitized
            $cfo = json_decode($_SERVER['HTTP_CF_VISITOR']);
            if (isset($cfo->scheme) && $cfo->scheme === 'https') {
                return true;
            }
        }

        // Other proxy
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        return is_ssl();
    }

    /**
     * @param string $url
     * @return string
     */
    public function maybeUseProtocolRelative(string $url): string
    {
        if ($this->sslAvailable() && substr($url, 0, 7) === 'http://') {
            $url = preg_replace('@^http://@', '//', $url);
        }

        return $url;
    }
}
