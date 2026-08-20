<?php

namespace WPStaging\Backend\Modules\Jobs;

use Exception;
use RuntimeException;
use WPStaging\Backend\Modules\Jobs\Cleaners\WpContentCleaner;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\FilesystemExceptions;
use WPStaging\Framework\Filesystem\PathChecker;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Filesystem\Permissions;
use WPStaging\Framework\Filesystem\WpUploadsFolderSymlinker;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\Traits\EndOfLinePlaceholderTrait;










class Files extends JobExecutable
{
    use EndOfLinePlaceholderTrait;

 
    protected $strUtil;




    protected $destination;




    private $file;




    private $maxFilesPerRun;




    private $permissions;




    private $filesystem;

 
    private $pathAdapter;

 
    private $pathChecker;

 
    private $directory;




    private $rootPath;




    private $contentPath;




    private $siteInfo;





    public function initialize()
    {
        $this->permissions = new Permissions();

 
        $this->filesystem  = WPStaging::make(Filesystem::class);
 
        $this->directory   = WPStaging::make(Directory::class);
        $this->pathAdapter = WPStaging::make(PathIdentifier::class);
        $this->pathChecker = WPStaging::make(PathChecker::class);
        $this->siteInfo    = WPStaging::make(SiteInfo::class);
        $this->strUtil     = WPStaging::make(Strings::class);
        $this->rootPath    = rtrim($this->directory->getAbsPath(), '/');
        $this->contentPath = rtrim($this->directory->getWpContentDirectory(), '/');
        $this->destination = $this->filesystem->normalizePath($this->options->destinationDir);

        $filePath = $this->getFilesIndexCacheFilePath();

        if (is_file($filePath)) {
            $this->file = new FileObject($filePath, 'r');
        } elseif ($this->options->totalFiles !== 0) {
            $this->returnException(sprintf('Fatal Error: Files - File: %s is missing! Either the file was deleted after directory scanning or there is a permission issue with the file system.', $filePath));
        }

        $logStep = 0;
        if ($this->isUpdateOrResetJob()) {
            $logStep = 1;
        }

 
        if ($this->options->currentStep === $logStep) {
            $this->log("Copying files...");
        }

        $this->settings->batchSize = $this->settings->batchSize * 1000000;
        $this->maxFilesPerRun      = $this->settings->fileLimit;
    }





    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = ceil($this->options->totalFiles / $this->maxFilesPerRun);
 
 
        if ($this->isUpdateOrResetJob()) {
            $this->options->totalSteps++;
        }

 
 
        if ($this->options->totalSteps == 0) {
            $this->options->totalSteps = 1;
        }
    }







    protected function execute()
    {
 
        if ($this->isFinished()) {
            $this->symlinkUploadFolder();
            $this->log("Copying files finished");
            $this->prepareResponse(true, false);
            return false;
        }

 
        if (!$this->cleanStagingDirectory()) {
            $this->prepareResponse(false, false);
            return false;
        }

 
        if (!$this->cleanWpContent()) {
            $this->prepareResponse(false, false);
            return false;
        }

 
        if (!$this->getFilesAndCopy()) {
            $this->prepareResponse(false, false);
            return false;
        }

 
        $this->prepareResponse();

 
        return true;
    }







    private function cleanStagingDirectory()
    {
        if ($this->options->mainJob !== Job::RESET) {
            return true;
        }

        if ($this->options->currentStep !== 0) {
            return true;
        }

        if (rtrim($this->destination, '/') === rtrim(get_home_path(), '/')) {
            $this->returnException('Can not delete directory: ' . $this->destination . '. This seems to be the root directory. Exclude this directory from deleting and try again.');
            throw new \Exception('Can not delete directory: ' . $this->destination . ' This seems to be the root directory. Exclude this directory from deleting and try again.');
        }

 
        if (empty($this->destination) || !is_dir($this->destination)) {
            $this->log(sprintf(__('Fail! Destination is not a directory! %s', 'wp-staging'), $this->destination));
            return true;
        }

        if (!isset($this->options->filesResettingStatus)) {
            $this->options->filesResettingStatus = 'pending';
            $this->saveOptions();
        }

        if ($this->options->filesResettingStatus === 'finished') {
            return true;
        }

        if ($this->options->filesResettingStatus === 'pending') {
            $this->log(sprintf(__('Files: Resetting staging site: %s.', 'wp-staging'), $this->destination));
            $this->options->filesResettingStatus = 'processing';
            $this->saveOptions();
        }

        $fs = new Filesystem();
        $fs->setShouldStop([$this, 'isOverThreshold'])
            ->shouldPermissionExceptionsBypass(true)
            ->setRecursive(true);
        try {
            if (!$fs->delete($this->destination, false)) {
                foreach ($fs->getLogs() as $log) {
                    $this->log($log, Logger::TYPE_WARNING);
                }

                return false;
            }
        } catch (RuntimeException $ex) {
        }

        foreach ($fs->getLogs() as $log) {
            $this->log($log, Logger::TYPE_WARNING);
        }

        $this->options->filesResettingStatus = 'finished';
        $this->saveOptions();

        $this->prepareResponse();
        return true;
    }






    private function cleanWpContent()
    {
        if ($this->options->mainJob !== Job::UPDATE) {
            return true;
        }

        if ($this->options->currentStep !== 0) {
            return true;
        }

 
        $contentCleaner = new WpContentCleaner($this);

        $result = $contentCleaner->tryCleanWpContent($this->destination);
        foreach ($contentCleaner->getLogs() as $log) {
            if ($log['type'] === Logger::TYPE_ERROR) {
                $this->log($log['msg'], $log['type']);
                $this->returnException($log['msg']);
            } else {
                $this->debugLog($log['msg'], $log['type']);
            }
        }

        if (!$result) {
            return false;
        }

        return true;
    }






    private function getFilesAndCopy()
    {
        if ($this->options->currentStep === 0 && ($this->isUpdateOrResetJob())) {
            return true;
        }

 
        if ($this->isOverThreshold()) {
 
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }

 
        if (isset($this->options->copiedFiles) && $this->options->copiedFiles != 0) {
            $this->file->seek($this->options->copiedFiles - 1);
        }

        $this->file->setFlags(FileObject::DROP_NEW_LINE);

        for ($i = 0; $i < $this->maxFilesPerRun; $i++) {
 
 
            $this->options->copiedFiles++;

 
            if ($this->file->eof()) {
                break;
            }

            $file = trim($this->file->readAndMoveNext());

 
            if ($file === trim(Cache::PHP_HEADER)) {
                continue;
            }

            $file = $this->replacePlaceholdersWithEOLs($file);

            if (empty($file)) {
                continue;
            }

            $this->copyFile($file);
        }

        $totalFiles = $this->options->copiedFiles;
 
        if ($this->options->copiedFiles % 50 == 0) {
            $this->log("Total {$totalFiles} files processed");
        }

        return true;
    }





    private function symlinkUploadFolder()
    {
 
        if ($this->options->mainJob === Job::UPDATE) {
            return true;
        }

        if (!$this->options->uploadsSymlinked) {
            $this->log(__("Skipped symlinking WP Uploads Folder", 'wp-staging'));
            return true;
        }

        $symlinker = WPStaging::make(WpUploadsFolderSymlinker::class);
        $symlinker->setStagingPath($this->options->destinationDir);
        if ($symlinker->trySymlink()) {
            $this->log(__("Uploads Folder symlinked with the production site", 'wp-staging'));
            return true;
        }

        $this->returnException($symlinker->getError());
        return false;
    }





    private function isFinished()
    {
        return
            !$this->isRunning() ||
            $this->options->currentStep >= $this->options->totalSteps ||
            $this->options->copiedFiles >= $this->options->totalFiles;
    }





    private function copyFile($file)
    {
        $basePath  = $this->rootPath;
        $isContent = false;
        if ($this->pathAdapter->getIdentifierFromPath($file) === PathIdentifier::IDENTIFIER_WP_CONTENT) {
            $basePath  = $this->contentPath;
            $isContent = true;
        }

        $filePath  = $this->pathAdapter->transformIdentifiableToPath($file);
        $file      = $this->filesystem->maybeNormalizePath($filePath);
        $directory = dirname($file);

 
        if ($this->isDirectoryExcluded($directory)) {
            $this->debugLog("Skipping directory by rule: {$file}", Logger::TYPE_INFO);
            return false;
        }

 
        if ($this->isFileExcluded($file)) {
            $this->debugLog("Skipping file by rule: {$file}", Logger::TYPE_INFO);
            return false;
        }

 
        if ($this->isFileExcludedFullPath($file)) {
            $this->options->tmpExcludedFilesFullPath[] = $file;
            $this->debugLog("Skipping file by rule: {$file}", Logger::TYPE_INFO);
            return false;
        }

 
        if (!is_file($file) && !is_dir($file)) {
            $this->log("File doesn't exist {$file}", Logger::TYPE_WARNING);
            return true;
        }

 
        if (!$this->filesystem->isReadableFile($file)) {
            $this->log("Can't read file {$file}", Logger::TYPE_WARNING);
            return true;
        }

 
        $fileSize = filesize($file);

 
        if ($fileSize >= $this->settings->maxFileSize * 1000000) {
            $this->log("Skipping big file: {$file}", Logger::TYPE_INFO);
            return false;
        }

 
        if (($destination = $this->getDestination($file, $basePath, $isContent)) === false) {
            $this->log("Can't get the destination of {$file}", Logger::TYPE_WARNING);
            return false;
        }

        if ($file === $destination) {
            $this->log("Skipping file copying: Destination same as source: {$destination}", Logger::TYPE_INFO);
            return false;
        }

 
        if ($fileSize >= $this->settings->batchSize) {
            return $this->copyBig($file, $destination, $this->settings->batchSize);
        }

 
        try {
            if (is_dir($file)) {
                $this->filesystem->copy($file, $destination);
 
                @chmod($destination, $this->permissions->getDirectoryOctal());
            } else {
                $this->filesystem->copyFile($file, $destination);
 
                @chmod($destination, $this->permissions->getFilePermission($destination));
            }
        } catch (RuntimeException $ex) {
            $this->log('Files: ' . $ex->getMessage(), Logger::TYPE_ERROR);
            return false;
        }

        $this->setDirPermissions($destination);

        return true;
    }









    private function setDirPermissions($file)
    {
        $dir = dirname($file);
        if (is_dir($dir)) {
            @chmod($dir, $this->permissions->getDirectoryOctal());
        }

        return false;
    }









    protected function getDestination($file, $basePath, $isContent = false)
    {
        $file            = $this->filesystem->normalizePath($file);
        $relativePath    = $this->strUtil->replaceStartWith($basePath, '', $file);
        $destinationPath = $this->destination . $relativePath;

        if ($isContent && $this->shouldUseDefaultWpContentPath()) {
            $destinationPath = $this->destination . 'wp-content/' . $relativePath;
        } elseif ($isContent) {
            $absPath         = $this->filesystem->normalizePath(ABSPATH);
            $destinationPath = $this->strUtil->replaceStartWith($absPath, $this->destination, $file);
        }

        $destinationDirectory = dirname($destinationPath);

        $isDirectoryNotCreated = !is_dir($destinationDirectory) && !$this->filesystem->mkdir($destinationDirectory) && !is_dir($destinationDirectory);
        if ($isDirectoryNotCreated) {
            $this->log("Files: Can not create directory {$destinationDirectory}." . $this->filesystem->getLogs()[0], Logger::TYPE_ERROR);
            return false;
        }

        return $this->filesystem->normalizePath($destinationPath);
    }




    protected function shouldUseDefaultWpContentPath(): bool
    {
 
        return $this->siteInfo->isWpContentOutsideAbspath();
    }








    private function copyBig($src, $dst, $bufferSize)
    {
        $src  = fopen($src, 'rb');
        $dest = fopen($dst, 'wb');

        if (!$src || !$dest) {
            return false;
        }

 
        while (!feof($src)) {
            if (fwrite($dest, fread($src, $bufferSize)) === false) {
                $error = true;
            }
        }
 
        if (isset($error) && ($error === true)) {
            while (!feof($src)) {
                if (stream_copy_to_stream($src, $dest, 1024) === false) {
                    $this->log("Can not copy file; {$src} -> {$dest}");
                    fclose($src);
                    fclose($dest);
                    return false;
                }
            }
        }

 
        fclose($src);
        fclose($dest);
        return true;
    }







    private function isFileExcluded($file)
    {
        $excludedFiles = (array)$this->options->excludedFiles;

 
 
        if ($this->isIdenticalHostname() === false && $this->options->mainJob !== Job::UPDATE) {
            $excludedFiles = \array_diff(
                $excludedFiles,
                ["web.config", ".htaccess"]
            );
        }

        $isExcluded = $this->filesystem->isFilenameExcluded($file, $excludedFiles, true);
        if ($isExcluded !== false) {
            $this->options->tmpExcludedFilesFullPath[] = $isExcluded;
            return true;
        }

 
 
        if (
            isset($this->options->mainJob) && $this->options->mainJob === Job::UPDATE
            && stripos(strrev($file), strrev("wp-config.php")) === 0
        ) {
            return true;
        }

        return false;
    }






    private function isIdenticalHostname()
    {
 
        $siteurl            = get_site_url();
        $url                = parse_url($siteurl);
        $productionHostname = $url['host'];

 
        $cloneUrl       = empty($this->options->cloneHostname) ? $url : parse_url($this->options->cloneHostname);
        $targetHostname = $cloneUrl['host'];

 
 
        if (wpstg_starts_with($productionHostname, $targetHostname)) {
            return true;
        }

        return false;
    }







    private function isFileExcludedFullPath($file)
    {
 
        foreach ($this->options->excludedFilesFullPath as $excludedFile) {
 
            try {
                $excludedFileFullPath = $this->pathAdapter->transformIdentifiableToPath($excludedFile);
            } catch (Exception $ex) {
                $excludedFileFullPath = $excludedFile;
            }

            if ($file === $excludedFileFullPath) {
                return true;
            }
        }

        return false;
    }










    private function sanitizeDirectorySeparator($path)
    {
        return preg_replace('/[\\\\]+/', '/', $path);
    }








    private function isDirectoryExcluded($directory)
    {
        $abspath   = $this->sanitizeDirectorySeparator(ABSPATH);
        $directory = $this->sanitizeDirectorySeparator($directory);

        if ($abspath === $directory . '/') {
            return false;
        }

 
        if (strpos($directory, 'wp-staging') !== false || strpos($directory, 'wp-staging-pro') !== false) {
            return false;
        }

        if ($this->isExtraDirectory($directory)) {
            return false;
        }

        return $this->pathChecker->isPathInPathsList($directory, $this->options->excludedDirectories, false);
    }






    private function isExtraDirectory($directory)
    {
        $directory = $this->sanitizeDirectorySeparator($directory);

        foreach ($this->options->extraDirectories as $extraDirectory) {
            $extraDirectory = trim($extraDirectory);

            if (empty($extraDirectory)) {
                continue;
            }

            if (strpos($directory, $this->sanitizeDirectorySeparator($extraDirectory)) === 0) {
                return true;
            }
        }

        return false;
    }
}
