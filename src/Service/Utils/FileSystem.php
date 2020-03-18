<?php

// TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Service\Utils;

class FileSystem
{
    /*
     * Makes sure all paths contain linux separator (/) which works fine on all windows systems, too
     * Windows understands both / and \
     */
    public function compatiblePath($path)
    {
        if ('/' === DIRECTORY_SEPARATOR) {
            return $path;
        }

        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    public function replaceWindowsDirSeparator($path)
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

        return false === (new FilesystemIterator($dir))->valid();
    }

    /**
     * Delete files and folder. Deals with directories recursively
     *
     * @param string $fullPath
     */
    public function deleteFiles($fullPath)
    {
        if (is_file($fullPath)) {
            unlink($fullPath);
            return;
        }

        if (!is_dir($fullPath) || $this->isEmptyDir($fullPath)) {
            return;
        }

        $di = new RecursiveDirectoryIterator($fullPath, FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $file) {
            $this->deleteFiles($file);
        }

        rmdir($fullPath);
    }
}
