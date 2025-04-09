<?php

namespace WPStaging\Framework\Filesystem;

use Exception;
use SplFileInfo;
use WPStaging\Framework\Notices\Notices;
use WPStaging\Core\WPStaging;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use RuntimeException;
use WPStaging\Backend\Pro\Modules\Jobs\Copiers\Copier;
use WPStaging\Framework\Adapter\PhpAdapter;

use function WPStaging\functions\debug_log;

/**
 * Class Filesystem
 * @package WPStaging\Framework\Filesystem
 *
 * @todo check how can we reduce this class, maybe into multiple classes or use traits?
 */
class Filesystem extends FilterableDirectoryIterator
{
    /** @var array */
    const BACKUP_FILE_EXTENSION = ['.wpstg', '.wpstg.sql'];

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

    /** @var PhpAdapter */
    private $phpAdapter;

    /** @var int */
    private $processed;

    /**
     * By default we will use the copy function for copying files
     * @var bool
     */
    private $useCopyFunction = true;

    /**
     * @todo Inject PhpAdapter and make changes to all instance of Filesystem accordingly :)
     */
    public function __construct()
    {
        parent::__construct();
        $this->phpAdapter = new PhpAdapter();
    }

    /**
     * @return string[]
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
    public function safePath(string $fullPath)
    {
        $safePath = realpath(dirname($fullPath));
        if (!$safePath) {
            return null;
        }

        $safePath = ABSPATH . str_replace(ABSPATH, '', $safePath);
        $safePath .= DIRECTORY_SEPARATOR . basename($fullPath);
        return $safePath;
    }

    /**
     * Move content from one path to another
     * This is better than $this->rename() method as this use custom fileiterator and $this->delete()
     * @param string $source
     * @param string $target
     *
     * @return bool Whether the move was successful or not.
     */
    public function move(string $source, string $target): bool
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
            /** @var \RecursiveDirectoryIterator $iterator */
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

            if (!$result || !$this->phpAdapter->isCallable($this->shouldStop)) {
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
    public function renameDirect(string $source, string $target): bool
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
     * This function moves file without using php's function rename, since rename does not work in all cases.
     *
     * @see https://github.com/wp-staging/wp-staging-pro/pull/2558
     *
     * @param string $source
     * @param string $target
     *
     * @return bool — true on success or false on failure.
     */
    public function moveFileOrDir(string $source, string $dest): bool
    {
        if (is_dir($source)) {
            return $this->moveDirRecursively($source, $dest);
        }

        try {
            if (!@copy($source, $dest)) {
                return false;
            }

            @unlink($source);
            return true;
        } catch (\Throwable $th) {
            debug_log("Failed to copy $source in moveFileOrDir. Error message: " . $th->getMessage());
            return false;
        }
    }

    /**
     * @param string $source
     * @param string $target
     *
     * @return bool — true on success or false on failure.
     */
    private function moveDirRecursively(string $source, string $dest): bool
    {
        if (!is_dir($source)) {
            debug_log("moveDirRecursively() - Is no dir: $source.");
            return false;
        }

        if (!$this->mkdir($dest)) {
            return false;
        }

        /** @var \RecursiveDirectoryIterator $iterator */
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $copySucceed = true;
        foreach ($iterator as $item) {
            // Skip if a link
            if ($item->isLink()) {
                continue;
            }

            if ($item->isDir() && !$this->mkdir(trailingslashit($dest) . $iterator->getSubPathname())) {
                $copySucceed = false;
                continue;
            }

            if (!$item->isFile()) {
                continue;
            }

            if (!$this->moveFileOrDir($item->getPathname(), trailingslashit($dest) . $iterator->getSubPathname())) {
                $copySucceed = false;
            }
        }

        if ($copySucceed && !$this->delete($source, true)) {
            debug_log("moveDirRecursively() - Failed to delete $source.");
        }

        return true;
    }

    /**
     * @param string|null $path The path to the new folder, or null to use FileSystem's path.
     * @param bool $detectDirectoryListing Whether to detect directory listing for notice.
     *
     * @return string Path to newly created folder, or empty string if couldn't create it.
     */
    public function mkdir($path, bool $detectDirectoryListing = false): string
    {
        $path = $this->findPath($path);

        /**
         * For UNC Paths
         * If the path starts with two forward slashes, we need to convert them to backward slashes to allow directory creation
         * examples
         * //server/path/to/dir -> \\server/path/to/dir
         * //server\path\to\dir -> \\server\path\to\dir
         */
        if (strpos($path, '//') === 0) {
            $path = '\\\\' . substr($path, 2);
        }

        set_error_handler([$this, 'handleMkdirError']);
        $result = $this->recursiveCreateDirectory($path);
        restore_error_handler();
        if (!$result) {
            \WPStaging\functions\debug_log("Failed to create directory $path");

            return '';
        }

        if (!$detectDirectoryListing) {
            return trailingslashit($path);
        }

        /** @var DirectoryListing $directoryListing */
        $directoryListing = WPStaging::getInstance()->getContainer()->get(DirectoryListing::class);
        try {
            $directoryListing->preventDirectoryListing($path);
        } catch (\Exception $e) {
            /**
             * Enqueue this error. All enqueued errors will be shown as a single notice.
             *
             * @see \WPStaging\Framework\Notices\Notices::showDirectoryListingWarningNotice
             */
            WPStaging::getInstance()->getContainer()->pushToArray(Notices::$directoryListingErrors, $e->getMessage());
        }

        return trailingslashit($path);
    }

    /**
     * The new copy method which works for files, links and directories
     *
     * @param string $source
     * @param string $target
     * @return bool
     */
    public function copy(string $source, string $target): bool
    {
        // if $source is link or file, copy it and stop execution
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
            /** @var \RecursiveDirectoryIterator $iterator */
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

            if (!$result || !$this->phpAdapter->isCallable($this->shouldStop)) {
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
    public function isEmptyDir(string $dir): bool
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
     * @param string|null $path
     * @param bool   $deleteSelf Whether to delete the target folder after deleting it's contents.
     * @param bool   $throw Whether to throw an exception if the directory could not be deleted.
     * @throws FilesystemExceptions Only if $throw is true.
     *
     * @return bool True if target was completely deleted, false if file not deleted or folder still have contents.
     */
    public function delete($path = null, bool $deleteSelf = true, bool $throw = false): bool
    {
        $path = $this->findPath($path);

        if ($path === ABSPATH) {
            $this->log('You can not delete WP Root directory');
            throw new RuntimeException('You can not delete WP Root directory');
        }

        clearstatcache();

        // if $path is link or file, delete it and stop execution
        if (is_link($path) || is_file($path)) {
            if (!@unlink($path)) {
                $this->log('Permission Error: Can not delete file ' . $path);
                return false;
            }

            $this->processed++;
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

            $this->processed++;
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
                $this->processed++;
            } catch (RuntimeException $e) {
                if ($this->arePermissionExceptionsBypassed() !== true) {
                    $this->setRecursive($originalIsRecursive);

                    throw $e;
                }
            }

            if (!$result || !$this->phpAdapter->isCallable($this->shouldStop)) {
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
            if (!@rmdir($path)) {
                $this->log('Permission Error: Can not delete directory ' . $path);
            }
        }

        $this->setRecursive($originalIsRecursive);
        $this->processed++;
        return true;
    }

    /**
     * @param string $file full path + filename
     * @param string[] $excludedFiles List of filenames. Can be wildcard pattern like data.php, data*.php, *.php, .php
     * @param bool $returnPattern If true, returns the pattern that matched the filename.
     *
     * @return bool|string false if not excluded, true if excluded and $returnPattern is false, string if $returnPattern is true
     */
    public function isFilenameExcluded(string $file, array $excludedFiles, bool $returnPattern = false)
    {
        $filename = basename($file);

        // Regular filenames
        if (in_array($filename, $excludedFiles, true)) {
            if ($returnPattern) {
                return $filename;
            }

            return true;
        }

        // Wildcards
        foreach ($excludedFiles as $pattern) {
            if ($this->fnmatch($pattern, $filename)) {
                if ($returnPattern) {
                    return $pattern;
                }

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
     * @param string[] $options options for matching. Valid options are:
     *
     * - caseSensitive: bool, whether pattern should be case sensitive. Defaults to `true`.
     * - escape: bool, whether backslash escaping is enabled. Defaults to `true`.
     * - filePath: bool, whether slashes in string only matches slashes in the given pattern. Defaults to `false`.
     *
     * @return bool whether the string matches pattern or not.
     */
    protected function fnmatch(string $pattern, string $string, array $options = []): bool
    {
        if ($pattern === '*' && empty($options['filePath'])) {
            return true;
        }

        $replacements = [
            '\\\\\\\\' => '\\\\',
            '\\\\\\*'  => '[*]',
            '\\\\\\?'  => '[?]',
            '\*'       => '.*',
            '\?'       => '.',
            '\[\!'     => '[^',
            '\['       => '[',
            '\]'       => ']',
            '\-'       => '-',
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
     * @param string[] $paths
     * @return bool
     */
    public function deletePaths(array $paths): bool
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
     * @param string|null $path
     * @return string|null
     */
    public function findPath($path)
    {
        return $path ?: $this->path;
    }

    /**
     * @return bool|null
     */
    public function arePermissionExceptionsBypassed()
    {
        return $this->bypassPermissionExceptions;
    }

    /**
     * @param bool|null $flag
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
    public function setShouldStop($shouldStop = null)
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
     * @return string[]
     */
    public function getFileNames(): array
    {
        return $this->fileNames ?: [];
    }

    /**
     * @param string[] $fileNames
     * @return self
     */
    public function setFileNames(array $fileNames)
    {
        $this->fileNames = $fileNames;
        return $this;
    }

    /**
     * @param string $fileName
     * @return self
     */
    public function addFileName(string $fileName)
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
    protected function deleteItem(SplFileInfo $item): bool
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
     * @return bool
     */
    protected function removeSymlink(string $path): bool
    {
        // remove symlink using rmdir if OS is windows
        if (PHP_SHLIB_SUFFIX === 'dll') {
            return @rmdir($path);
        }

        return @unlink($path);
    }

    /**
     * @param string $string
     */
    protected function log(string $string)
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->warning($string);
            return;
        }

        $this->logs[] = $string;
    }

    /**
     * Create or update a file with content
     *
     * @param string $path Path to the file
     * @param string $content Content of the file
     * @param string $mode
     * @return bool
     */
    public function create(string $path, string $content, string $mode = 'wb'): bool
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
        if (( $handle = @fopen($path, $mode) ) !== false) {
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
     * @param  array|string $content Content of the file.
     * @return bool
     */
    public function createWithMarkers(string $path, string $marker, $content): bool
    {
        return @insert_with_markers($path, $marker, $content);
    }

    /**
     * Normalize Path if
     * 1. On Windows OS
     * 2. OR it doesn't contains backslash
     *
     * Normalize work under normal condition but on Linux OS if a folder contains backslash \,
     * it will be converted into forward slash thus make the folder path inaccessible.
     * So on linux if the path contains backslash we don't normalize it.
     * On Windows OS we always return the normalize path for readability.
     *
     * @param string $path
     * @param bool $addTrailingslash
     *
     * @return string
     */
    public function maybeNormalizePath(string $path, bool $addTrailingslash = false): string
    {
        if ($this->isWindowsOs() || !strpos($path, '\\')) {
            return $this->normalizePath($path, $addTrailingslash);
        }

        return $addTrailingslash ? $this->trailingSlashit($path) : $path;
    }

    /**
     * Normalize a filesystem path.
     *
     * On windows systems, replaces backslashes with forward slashes
     * and forces upper-case drive letters.
     * Allows two leading slashes for Windows network shares
     *
     * @param string $path
     * @param bool   $addTrailingslash. Default false
     *
     * @return string
     */
    public function normalizePath(string $path, bool $addTrailingslash = false): string
    {
        /**
         * For UNC Paths
         * If the path starts with two backslashes, we need to escape them, to make a valid UNC path
         * No need to escape if already escaped.
         * \\server\path\to\file is treated as \server\path\to\file
         * The below code make sure \\server\path\to\file is changed to \\\\server\path\to\file so that it is treated as \\server\path\to\file
         * No escaping is done if path already starts with four backslashes \\\\
         */
        if (strpos($path, '\\') === 0 && strpos($path, '\\\\') !== 0) {
            $path = '\\' . $path;
        }

        if ($addTrailingslash) {
            $path = trim($path);
            $path = wp_normalize_path($path);
            $path = trailingslashit($path);

            return $path;
        }

        return wp_normalize_path($path);
    }

    /**
     * @param int $errno
     * @param string $errstr
     * @return void
     */
    public function handleMkdirError(int $errno, string $errstr)
    {
        $this->logs[] = "Unable to create directory. Reason: " . $errstr;
    }

    /**
     * Build a temporary path for plugins and themes
     * Add temporary prefix to plugin or theme main dir during the file copying push process.
     * E.g. wpstg-tmp-plugins/yoast or wpstg-tmp-themes/avada
     * @param string $fullPath
     * @return string
     */
    public function tmpDestinationPath(string $fullPath): string
    {
        return preg_replace(
            '#wp-content/(plugins|themes)/([A-Za-z0-9-_]+)#',
            'wp-content/' . Copier::PREFIX_TEMP . '$1/$2',
            $fullPath
        );
    }

    /**
     * @param string $filePath The filename to check.
     * Also compatible with Windows Network files
     * @see https://bugs.php.net/bug.php?id=73543
     * @see https://bugs.php.net/bug.php?id=69834
     *
     * @return bool Whether the file exists and is readable.
     */
    public function isReadableFile(string $filePath): bool
    {
        if (is_readable($filePath)) {
            return true;
        }

        if (!file_exists($filePath) || !is_file($filePath)) {
            return false;
        }

        // On window or samba network file is_readable sometimes return false
        // Even though files can be accessible with file_get_contents or fopen
        // see links in the method docblock for more detail.
        try {
            $fileHandle = fopen($filePath, 'rb');
            if (!is_resource($fileHandle)) {
                return false;
            }

            if (fclose($fileHandle)) {
                return true;
            }
        } catch (Exception $ex) {
            debug_log($ex->getMessage());
        }

        return false;
    }

    /**
     * @param int $processed
     * @return void
     */
    public function setProcessedCount(int $processed = 0)
    {
        $this->processed = $processed;
    }

    /**
     * @return int
     */
    public function getProcessedCount(): int
    {
        return $this->processed;
    }

    /**
     * @param string $path Path to search files for
     * @return array An array of files found in a directory,
     *               where the index is the path relative to the directory, and the value is the absolute path to the file.
     * @example [
     *              'debug.log' => '/var/www/single/wp-content/uploads/wp-staging/tmp/restore/655bb61a54f5/wpstg_c_/debug.log',
     *              'custom-folder/custom-file.png' => '/var/www/single/wp-content/uploads/wp-staging/tmp/restore/655bb61a54f5/wpstg_c_/custom-folder/custom-file.png',
     *          ]
     *
     */
    public function findFilesInDir(string $path): array
    {
        $path = $this->normalizePath($path);

        $it = @new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $it = new \RecursiveIteratorIterator($it);

        $files = [];

        /** @var \SplFileInfo $item */
        foreach ($it as $item) {
            // Early bail: We don't want dots, links or anything that is not a file.
            if (!$item->isFile() || $item->isLink()) {
                continue;
            }

            $pathName = $this->normalizePath($item->getPathname());

            $relativePath = str_replace($path, '', $pathName);

            $files[$relativePath] = $pathName;
        }

        return $files;
    }

    /**
     * This extends and improves WP's trailingslashit().
     * If directory path ends with backlash as it is supported by linux, it adds a trailing slash to escape it properly e.g '/var/www/folder\\' => '/var/www/folder\/'
     * WP's trailingslashit() would return the wrong directory path '/var/www/folder/'
     * @param string $path
     * @return string
     */
    public function trailingSlashit(string $path): string
    {
        if ($this->isWindowsOs()) {
            return trailingslashit($path);
        }

        if ($path[strlen($path) - 1] === '\\') {
            return $path . '/';
        }

        return trailingslashit($path);
    }

    /**
     * Wrapper around WPStaging::isWindowsOs to make it easily mockable
     *
     * @return bool
     */
    protected function isWindowsOs(): bool
    {
        return WPStaging::isWindowsOs();
    }

    /**
     * wp_mkdir_p doesn't work properly if the ABSPATH has custom/wrong permissions,
     * which leads to permission denied error on accessing the created directory
     * @see https://github.com/wp-staging/wp-staging-pro/issues/2925
     * So we will try to create the directory with 0775 permission and recursively create all parent which doesn't exist
     *
     * @param string $directory
     * @return bool
     */
    protected function recursiveCreateDirectory(string $directory): bool
    {
        if (is_dir($directory)) {
            return true;
        }

        return mkdir($directory, 0775, true);
    }

    /**
     * Check if file has *.wpstg or *.wpstg.sql extension
     * This does not check if it's a valid backup file; does not check the content.
     * @param string $backupFile
     * @return bool
     */
    public function isWpstgBackupFile(string $backupFile): bool
    {
        if (empty($backupFile)) {
            return false;
        }

        $backupFile = basename($backupFile);
        $backupFile = strtolower($backupFile);

        foreach (self::BACKUP_FILE_EXTENSION as $extension) {
            if ($extension === substr($backupFile, -strlen($extension))) {
                return true;
            }
        }

        return false;
    }

    /**
     * A wrapper for copy function for the fix #4144
     * @see https://github.com/wp-staging/wp-staging-pro/issues/4144
     * @param string $source
     * @param string $destination
     * @throws RuntimeException
     * @return true
     */
    public function copyFile(string $source, string $destination): bool
    {
        if ($this->useCopyFunction && @copy($source, $destination)) {
            return true;
        }

        // Copy function fails, lets try with file_get_contents and file_put_contents
        // But first lets get the original error during copying function
        if ($this->useCopyFunction) {
            $errorObject  = error_get_last();
            $errorMessage = $errorObject['message'] ?? '';
        }

        $result = file_put_contents($destination, file_get_contents($source));
        // If file_put_contents and file_get_contents fails,
        if ($result === false) {
            throw new RuntimeException("Failed to copy file to destination: {$source} -> {$destination}. Error: {$errorMessage}");
        }

        // If file_put_contents and file_get_contents works, let use for the rest of request
        if ($this->useCopyFunction) {
            $this->useCopyFunction = false;
            debug_log("Copy function failed with error: {$errorMessage}. Using file_get_contents and file_put_contents instead.");
        }

        return true;
    }
}
