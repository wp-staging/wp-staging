<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints
// TODO PHP7.1; constant visibility

namespace WPStaging\Framework\Utils\Cache;

use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Exceptions\IOException;
use WPStaging\Framework\Adapter\Directory;

abstract class AbstractCache
{
    const DEFAULT_LIFETIME = 2592000; // 30 days
    const EXTENSION = 'cache';

    /** @var int */
    protected $lifetime;

    /** @var string */
    protected $path;

    /** @var string */
    protected $filename;

    /** @var string */
    protected $filePath;

    public function __construct(Directory $directory)
    {
        $this->setPath($directory->getCacheDirectory());
        $this->setLifetime(self::DEFAULT_LIFETIME);
    }

    /**
     * @param null|mixed $default
     *
     * @return array|mixed|object|null
     */
    abstract public function get($default = null);

    /**
     * @param mixed $value
     *
     * @return bool
     */
    abstract public function save($value);

    public function delete()
    {
        if (!is_file($this->filePath)) {
            return;
        }

        if (unlink($this->filePath)) {
            return;
        }

        throw new IOException(sprintf('Attempting to delete invalid cache file (%s) failed', $this->filePath));
    }

    /**
     * @return int
     */
    public function getLifetime()
    {
        return $this->lifetime;
    }

    /**
     * @param int $lifetime
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = (int) $lifetime;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;

        (new Filesystem())->mkdir($path, true);

        $this->initializeFilePath();
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        $this->initializeFilePath();
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @param $fileName
     *
     * @return bool
     */
    public function cacheExists($fileName)
    {
        return is_file(trailingslashit($this->getPath()) . $fileName . '.' . static::EXTENSION);
    }

    /**
     * @return bool
     */
    protected function isValid()
    {
        if (!$this->filePath || !is_file($this->filePath)) {
            return false;
        }

        if (!$this->isExpired()) {
            return true;
        }

        $this->delete();
        return false;
    }

    /**
     * @return bool
     */
    protected function isExpired()
    {
        if ($this->lifetime === -1) {
            return false;
        }

        return $this->lifetime <= time() - filemtime($this->filePath);
    }

    private function initializeFilePath()
    {
        $this->filePath = $this->path;
        if ($this->filename) {
            $this->filePath .= $this->filename . '.' . self::EXTENSION;
        }
    }
}
