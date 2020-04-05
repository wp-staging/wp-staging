<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types & type-hint

namespace WPStaging\Manager\FileSystem;

use RuntimeException;
use WPStaging\Service\Adapter\Directory;

class DirectoryManager
{
    /** @var Directory */
    private $adapter;

    public function __construct(Directory $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @noinspection PhpUnused
     * @return string
     */
    public function getRelativeUploadsDirectory()
    {
        return str_replace(ABSPATH, null, $this->adapter->getUploadsDirectory());
    }

    /**
     * @param string $dirname
     * @return string
     */
    public function provideCustomUploadsDirectory($dirname)
    {
        $fullPath = trailingslashit($this->adapter->getUploadsDirectory()) . trim($dirname, '/\\');

        // TODO RPoC
        if (!wp_mkdir_p($fullPath)) {
            throw new RuntimeException('Failed to create directory ' . $fullPath);
        }

        return trailingslashit($fullPath);
    }
}
