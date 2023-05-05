<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints
// TODO PHP7.1; constant visibility

namespace WPStaging\Framework\Utils\Cache;

use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Exceptions\IOException;
use WPStaging\Framework\Adapter\Directory;

use function WPStaging\functions\debug_log;

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

    /**
     * @return void
     * @throws IOException
     */
    public function delete()
    {
        if (!is_file($this->filePath)) {
            return;
        }

        if (unlink($this->filePath)) {
            return;
        }

        debug_log(sprintf('Attempting to delete invalid cache file (%s) failed', $this->filePath));
        throw new IOException(sprintf('Attempting to delete invalid cache file (%s) failed', $this->filePath));
    }

    /**
     * @param int $lifetime
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = (int)$lifetime;
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
     * If cache is not valid, it will be deleted
     * @param $delete
     * @return bool
     * @throws IOException
     */
    public function isValid($delete = true)
    {
        if (!$this->filePath || !is_file($this->filePath)) {
            return false;
        }

        if (!$this->isExpired()) {
            return true;
        }

        if ($delete) {
            $this->delete();
        }

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
