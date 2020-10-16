<?php

namespace WPStaging\Framework\Adapter;

use RuntimeException;

class Directory
{

    /** @var string */
    private $domain;

    /** @var string */
    private $slug;

    /** @var string|null */
    private $uploadDir;

    /**
     * Domain: wp-staging
     * Slug: wp-staging | wp-staging-pro
     *
     * @param string $domain
     * @param string $slug
     */
    public function __construct($domain, $slug)
    {
        $this->domain = $domain;
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getPluginDirectory()
    {
        return sprintf('%s/%s/', WP_PLUGIN_DIR, $this->slug);
    }

    /**
     * @noinspection PhpUnused
     * @return string
     */
    public function getCacheDirectory()
    {
        $directory = sprintf('%scache', $this->getPluginUploadsDirectory());
        return trailingslashit($directory);
    }

    /**
     * @noinspection PhpUnused
     * @return string
     */
    public function getLogDirectory()
    {
        $directory = sprintf('%s%s/logs', $this->getUploadsDirectory(), $this->domain);
        return trailingslashit($directory);
    }

    public function getPluginUploadsDirectory()
    {
        return trailingslashit($this->getUploadsDirectory() . $this->domain);
    }

    /**
     * Absolute Path
     * @return string
     */
    public function getUploadsDirectory()
    {
        if ($this->uploadDir) {
            return $this->uploadDir;
        }

        // Get upload directory information. Default is ABSPATH . 'wp-content/uploads'
        // Can be customized by populating the db option upload_path or the constant UPLOADS
        // If both are defined WordPress will uses the value of the UPLOADS constant
        $dir = wp_upload_dir();

        // TODO RPoC
        if ($dir['error']) {
            throw new RuntimeException($dir['error']);
        }

        // Get absolute path to wordpress uploads directory e.g /var/www/wp-content/uploads/
        $this->uploadDir = trailingslashit($dir['basedir']);
        return $this->uploadDir;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function findPluginDirectoryName()
    {
        $dir = ltrim(str_replace([WP_PLUGIN_DIR, '\\'], [null, '/'], __DIR__), '/');
        $parts = explode('/', $dir);
        return $parts && isset($parts[0])? $parts[0] : null;
    }
}
