<?php

namespace WPStaging\Service\Adapter;

use RuntimeException;

class Directory
{
    /** @var string */
    private $slug;

    /** @var string|null */
    private $uploadDir;

    /**
     * @param string $slug
     */
    public function __construct($slug)
    {
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
        // TODO implement
    }

    /**
     * @noinspection PhpUnused
     * @return string
     */
    public function getLogDirectory()
    {
        // TODO implement
    }

    /**
     * Relative Path
     * @noinspection PhpUnused
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
}
