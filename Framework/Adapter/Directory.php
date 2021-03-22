<?php

namespace WPStaging\Framework\Adapter;

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

        // Get absolute path to wordpress uploads directory e.g /var/www/wp-content/uploads/
        // Default is ABSPATH . 'wp-content/uploads', but it can be customized by the db option upload_path or the constant UPLOADS
        $uploadDir = wp_upload_dir(null, false, null)['basedir'];
        $uploadDir = trim(trailingslashit($uploadDir));

        $this->uploadDir = $uploadDir;

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
