<?php

namespace WPStaging\Core\Utils;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

use Exception;
use WPStaging\Framework\Filesystem\Filesystem;

/**
 * Class Cache
 * @package WPStaging\Core\Utils
 */
class Cache
{

    /**
     * Cache directory (full path)
     * @var string
     */
    private $cacheDir;

    /**
     * Cache file extension
     * @var string
     */
    private $cacheExtension = "cache";

    /**
     * Lifetime of cache files in seconds
     * @var int
     */
    private $lifetime = 2592000; // 30 days

    /**
     * @todo This is referenced by \WPStaging\Core\Utils\Cache::returnException, but doesn't seem to be populated ever. Remove this.
     * @var array
     */
    private $options;

    /**
     * Cache constructor.
     * @param null|int $lifetime
     * @param null|string $cacheDir
     * @param null|string $cacheExtension
     * @throws \Exception
     */

    public function __construct($lifetime = null, $cacheDir = null, $cacheExtension = null)
    {
        // Set lifetime
        $lifetime = (int) $lifetime;
        if ($lifetime > 0) {
            $this->lifetime = $lifetime;
        }

        // Set cache directory
        if (!empty($cacheDir) && is_dir($cacheDir)) {
            $this->cacheDir = $cacheDir;
        }
        // Set default
        else {
            $this->cacheDir = \WPStaging\Core\WPStaging::getContentDir();
        }

        // Set cache extension
        if (!empty($cacheExtension)) {
            $this->cacheExtension = $cacheExtension;
        }

        /**
         * If cache directory doesn't exists, create it.
         *
         * There's no need to handle failure to create here,
         * as a warning is already displayed on:
         *
         * @see \WPStaging\Framework\Notices\Notices::renderNotices
         */
        (new Filesystem())->mkdir($this->cacheDir);
    }

    /**
     * Get cache
     * @param string $cacheFileName
     * @param mixed $defaultValue
     * @param null|int $lifetime
     * @return mixed|null
     */
    public function get($cacheFileName, $defaultValue = null, $lifetime = null)
    {
        // Check if file is valid
        if (($cacheFile = $this->isValid($cacheFileName, true, $lifetime)) === false) {
            return $defaultValue;
        }

        return @unserialize(file_get_contents($cacheFile));
    }

    /**
     * Saves value to given cache file
     * @param string $cacheFileName
     * @param mixed $value
     * @return bool
     * @throws \Exception
     */
    public function save($cacheFileName, $value)
    {
        $cacheFile = $this->cacheDir . $cacheFileName . '.' . $this->cacheExtension;

        // Attempt to delete cache file if it exists
        if (is_file($cacheFile) && !@unlink($cacheFile)) {
            $this->returnException("Can't delete existing cache file");
            throw new \Exception("Can't delete existing cache file");
        }

        try {
            // Save it to file
            if (!wpstg_put_contents($cacheFile, @serialize($value))) {
                $this->returnException(" Can't save data to: " . $cacheFile . " Disk quota exceeded or not enough free disk space left. Ask your host to increase the storage disk space.");
                return false;
            }
        } catch (Exception $e) {
            $this->returnException(" Can't write to: " . $cacheFile . " Error: " . $e);
            return false;
        }
        return true;
    }

    /**
     * Checks if file is valid or not
     * @param $cacheFileName
     * @param bool $deleteFileIfInvalid
     * @param null|int $lifetime
     * @return string|bool
     * @throws \Exception
     */
    public function isValid($cacheFileName, $deleteFileIfInvalid = false, $lifetime = null)
    {
        // Lifetime
        $lifetime = (int) $lifetime;
        if ($lifetime < -1 || $lifetime == 0) {
            $lifetime = $this->lifetime;
        }

        // Get full path of the given cache file
        $cacheFile = $this->cacheDir . $cacheFileName . '.' . $this->cacheExtension;

        // File doesn't exist
        if (!is_file($cacheFile)) {
            return false;
        }

        // As long as file exists, don't check lifetime
        if ($lifetime == -1) {
            return $cacheFile;
        }

        // Time is up, file is invalid
        if ($lifetime <= time() - filemtime($cacheFile)) {
            // Attempt to delete the file
            if ($deleteFileIfInvalid === true && !@unlink($cacheFile)) {
                throw new \Exception("Attempting to delete invalid cache file has failed!");
            }

            // No need to delete the file, return
            return false;
        }

        return $cacheFile;
    }

    /**
     * Delete a cache file
     * @param string $cacheFileName
     * @return bool
     * @throws \Exception
     */
    public function delete($cacheFileName)
    {
        if (($cacheFile = $this->isValid($cacheFileName, true)) !== false && @unlink($cacheFile) === false) {
            throw new \Exception("Couldn't delete cache: {$cacheFileName}. Full Path: {$cacheFile}");
        }

        return true;
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * @return string
     */
    public function getCacheExtension()
    {
        return $this->cacheExtension;
    }

    /**
     * Throw a errror message via json and stop further execution
     * @param string $message
     */
    protected function returnException($message = '')
    {
        wp_die(json_encode([
            'job'     => isset($this->options->currentJob) ? $this->options->currentJob : '',
            'status'  => false,
            'message' => $message,
            'error'   => true
        ]));
    }
}
