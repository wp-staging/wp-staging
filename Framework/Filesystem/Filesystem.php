<?php

namespace WPStaging\Framework\Filesystem;

use SplFileInfo;
use WPStaging\Backend\Notices\Notices;
use WPStaging\Core\WPStaging;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use RuntimeException;

class Filesystem extends FilterableDirectoryIterator
{
    /** @var string|null */
    private $path;

    /** @var callable|null */
    private $shouldStop;

    /** @var int|null */
    private $depth;

    /** @var string[]|array|null */
    private $fileNames;

    /** @var LoggerInterface|null */
    private $logger;

    /** @var boolean|null */
    private $bypassPermissionExceptions;

    /** @var array */
    private $logs = [];

    /**
     * @return array
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * Safe path makes sure given path is within WP root directory
     * @param string $fullPath
     * @return string|null
     */
    public function safePath($fullPath)
    {
        $safePath = realpath(dirname($fullPath));
        if (!$safePath) {
            return null;
        }
        $safePath = ABSPATH . str_replace(ABSPATH, null, $safePath);
        $safePath .= DIRECTORY_SEPARATOR . basename($fullPath);
        return $safePath;
    }

    /**
     * Move content from one path to another
     * This is better than $this->rename() method as this use custom fileiterator and $this->delete()
     * @param string $source
     * @param string $target
     *
     * @return boolean Whether the move was successful or not.
     */
    public function move($source, $target)
    {
        // if $source is link or file, move it and stop execution
        if (is_link($source) || is_file($source)) {
            return $this->renameDirect($source, $target);
        }

        // if $source is empty dir
        if ($this->isEmptyDir($source)) {
            return wp_mkdir_p($target) && @rmdir($source);
        }

        $this->setDirectory($source);
        $iterator = null;
        try {
            $iterator = $this->setIteratorMode(\RecursiveIteratorIterator::CHILD_FIRST)->get();
        } catch (FilesystemExceptions $e) {
            $this->log('Permission Error: Can not create recursive iterator for ' . $source);
            return false;
        }

        $basePath = trailingslashit($target);
        foreach ($iterator as $item) {
            if ($item->isDir() && !$this->isEmptyDir($item->getPathname())) {
                continue;
            }

            $relativeFilePath = $iterator->getFilename();
            if ($this->isIteratorRecursive()) {
                $relativeFilePath = $iterator->getSubPathName();
            }

            $destination = $basePath . $relativeFilePath;
            if (file_exists($destination)) {
                continue;
            }

            $result = false;
            // if empty dir
            if ($item->isDir()) {
                $result = wp_mkdir_p($destination) && @rmdir($item->getPathname());
            } else { // if file or link
                $result = $this->renameDirect($item->getPathname(), $destination);
            }

            if (!$result || !is_callable($this->shouldStop)) {
                continue;
            }

            if (call_user_func($this->shouldStop)) {
                return false;
            }
        }

        $deleteSelf = true;
        if (count($this->getExcludePaths()) > 0 || !$this->isIteratorRecursive()) {
            $deleteSelf = false;
        }

        return $this->delete($source, $deleteSelf);
    }

    /**
     * @param string $source
     * @param string $target
     *
     * @return bool Whether the rename was successful or not.
     */
    public function renameDirect($source, $target)
    {
        $dir = dirname($target);
        if (!file_exists($dir)) {
            $this->mkdir($dir);
        }

        $renamed = @rename($source, $target);

        if (!$renamed) {
            $this->log(sprintf('Failed to move %s to %s', $source, $target));
        }

        return $renamed;
    }

    /**
     * @param string|null $path The path to the new folder, or null to use FileSystem's path.
     *
     * @return string Path to newly created folder, or empty string if couldn't create it.
     */
    public function mkdir($path, $preventDirectoryListing = false)
    {
        $path = $this->findPath($path);

        if (!wp_mkdir_p($path)) {
            if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                error_log("Failed to create directory $path");
            }

            return '';
        }

        if ($preventDirectoryListing) {
            /** @var DirectoryListing $directoryListing */
            $directoryListing = WPStaging::getInstance()->getContainer()->make(DirectoryListing::class);
            try {
                $directoryListing->preventDirectoryListing($path);
            } catch (\Exception $e) {
                /**
                 * Enqueue this error. All enqueued errors will be shown as a single notice.
                 *
                 * @see \WPStaging\Backend\Notices\Notices::showDirectoryListingWarningNotice
                 */
                WPStaging::getInstance()->getContainer()->pushToArray(Notices::$directoryListingErrors, $e->getMessage());
            }
        }

        return trailingslashit($path);
    }

    /**
     * The new copy method which works for files, links and directories
     *
     * @param string $source
     * @param string $target
     * @return boolean
     */
    public function copy($source, $target)
    {
        // if $source is link or file, move it and stop execution
        if (is_link($source) || is_file($source)) {
            $this->mkdir(dirname($target));
            return copy($source, $target);
        }

        // if $source is empty dir
        if ($this->isEmptyDir($source)) {
            return wp_mkdir_p($target);
        }

        $this->setDirectory($source);
        $iterator = null;
        try {
            $iterator = $this->setIteratorMode(\RecursiveIteratorIterator::CHILD_FIRST)->get();
        } catch (FilesystemExceptions $e) {
            $this->log('Permission Error: Can not create recursive iterator for ' . $source);
            return false;
        }

        $basePath = trailingslashit($target);
        foreach ($iterator as $item) {
            if ($item->isDir() && !$this->isEmptyDir($item->getPathname())) {
                continue;
            }

            $relativeFilePath = $iterator->getFilename();
            if ($this->isIteratorRecursive()) {
                $relativeFilePath = $iterator->getSubPathName();
            }

            $destination = $basePath . $relativeFilePath;
            if (file_exists($destination)) {
                continue;
            }

            $result = false;
            // if empty dir
            if ($item->isDir()) {
                $result = wp_mkdir_p($destination);
            } else { // if file or link
                $this->mkdir(dirname($destination));
                $result = copy($item->getPathname(), $destination);
            }

            if (!$result || !is_callable($this->shouldStop)) {
                continue;
            }

            if (call_user_func($this->shouldStop)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if directory exists and is not empty
     * @param string $dir
     * @return bool
     */
    public function isEmptyDir($dir)
    {
        if (is_dir($dir)) {
            $iterator = new \FilesystemIterator($dir);
            return !$iterator->valid();
        }
        return true;
    }

    /**
     * Deletes a directory recursively or not.
     *
     * @see \WPStaging\Framework\Filesystem\FilterableDirectoryIterator::setRecursive To control whether this function should delete recursively.
     *
     * @param string $path
     * @param bool   $deleteSelf Whether to delete the target folder after deleting it's contents.
     * @throws FilesystemExceptions Only if $throw is true.
     *
     * @return bool True if target was completely deleted, false if file not deleted or folder still have contents.
     */
    public function delete($path = null, $deleteSelf = true, $throw = false)
    {
        $path = $this->findPath($path);

        if ($path === ABSPATH) {
            $this->log('You can not delete WP Root directory');
            throw new RuntimeException('You can not delete WP Root directory');
        }

        clearstatcache();

        // if $path is link or file, delete it and stop execution
        if (is_link($path) || is_file($path)) {
            if (!unlink($path)) {
                $this->log('Permission Error: Can not delete file ' . $path);
                return false;
            }

            return true;
        }

        // Assume it is already deleted
        if (!is_dir($path)) {
            return true;
        }

        // delete the directory if it is empty and deleteSelf was true
        if (is_dir($path) && $this->isEmptyDir($path) && $deleteSelf) {
            if (!@rmdir($path)) {
                $this->log('Permission Error: Can not delete directory ' . $path);
                return false;
            }

            return true;
        }

        // return since directory was empty and deleteSelf was false
        if (is_dir($path) && $this->isEmptyDir($path) && !$deleteSelf) {
            return true;
        }

        $this->setDirectory($path);
        $originalIsRecursive = (bool)$this->isIteratorRecursive();
        try {
            /*
             * For historical reasons, this function will run as Recursive Mode by default.
             * To minimize any side-effects of calling this method on an existing instance
             * of Filesystem, we will store the original isRecursive, and set it to the
             * original value before returning.
             */
            if ($this->isIteratorRecursive() === null) {
                $this->setRecursive();
            }

            $iterator = $this->setIteratorMode(\RecursiveIteratorIterator::CHILD_FIRST)->get();
        } catch (FilesystemExceptions $e) {
            $this->log('Permission Error: Can not create recursive iterator for ' . $path);
            if ($throw) {
                $this->setRecursive($originalIsRecursive);
                // This allows us to know that Filesystem FAILED and should not continue;
                throw $e;
            } else {
                $this->setRecursive($originalIsRecursive);
                return false;
            }
        }

        foreach ($iterator as $item) {
            $result = false;

            try {
                $result = $this->deleteItem($item);
            } catch (RuntimeException $e) {
                if ($this->arePermissionExceptionsBypassed() !== true) {
                    $this->setRecursive($originalIsRecursive);

                    throw $e;
                }
            }

            if (!$result || !is_callable($this->shouldStop)) {
                continue;
            }

            if (call_user_func($this->shouldStop)) {
                $this->setRecursive($originalIsRecursive);
                return false;
            }
        }

        // If deleteSelf flag is false or the directory is not empty, stop execution
        if (!$deleteSelf || !$this->isEmptyDir($path)) {
            $this->setRecursive($originalIsRecursive);
            return true;
        }

        // Delete the empty directory itself and finish execution
        if (is_dir($path)) {
            if (!rmdir($path)) {
                $this->log('Permission Error: Can not delete directory ' . $path);
            }
        }

        $this->setRecursive($originalIsRecursive);
        return true;
    }

    /**
     * @param string $file full path + filename
     * @param array $excludedFiles List of filenames. Can be wildcard pattern like data.php, data*.php, *.php, .php
     * @return boolean
     */
    public function isFilenameExcluded($file, $excludedFiles)
    {
        $filename = basename($file);

        // Regular filenames
        if (in_array($filename, $excludedFiles, true)) {
            return true;
        }

        // Wildcards
        foreach ($excludedFiles as $pattern) {
            if ($this->fnmatch($pattern, $filename)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the passed string would match the given shell wildcard pattern.
     * This function emulates [[fnmatch()]], which may be unavailable at certain environment, using PCRE.
     * @param string $pattern the shell wildcard pattern.
     * @param string $string the tested string.
     * @param array $options options for matching. Valid options are:
     *
     * - caseSensitive: bool, whether pattern should be case sensitive. Defaults to `true`.
     * - escape: bool, whether backslash escaping is enabled. Defaults to `true`.
     * - filePath: bool, whether slashes in string only matches slashes in the given pattern. Defaults to `false`.
     *
     * @return bool whether the string matches pattern or not.
     */
    protected function fnmatch($pattern, $string, $options = [])
    {
        if ($pattern === '*' && empty($options['filePath'])) {
            return true;
        }

        $replacements = [
            '\\\\\\\\' => '\\\\',
            '\\\\\\*' => '[*]',
            '\\\\\\?' => '[?]',
            '\*' => '.*',
            '\?' => '.',
            '\[\!' => '[^',
            '\[' => '[',
            '\]' => ']',
            '\-' => '-',
        ];

        if (isset($options['escape']) && !$options['escape']) {
            unset($replacements['\\\\\\\\'], $replacements['\\\\\\*'], $replacements['\\\\\\?']);
        }

        if (!empty($options['filePath'])) {
            $replacements['\*'] = '[^/\\\\]*';
            $replacements['\?'] = '[^/\\\\]';
        }

        $pattern = strtr(preg_quote($pattern, '#'), $replacements);
        $pattern = '#^' . $pattern . '$#us';
        if (isset($options['caseSensitive']) && !$options['caseSensitive']) {
            $pattern .= 'i';
        }

        return preg_match($pattern, $string) === 1;
    }

    /**
     * @param array $paths
     * @return boolean
     */
    public function deletePaths($paths)
    {
        foreach ($paths as $path) {
            // only delete the dir if empty
            // helpful when we exclude path(s) during delete
            if (is_dir($path) && $this->isEmptyDir($path)) {
                if (!@rmdir($path)) {
                    $this->log('Permission Error: Can not delete directory ' . $path);
                    throw new RuntimeException('Permission Error: Can not delete directory ' . $path);
                }

                continue;
            }

            // force to not delete the parent path itself
            if (!$this->delete($path, false)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $path
     * @return string|null
     */
    public function findPath($path)
    {
        return $path ?: $this->path;
    }

    /**
     * @return boolean|null
     */
    public function arePermissionExceptionsBypassed()
    {
        return $this->bypassPermissionExceptions;
    }

    /**
     * @param boolean|null $flag
     * @return self
     */
    public function shouldPermissionExceptionsBypass($flag)
    {
        $this->bypassPermissionExceptions = $flag;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string|null $path
     * @return self
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return callable|null
     */
    public function getShouldStop()
    {
        return $this->shouldStop;
    }

    /**
     * @param callable|null $shouldStop
     * @return self
     */
    public function setShouldStop(callable $shouldStop = null)
    {
        $this->shouldStop = $shouldStop;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * @param int|null $depth
     * @return self
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;
        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getFileNames()
    {
        return $this->fileNames ?: [];
    }

    /**
     * @param array|string[] $fileNames
     * @return self
     */
    public function setFileNames($fileNames)
    {
        $this->fileNames = $fileNames;
        return $this;
    }

    /**
     * @param string $fileName
     * @return self
     */
    public function addFileName($fileName)
    {
        $this->fileNames[] = $fileName;
        return $this;
    }

    /**
     * @param LoggerInterface|null $logger
     * @return self
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Delete file or directory
     * @param SplFileInfo $item
     * @return bool
     */
    protected function deleteItem($item)
    {
        $path = $item->getPathname();

        if ($item->isLink()) {
            if (!$this->removeSymlink($path)) {
                $this->log('Permission Error: Can not delete link ' . $path);
                throw new RuntimeException('Permission Error: Can not delete link ' . $path);
            }
        }

        // Checks whether that file or directory exists
        if (!file_exists($path)) {
            return true;
        }

        if ($item->isDir()) {
            if (!$this->isEmptyDir($path)) {
                return false;
            }

            if (!@rmdir($path)) {
                $this->log('Permission Error: Can not delete folder ' . $path);
                throw new RuntimeException('Permission Error: Can not delete folder ' . $path);
            }

            return true;
        }

        if (!$item->isFile()) {
            return false;
        }

        if (!@unlink($path)) {
            $this->log('Permission Error: Can not delete file ' . $path);
            throw new RuntimeException('Permission Error: Can not delete file ' . $path);
        }

        return true;
    }

    /**
     * Remove symlink for both windows and other OSes
     * @param string $path Path to the link
     * @return boolean
     */
    protected function removeSymlink($path)
    {
        // remove symlink using rmdir if OS is windows
        if (PHP_SHLIB_SUFFIX === 'dll') {
            return @rmdir($path);
        }

        return @unlink($path);
    }

    /**
     * @param $string
     */
    protected function log($string)
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->warning($string);
            return;
        }

        $this->logs[] = $string;
    }

    /**
     * Create a file with content
     *
     * @param  string $path    Path to the file
     * @param  string $content Content of the file
     * @return boolean
     */
    public function create($path, $content)
    {
        if (!@file_exists($path)) {
            if (!@is_writable(dirname($path))) {
                return false;
            }

            if (!@touch($path)) {
                return false;
            }
        } elseif (!@is_writable($path)) {
            return false;
        }

        $written = false;
        if (( $handle     = @fopen($path, 'w') ) !== false) {
            if (@fwrite($handle, $content) !== false) {
                $written = true;
            }

            @fclose($handle);
        }

        return $written;
    }

    /**
     * Create a file with marker and content
     *
     * @param  string $path    Path to the file
     * @param  string $marker  Name of the marker
     * @param  string $content Content of the file
     * @return boolean
     */
    public function createWithMarkers($path, $marker, $content)
    {
        return @insert_with_markers($path, $marker, $content);
    }

    /**
     * This normalizes the given path in a specific way,
     * aiming to compare paths with precision.
     *
     * @param $path
     *
     * @return string
     */
    public function normalizePath($path)
    {
        $path = trim($path);
        $path = wp_normalize_path($path);
        $path = trailingslashit($path);

        return $path;
    }
}
