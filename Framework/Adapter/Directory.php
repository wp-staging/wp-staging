<?php

namespace WPStaging\Framework\Adapter;

use RuntimeException;

class Directory
{
    /** @var string|null */
    private $uploadDir;

    /**
     * @noinspection PhpUnused
     * @return string
     */
    public function getCacheDirectory()
    {
        return $this->getPluginUploadsDirectory() . 'cache/';
    }

    /**
     * @noinspection PhpUnused
     * @return string
     */
    public function getLogDirectory()
    {
        return $this->getPluginUploadsDirectory() . $this->getDomain() . 'logs/';
    }

    public function getPluginUploadsDirectory()
    {
        return trailingslashit($this->getUploadsDirectory() . $this->getDomain());
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
        return WPSTG_PLUGIN_DOMAIN;
    }

    public function getSlug()
    {
	    return WPSTG_PLUGIN_SLUG;
    }
}
