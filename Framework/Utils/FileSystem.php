<?php

// TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Framework\Utils;

use FilesystemIterator;

/**
 * Class FileSystem
 *
 * @todo Remove this class in favor of:
 * @see \WPStaging\Framework\Filesystem\Filesystem
 *
 * @package WPStaging\Framework\Utils
 */
class FileSystem
{
    /*
     * Makes sure all paths contain linux directory separator forward slash (/). Windows supports backslash and slash where linux only understands forward slash
     * Windows understands both / and \
     *
     */
    public function replaceWindowsDirSeparator($path)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return $path;
        }

        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    /*
     * Makes sure all paths contain linux directory separator forward slash (/). Windows supports backslash and slash where linux only understands forward slash
     * Windows understands both / and \
     *
     * This does the same as replaceWindowsDirSeparator() but is probably not as performant as variant 1.
     * Not tested it, yet. Keep it here for todo testing purposes
     */
    public function replaceWindowsDirSeparator2($path)
    {
        return preg_replace('/[\\\\]+/', '/', $path);
    }

    /**
     * @param string $dir
     * @return bool True if directory is empty. False if empty or does not exist
     */
    public function isEmptyDir($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }

        return (new FilesystemIterator($dir))->valid() === false;
    }
}
