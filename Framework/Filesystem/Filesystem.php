<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Vendor\Psr\Log\LoggerInterface;
use RuntimeException;
use WPStaging\Vendor\Symfony\Component\Filesystem\Exception\IOException;
use WPStaging\Vendor\Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use WPStaging\Vendor\Symfony\Component\Finder\Finder;

class Filesystem extends FilterableDirectoryIterator
{
    /** @var string|null */
    private $path;

    /** @var callable|null */
    private $shouldStop;

    /** @var string[]|array|null */
    private $notPath;

    /** @var int|null */
    private $depth;

    /** @var string[]|array|null */
    private $fileNames;

    /** @var LoggerInterface|null */
    private $logger;

    /**
     * @param string|null $directory
     * @return Finder
     */
    public function findFiles($directory = null)
    {
        $finder = (new Finder)
            ->ignoreUnreadableDirs()
            ->files()
            ->in($this->findPath($directory))
        ;

        if ($this->depth !== null) {
            $finder = $finder->depth((string) $this->depth);
        }

        foreach ($this->getNotPath() as $exclude) {
            $finder->notPath($exclude);
        }

        foreach ($this->getFileNames() as $fileName) {
            $finder->name($fileName);
        }

        $finder_has_results = count($finder) > 0;

        if (!$finder_has_results) {
            return null;
        }

        return $finder;
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
     * Checks given file or directory exists
     * @param string $fullPath
     * @return bool
     */
    public function exists($fullPath)
    {
        return (new SymfonyFilesystem)->exists($fullPath);
    }

    /**
     * @param string $newPath
     * @return bool
     */
    public function rename($newPath)
    {
        // Target doesn't exist, just use rename
        if (!$this->exists($newPath)) {
            return rename($this->getPath(), $newPath);
        }

        // Get all files and dirs
        $finder = (new Finder)->ignoreUnreadableDirs()->in($this->getPath());
        if ($this->getNotPath()) {
            foreach ($this->getNotPath() as $notPath) {
                $finder->notPath($notPath);
            }
        }

        $basePath = trailingslashit($newPath);
        foreach ($finder as $item) {
            if (!$this->exists($item->getPathname())) {
                $this->log('Failed to move directory. Directory does not exists' . $item->getPathname());
                continue;
            }

            // RelativePathname is relative to $this->path
            $destination = $basePath . $item->getRelativePathname();

            // It is not a directory, use built-in rename instead
            if (!$item->isDir()) {
                $this->renameDirect($item->getPathname(), $destination);
                continue;
            }

            // If it doesn't exist, use built-in rename instead
            if (!$this->exists($destination)) {
                $this->renameDirect($item->getPathname(), $destination);
                continue;
            }

            if ($this->shouldStop) {
                return false;
            }
        }

        // Due to rename, all files should be deleted so just empty directories left, make sure all of them are deleted.
        // Due to setting shouldStop, this might return false till everything is deleted.
        // This might not be just empty dirs as we can exclude some directories using notPath()
        return $this->delete(null, false);
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
        if (!$this->exists($dir)) {
            $this->mkdir($dir);
        }

        $renamed = @rename($source, $target);

        if (!$renamed) {
            $this->log(sprintf('Failed to move %s to %s', $source, $target));
        }

        return $renamed;
    }

    /**
     * @param string|null $path
     *
     * @return string
     */
    public function mkdir($path)
    {
        $path = $this->findPath($path);
        if (!wp_mkdir_p($path)) {
            throw new IOException('Failed to create directory ' . $path);
        }

        return trailingslashit($path);
    }

    public function copy($source, $destination)
    {
        $fs = new SymfonyFilesystem;
        // TODO perhaps use stream_set_chunk_size()?
        $fs->copy($source, $destination);
        return $fs->exists($destination);
    }

    /**
     * @param string
     * @return bool
     */
    public function isWritableDir($fullPath)
    {
        return is_dir($fullPath) && is_writable($fullPath);
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
     * Delete single file or entire folder including all subfolders and containing files
     * @param null $path
     * @param bool $isUseNotPath
     * @return bool
     * @todo Make it deprecated and switch it with $this->>deleteNew() as it is more performant and unit tested
     */
    public function delete($path = null, $isUseNotPath = true)
    {
        $path = $this->findPath($path);
        if (!$path) {
            $this->log('You need to define path to delete');
            throw new RuntimeException('You need to define path to delete');
        }

        if ($path === ABSPATH) {
            $this->log('You can not delete WP Root directory');
            throw new RuntimeException('You can not delete WP Root directory');
        }

        if (is_link($path) || is_file($path)) {
            return unlink($path);
        }

        if (!is_dir($path)) {
            return true;
        }

        $iterator = (new Finder)
            ->ignoreUnreadableDirs()
            ->ignoreDotFiles(false)
            ->in($this->findPath($path))
        ;

        if ($this->getShouldStop()) {
            $iterator->depth('0');
        }

        if ($isUseNotPath) {
            foreach ($this->getNotPath() as $exclude) {
                $iterator->notPath($exclude);
            }
        }

        foreach ($this->getFileNames() as $fileName) {
            $iterator->name($fileName);
        }

        $iteratorHasResults = count($iterator) > 0;

        if (is_dir($path) && !$iteratorHasResults && $this->isEmptyDir($path)) {
            return rmdir($path);
        }

        foreach ($iterator as $file) {
            $this->delete($file->getPathname(), $isUseNotPath);
            if ($this->shouldStop) {
                return false;
            }
        }

        if (is_dir($path)){
            return rmdir($path);
        }
    }

    /**
     * Delete a whole directory including all sub directories or a file.
     * A new faster method than $this->delete()
     * $this->delete() uses the finder method which seems to be very slow compared to native RecursiveIteratorIterator!
     * @todo replace $this->>delete() with this method if this can fulfill all requirements
     *
     * @param string $path
     * @param bool $deleteSelf making it optional to delete the parent itself, useful during file and dir exclusion
     * @return bool True if folder or file is deleted or file is empty ($deleteSelf = false); Return False if folder is not empty and execution should be continued
     */
    public function deleteNew($path, $deleteSelf = true)
    {
        // if $path is link or file, delete it and stop execution
        if(is_link($path) || is_file($path))
        {
            if (!unlink($path)){
                $this->log('Permission Error: Can not delete file ' . $path);
                return false;
            }
            return true;
        }


        $this->setDirectory($path);
        $iterator = null;
        try {
            $iterator = $this->setIteratorMode(\RecursiveIteratorIterator::CHILD_FIRST)->get();
        } catch (FilesystemExceptions $e) {
            $this->log('Permission Error: Can not create recursive iterator for ' . $path);
            return false;
        }

        foreach ($iterator as $item) {
            $result = $this->deleteItem($item);
            if (!$result || !is_callable($this->shouldStop)) {
                continue;
            }

            if (call_user_func($this->shouldStop)) {
                return false;
            }
        }

        // Don't delete the parent main dir itself and finish execution
        if (!$deleteSelf) {
            return true;
        }

        // Folder is not empty. Return false and continue execution if requested
        if (!$this->isEmptyDir($path)) {
            return false;
        }
        
        // Delete the empty directory itself and finish execution
        if (is_dir($path)){
            if (!rmdir($path)){
                $this->log('Permission Error: Can not delete directory ' . $path);
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
        return $path?: $this->path;
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
     * @return array
     */
    public function getNotPath()
    {
        return $this->notPath?: [];
    }

    /**
     * @param array $notPath
     * @return self
     */
    public function setNotPath(array $notPath = [])
    {
        $this->notPath = $notPath;
        return $this;
    }

    /**
     * @param string $notPath
     * @return self
     */
    public function addNotPath($notPath)
    {
        $this->notPath[] = $notPath;
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
        return $this->fileNames?: [];
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
     * @param \SplFileInfo $item
     * @return bool
     */
    protected function deleteItem($item)
    {
        $path = $item->getPathname();
        // Checks whether that file or directory exists
        if (!file_exists($path)) {
            return true;
        }

        if ($item->isLink()) {
            if(!unlink($path)) {
                $this->log('Permission Error: Can not delete link ' . $path);
                throw new RuntimeException('Permission Error: Can not delete link ' . $path);
            }
        } elseif ($item->isDir()) {
            if (!$this->isEmptyDir($path)) {
                return false;
            }

            if (!@rmdir($path)) {
                $this->log('Permission Error: Can not delete folder ' . $path);
                throw new RuntimeException('Permission Error: Can not delete folder ' . $path);
            }
        } elseif (!$item->isFile()) {
            return false;
        } elseif (!unlink($path)) {
            $this->log('Permission Error: Can not delete file ' . $path);
            throw new RuntimeException('Permission Error: Can not delete file ' . $path);
        }

        return true;
    }

    /**
     * @param $string
     */
    protected function log($string){
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->warning($string);
        }
    }


}
