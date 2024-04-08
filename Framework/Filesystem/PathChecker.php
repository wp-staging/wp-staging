<?php

namespace WPStaging\Framework\Filesystem;

use UnexpectedValueException;
use WPStaging\Framework\Adapter\Directory;

/**
 * Class PathChecker
 * This class is created to avoid circular dependency between Filesystem, PathIdentifier and Directory classes
 * @package WPStaging\Framework\Filesystem
 */
class PathChecker
{
    /** @var Filesystem */
    private $filesystem;

    /** @var Directory */
    private $directory;

    /** @var PathIdentifier */
    private $pathIdentifier;

    /**
     * PathChecker constructor.
     *
     * @param Filesystem     $filesystem
     * @param Directory      $directory
     * @param PathIdentifier $pathIdentifier
     */
    public function __construct(Filesystem $filesystem, Directory $directory, PathIdentifier $pathIdentifier)
    {
        $this->filesystem     = $filesystem;
        $this->directory      = $directory;
        $this->pathIdentifier = $pathIdentifier;
    }

    /**
     * Check whether the path exists in the list.
     * If the isRelative flag is checked it will ignore ABSPATH (root path of WP Installation),
     * from both the paths during checking
     *
     * @param string  $path        The path to check
     * @param array   $list        List of path to check against
     * @param bool    $isRelative  Should the ABSPATH be ignored when checking. Default false
     * @param ?string $basePath    Use ABSPATH if null or empty otherwise use the provided path as base path
     *
     * @return bool
     */
    public function isPathInPathsList(string $path, array $list, bool $isRelative = false, $basePath = null): bool
    {
        if (empty($basePath)) {
            $basePath = $this->directory->getAbsPath();
        }

        $basePath = $this->filesystem->normalizePath($basePath);
        $path     = $this->filesystem->normalizePath($path);
        // remove ABSPATH and add leading slash if not present
        if ($isRelative) {
            $path = '/' . ltrim(str_replace($basePath, '', $path), '/');
        }

        foreach ($list as $pathItem) {
            $pathItem = $this->filesystem->normalizePath($pathItem);
            try {
                $pathItem = $this->pathIdentifier->transformIdentifiableToPath($pathItem);
            } catch (UnexpectedValueException $ex) {
            }

            // remove ABSPATH and add leading slash if not present
            if ($isRelative) {
                $pathItem = '/' . ltrim(str_replace($basePath, '', $pathItem), '/');
            }

            if ($path === $pathItem) {
                return true;
            }

            // Check whether directory a child of any excluded directories
            if (strpos($path, $pathItem . '/') === 0) {
                return true;
            }
        }

        return false;
    }
}
