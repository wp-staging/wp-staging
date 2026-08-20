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







class Filesystem extends FilterableDirectoryIterator
{
 
    const BACKUP_FILE_EXTENSION = ['.wpstg', '.wpstg.sql'];

 
    private $path;

 
    private $shouldStop;

 
    private $depth;

 
    private $fileNames;

 
    private $logger;

 
    private $bypassPermissionExceptions;

 
    private $logs = [];

 
    private $phpAdapter;

 
    private $processed;





    private $useCopyFunction = true;




    public function __construct()
    {
        parent::__construct();
        $this->phpAdapter = new PhpAdapter();
    }




    public function getLogs()
    {
        return $this->logs;
    }






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









    public function move(string $source, string $target): bool
    {
 
        if (is_link($source) || is_file($source)) {
            return $this->renameDirect($source, $target);
        }

 
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
 
            if ($item->isDir()) {
                $result = wp_mkdir_p($destination) && @rmdir($item->getPathname());
            } else { 
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







    private function moveDirRecursively(string $source, string $dest): bool
    {
        if (!is_dir($source)) {
            debug_log("moveDirRecursively() - Is no dir: $source.");
            return false;
        }

        if (!$this->mkdir($dest)) {
            return false;
        }

 
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $copySucceed = true;
        foreach ($iterator as $item) {
 
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







    public function mkdir($path, bool $detectDirectoryListing = false): string
    {
        $path = $this->findPath($path);








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

 
        $directoryListing = WPStaging::getInstance()->getContainer()->get(DirectoryListing::class);
        try {
            $directoryListing->preventDirectoryListing($path);
        } catch (\Exception $e) {





            WPStaging::getInstance()->getContainer()->pushToArray(Notices::$directoryListingErrors, $e->getMessage());
        }

        return trailingslashit($path);
    }








    public function copy(string $source, string $target): bool
    {
 
        if (is_link($source) || is_file($source)) {
            $this->mkdir(dirname($target));
            return copy($source, $target);
        }

 
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
 
            if ($item->isDir()) {
                $result = wp_mkdir_p($destination);
            } else { 
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






    public function isEmptyDir(string $dir): bool
    {
        if (is_dir($dir)) {
            $iterator = new \FilesystemIterator($dir);
            return !$iterator->valid();
        }

        return true;
    }













    public function delete($path = null, bool $deleteSelf = true, bool $throw = false): bool
    {
        $path = $this->findPath($path);

        if ($path === ABSPATH) {
            $this->log('You can not delete WP Root directory');
            throw new RuntimeException('You can not delete WP Root directory');
        }

        clearstatcache();

 
        if (is_link($path) || is_file($path)) {
            if (!@unlink($path)) {
                $this->log('Permission Error: Can not delete file ' . $path);
                return false;
            }

            $this->processed++;
            return true;
        }

 
        if (!is_dir($path)) {
            return true;
        }

 
        if (is_dir($path) && $this->isEmptyDir($path) && $deleteSelf) {
            if (!@rmdir($path)) {
                $this->log('Permission Error: Can not delete directory ' . $path);
                return false;
            }

            $this->processed++;
            return true;
        }

 
        if (is_dir($path) && $this->isEmptyDir($path) && !$deleteSelf) {
            return true;
        }

        $this->setDirectory($path);
        $originalIsRecursive = (bool)$this->isIteratorRecursive();
        try {






            if ($this->isIteratorRecursive() === null) {
                $this->setRecursive();
            }

            $iterator = $this->setIteratorMode(\RecursiveIteratorIterator::CHILD_FIRST)->get();
        } catch (FilesystemExceptions $e) {
            $this->log('Permission Error: Can not create recursive iterator for ' . $path);
            if ($throw) {
                $this->setRecursive($originalIsRecursive);
 
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

 
        if (!$deleteSelf || !$this->isEmptyDir($path)) {
            $this->setRecursive($originalIsRecursive);
            return true;
        }

 
        if (is_dir($path)) {
            if (!@rmdir($path)) {
                $this->log('Permission Error: Can not delete directory ' . $path);
            }
        }

        $this->setRecursive($originalIsRecursive);
        $this->processed++;
        return true;
    }








    public function isFilenameExcluded(string $file, array $excludedFiles, bool $returnPattern = false)
    {
        $filename = basename($file);

 
        if (in_array($filename, $excludedFiles, true)) {
            if ($returnPattern) {
                return $filename;
            }

            return true;
        }

 
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





    public function deletePaths(array $paths): bool
    {
        foreach ($paths as $path) {
 
 
            if (is_dir($path) && $this->isEmptyDir($path)) {
                if (!@rmdir($path)) {
                    $this->log('Permission Error: Can not delete directory ' . $path);
                    throw new RuntimeException('Permission Error: Can not delete directory ' . $path);
                }

                continue;
            }

 
            if (!$this->delete($path, false)) {
                return false;
            }
        }

        return true;
    }





    public function findPath($path)
    {
        return $path ?: $this->path;
    }




    public function arePermissionExceptionsBypassed()
    {
        return $this->bypassPermissionExceptions;
    }





    public function shouldPermissionExceptionsBypass($flag)
    {
        $this->bypassPermissionExceptions = $flag;
        return $this;
    }




    public function getPath()
    {
        return $this->path;
    }





    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }




    public function getShouldStop()
    {
        return $this->shouldStop;
    }





    public function setShouldStop($shouldStop = null)
    {
        $this->shouldStop = $shouldStop;
        return $this;
    }




    public function getDepth()
    {
        return $this->depth;
    }





    public function setDepth($depth)
    {
        $this->depth = $depth;
        return $this;
    }




    public function getFileNames(): array
    {
        return $this->fileNames ?: [];
    }





    public function setFileNames(array $fileNames)
    {
        $this->fileNames = $fileNames;
        return $this;
    }





    public function addFileName(string $fileName)
    {
        $this->fileNames[] = $fileName;
        return $this;
    }





    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }






    protected function deleteItem(SplFileInfo $item): bool
    {
        $path = $item->getPathname();

        if ($item->isLink()) {
            if (!$this->removeSymlink($path)) {
                $this->log('Permission Error: Can not delete link ' . $path);
                throw new RuntimeException('Permission Error: Can not delete link ' . $path);
            }
        }

 
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






    protected function removeSymlink(string $path): bool
    {
 
        if (PHP_SHLIB_SUFFIX === 'dll') {
            return @rmdir($path);
        }

        return @unlink($path);
    }




    protected function log(string $string)
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->warning($string);
            return;
        }

        $this->logs[] = $string;
    }









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









    public function createWithMarkers(string $path, string $marker, $content): bool
    {
        return @insert_with_markers($path, $marker, $content);
    }
















    public function maybeNormalizePath(string $path, bool $addTrailingslash = false): string
    {
        if ($this->isWindowsOs() || !strpos($path, '\\')) {
            return $this->normalizePath($path, $addTrailingslash);
        }

        return $addTrailingslash ? $this->trailingSlashit($path) : $path;
    }













    public function normalizePath(string $path, bool $addTrailingslash = false): string
    {








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








    public function handleMkdirError(int $errno, string $errstr, string $errfile = '', int $errline = 0): bool
    {
        $this->logs[] = "Unable to create directory. Reason: " . $errstr;
        return true; 
    }








    public function tmpDestinationPath(string $fullPath): string
    {
        return preg_replace(
            '#wp-content/(plugins|themes)/([A-Za-z0-9-_]+)#',
            'wp-content/' . Copier::PREFIX_TEMP . '$1/$2',
            $fullPath
        );
    }









    public function isReadableFile(string $filePath): bool
    {
        if (is_readable($filePath)) {
            return true;
        }

        if (!file_exists($filePath) || !is_file($filePath)) {
            return false;
        }

 
 
 
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





    public function setProcessedCount(int $processed = 0)
    {
        $this->processed = $processed;
    }




    public function getProcessedCount(): int
    {
        return $this->processed;
    }











    public function findFilesInDir(string $path): array
    {
        $path = $this->normalizePath($path);

        $it = @new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $it = new \RecursiveIteratorIterator($it);

        $files = [];

 
        foreach ($it as $item) {
 
            if (!$item->isFile() || $item->isLink()) {
                continue;
            }

            $pathName = $this->normalizePath($item->getPathname());

            $relativePath = str_replace($path, '', $pathName);

            $files[$relativePath] = $pathName;
        }

        return $files;
    }








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






    protected function isWindowsOs(): bool
    {
        return WPStaging::isWindowsOs();
    }










    protected function recursiveCreateDirectory(string $directory): bool
    {
        if (is_dir($directory)) {
            return true;
        }

        return mkdir($directory, 0775, true);
    }







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









    public function copyFile(string $source, string $destination): bool
    {
        if ($this->useCopyFunction && @copy($source, $destination)) {
            return true;
        }

 
 
        if ($this->useCopyFunction) {
            $errorObject  = error_get_last();
            $errorMessage = $errorObject['message'] ?? '';
        }

        $result = file_put_contents($destination, file_get_contents($source));
 
        if ($result === false) {
            throw new RuntimeException("Failed to copy file to destination: {$source} -> {$destination}. Error: {$errorMessage}");
        }

 
        if ($this->useCopyFunction) {
            $this->useCopyFunction = false;
            debug_log("Copy function failed with error: {$errorMessage}. Using file_get_contents and file_put_contents instead.");
        }

        return true;
    }
}
