<?php

namespace WPStaging\Framework\Utils;

class Urls
{

    /**
     * Retrieves the URL for a given site where the front end is accessible.
     *
     * Returns the 'home' option with the appropriate protocol. The protocol will be 'https'
     * if is_ssl() evaluates to true; otherwise, it will be the same as the 'home' option.
     * If `$scheme` is 'http' or 'https', is_ssl() is overridden.
     */
    public function getHomeUrl($blog_id = null, $scheme = null)
    {

        if (empty($blog_id) || !is_multisite()) {
            $url = get_option('home');
        } else {
            switch_to_blog($blog_id);
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

        $url = set_url_scheme($url, $scheme);

        return $url;
    }

    /**
     * Return WordPress home url without scheme e.h. host.com or www.host.com
     * @param string $str
     * @return string
     */
    public function getHomeUrlWithoutScheme()
    {
        return preg_replace('#^https?://#', '', rtrim($this->getHomeUrl(), '/'));
    }

    /**
     * Retrieves the URL for a given site where the front end is accessible.
     *
     * Returns the 'home' option with the appropriate protocol. The protocol will be 'https'
     * if is_ssl() evaluates to true; otherwise, it will be the same as the 'home' option.
     * If `$scheme` is 'http' or 'https', is_ssl() is overridden.
     */
    public function getSiteUrl($blog_id = null, $scheme = null)
    {

        if (empty($blog_id) || !is_multisite()) {
            $url = get_option('siteurl');
        } else {
            switch_to_blog($blog_id);
            $url = get_option('siteurl');
            restore_current_blog();
        }

        if (!in_array($scheme, ['http', 'https', 'relative'])) {
            if (is_ssl()) {
                $scheme = 'https';
            } else {
                $scheme = parse_url($url, PHP_URL_SCHEME);
            }
        }

        $url = set_url_scheme($url, $scheme);

        return $url;
    }

    /**
     * Get raw base URL e.g. https://blog.domain.com or https://domain.com without any subfolder
     * @return string
     */
    public function getBaseUrl()
    {
        $result = parse_url($this->getHomeUrl());
        return $result['scheme'] . "://" . $result['host'];
    }

    /**
     * Return base URL (domain) without scheme e.g. blog.domain.com or domain.com
     * @param string $str
     * @return string
     */
    public function getBaseUrlWithoutScheme()
    {
        return preg_replace('#^https?://#', '', rtrim($this->getBaseUrl(), '/'));
    }

    /**
     * Get hostname of production site including scheme
     * @return string
     */
    public function getProductionHostname()
    {

        $connection = get_option('wpstg_connection');

        // Get the stored hostname
        if (!empty($connection['prodHostname'])) {
            return $connection['prodHostname'];
        }

        // Default. Try to get the hostname from the main domain (Workaround for WP Staging Pro older < 2.9.1)
        $siteurl = get_site_url();
        $result = parse_url($siteurl);
        return $result['scheme'] . "://" . $result['host'];
    }
}
