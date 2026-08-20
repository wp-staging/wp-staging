<?php

namespace WPStaging\Framework\Utils;

use WPStaging\Backup\Service\BackupsDirectoryResolver;
use WPStaging\Core\WPStaging;




class Urls
{










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





    public function getHomeUrlWithoutScheme(): string
    {
        return preg_replace('#^https?://#', '', rtrim($this->getHomeUrl(), '/'));
    }











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





    public function getBaseUrl(): string
    {
        $result = parse_url($this->getHomeUrl());
        return $result['scheme'] . "://" . $result['host'];
    }





    public function getBaseUrlWithoutScheme(): string
    {
        return preg_replace('#^https?://#', '', rtrim($this->getBaseUrl(), '/'));
    }





    public function getProductionHostname(): string
    {
        $connection = get_option('wpstg_connection');
 
        if (!empty($connection['prodHostname'])) {
            return $connection['prodHostname'];
        }

 
        $siteurl = get_site_url();
        $result  = parse_url($siteurl);
        return $result['scheme'] . "://" . $result['host'];
    }





    public function getUploadsUrl(): string
    {
        $upload_dir = wp_upload_dir(null, false, false);
        return trailingslashit($upload_dir['baseurl']);
    }






    public function getBackupUrl(): string
    {
        $uploads              = wp_upload_dir(null, false);
        $backupsDirResolver   = WPStaging::make(BackupsDirectoryResolver::class);
        $normalizedBackupPath = $backupsDirResolver->resolveFromUploadsDirectory($uploads['basedir']);
        $normalizedUploadsDir = trailingslashit(wp_normalize_path($uploads['basedir']));

        if (strpos($normalizedBackupPath, $normalizedUploadsDir) === 0) {
            $relativePath = substr($normalizedBackupPath, strlen($normalizedUploadsDir));
            return trailingslashit($this->maybeUseProtocolRelative($uploads['baseurl'])) . ltrim($relativePath, '/');
        }

        $normalizedWpContentDir = trailingslashit(wp_normalize_path(WP_CONTENT_DIR));
        if (strpos($normalizedBackupPath, $normalizedWpContentDir) === 0) {
            $relativePath = substr($normalizedBackupPath, strlen($normalizedWpContentDir));
            return trailingslashit($this->maybeUseProtocolRelative(content_url())) . ltrim($relativePath, '/');
        }

        $normalizedAbspath = trailingslashit(wp_normalize_path(ABSPATH));
        $relativePath      = $normalizedBackupPath;
        if (strpos($normalizedBackupPath, $normalizedAbspath) === 0) {
            $relativePath = substr($normalizedBackupPath, strlen($normalizedAbspath));
        }

        $siteurl = $this->maybeUseProtocolRelative(get_option('siteurl'));

        return trailingslashit($siteurl) . ltrim($relativePath, '/');
    }

 
    public function sslAvailable(): bool
    {
 
        if (!empty($_SERVER['HTTP_CF_VISITOR'])) {
            // phpcs:ignore WPStagingCS.Security.SanitizeInput.InputNotSanitized
            $cfo = json_decode($_SERVER['HTTP_CF_VISITOR']);
            if (isset($cfo->scheme) && $cfo->scheme === 'https') {
                return true;
            }
        }

 
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        return is_ssl();
    }





    public function maybeUseProtocolRelative(string $url): string
    {
        if ($this->sslAvailable() && substr($url, 0, 7) === 'http://') {
            $url = preg_replace('@^http://@', '//', $url);
        }

        return $url;
    }
}
