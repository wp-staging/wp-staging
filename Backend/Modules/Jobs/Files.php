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

/**
 * Class Files
 *
 * @todo Can we unify these?
 *       \WPStaging\Backend\Modules\Jobs\Files (Cloning copying files)
 *       \WPStaging\Backend\Pro\Modules\Jobs\Files (Pro push copying files)
 *
 * @package WPStaging\Backend\Modules\Jobs
 */
class Files extends JobExecutable
{
    use EndOfLinePlaceholderTrait;

    /** @var Strings */
    protected $strUtil;

    /**
     * @var string
     */
    protected $destination;

    /**
     * @var FileObject
     */
    private $file;

    /**
     * @var int
     */
    private $maxFilesPerRun;

    /**
     * @var Permissions
     */
    private $permissions;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /** @var PathIdentifier */
    private $pathAdapter;

    /** @var PathChecker */
    private $pathChecker;

    /** @var Directory */
    private $directory;

    /**
     * @var string
     */
    private $rootPath;

    /**
     * @var string
     */
    private $contentPath;

    /**
     * @var SiteInfo
     */
    private $siteInfo;

    /**
     * Initialization
     * @throws DiskNotWritableException|FilesystemExceptions
     */
    public function initialize()
    {
        $this->permissions = new Permissions();

        /** @var Filesystem */
        $this->filesystem  = WPStaging::make(Filesystem::class);
        /** @var Directory */
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

        // Informational logs
        if ($this->options->currentStep === $logStep) {
            $this->log("Copying files...");
        }

        $this->settings->batchSize = $this->settings->batchSize * 1000000;
        $this->maxFilesPerRun      = $this->settings->fileLimit;
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = ceil($this->options->totalFiles / $this->maxFilesPerRun);
        // Add an extra step for cleaning content in themes, plugins and uploads dir
        // or for deleting whole dir if resetting
        if ($this->isUpdateOrResetJob()) {
            $this->options->totalSteps++;
        }

        // Run this job at least once if no files selected
        // Strict comparison doesn't work for 0 neither '0'
        if ($this->options->totalSteps == 0) {
            $this->options->totalSteps = 1;
        }
    }

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     * @throws \Exception
     */
    protected function execute()
    {
        // Finished
        if ($this->isFinished()) {
            $this->symlinkUploadFolder();
            $this->log("Copying files finished");
            $this->prepareResponse(true, false);
            return false;
        }

        // Clean Staging Directory if job is resetting
        if (!$this->cleanStagingDirectory()) {
            $this->prepareResponse(false, false);
            return false;
        }

        // Cleaning wp-content directories: uploads, themes and plugins if selected during update
        if (!$this->cleanWpContent()) {
            $this->prepareResponse(false, false);
            return false;
        }

        // Get files and copy'em
        if (!$this->getFilesAndCopy()) {
            $this->prepareResponse(false, false);
            return false;
        }

        // Prepare and return response
        $this->prepareResponse();

        // Not finished
        return true;
    }

    /**
     * Clean staging site directory if the mainJob is resetting
     *
     * @return bool
     * @throws \Exception
     */
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

        // Finished or path does not exist
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

    /**
     * Clean WP Content According to option selected
     *
     * @return bool
     */
    private function cleanWpContent()
    {
        if ($this->options->mainJob !== Job::UPDATE) {
            return true;
        }

        if ($this->options->currentStep !== 0) {
            return true;
        }

        // @todo inject using DI if possible
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

    /**
     * Get files and copy
     * @return bool
     * @throws \Exception
     */
    private function getFilesAndCopy()
    {
        if ($this->options->currentStep === 0 && ($this->isUpdateOrResetJob())) {
            return true;
        }

        // Over limits threshold
        if ($this->isOverThreshold()) {
            // Prepare response and save current progress
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }

        // Go to last copied line and then to next one
        if (isset($this->options->copiedFiles) && $this->options->copiedFiles != 0) {
            $this->file->seek($this->options->copiedFiles - 1);
        }

        $this->file->setFlags(FileObject::DROP_NEW_LINE);

        for ($i = 0; $i < $this->maxFilesPerRun; $i++) {
            // Increment copied files
            // Do this anytime to make sure to not stuck in the same step / files
            $this->options->copiedFiles++;

            // End of file
            if ($this->file->eof()) {
                break;
            }

            $file = trim($this->file->readAndMoveNext());

            // Skip PHP header content
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
        // Log this only every 50 entries to keep the log small and to not block the rendering browser
        if ($this->options->copiedFiles % 50 == 0) {
            $this->log("Total {$totalFiles} files processed");
        }

        return true;
    }

    /**
     * Symlink the upload folder to production site if set
     * @return bool
     */
    private function symlinkUploadFolder()
    {
        // Don't symlink if the site is updated because the folder or symlink already exists
        if ($this->options->mainJob === Job::UPDATE) {
            return true;
        }

        if (!$this->options->uploadsSymlinked) {
            $this->log(__("Skipped symlinking WP Uploads Folder", 'wp-staging'));
            return true;
        }

        $symlinker = new WpUploadsFolderSymlinker($this->options->destinationDir);
        if ($symlinker->trySymlink()) {
            $this->log(__("Uploads Folder symlinked with the production site", 'wp-staging'));
            return true;
        }

        $this->returnException($symlinker->getError());
        return false;
    }

    /**
     * Checks Whether There is Any Job to Execute or Not
     * @return bool
     */
    private function isFinished()
    {
        return
            !$this->isRunning() ||
            $this->options->currentStep >= $this->options->totalSteps ||
            $this->options->copiedFiles >= $this->options->totalFiles;
    }

    /**
     * @param string $file
     * @return bool
     */
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

        // Directory is excluded
        if ($this->isDirectoryExcluded($directory)) {
            $this->debugLog("Skipping directory by rule: {$file}", Logger::TYPE_INFO);
            return false;
        }

        // File is excluded
        if ($this->isFileExcluded($file)) {
            $this->debugLog("Skipping file by rule: {$file}", Logger::TYPE_INFO);
            return false;
        }

        // Path + File is excluded
        if ($this->isFileExcludedFullPath($file)) {
            $this->options->tmpExcludedFilesFullPath[] = $file;
            $this->debugLog("Skipping file by rule: {$file}", Logger::TYPE_INFO);
            return false;
        }

        // Invalid file, skipping it as if succeeded
        if (!is_file($file)) {
            $this->log("File doesn't exist {$file}", Logger::TYPE_WARNING);
            return true;
        }

        // If file is unreadable, skip it as if succeeded
        if (!$this->filesystem->isReadableFile($file)) {
            $this->log("Can't read file {$file}", Logger::TYPE_WARNING);
            return true;
        }

        // Get file size
        $fileSize = filesize($file);

        // File is over maximum allowed file size (8MB)
        if ($fileSize >= $this->settings->maxFileSize * 1000000) {
            $this->log("Skipping big file: {$file}", Logger::TYPE_INFO);
            return false;
        }

        // Failed to get destination
        if (($destination = $this->getDestination($file, $basePath, $isContent)) === false) {
            $this->log("Can't get the destination of {$file}", Logger::TYPE_WARNING);
            return false;
        }

        if ($file === $destination) {
            $this->log("Skipping file copying: Destination same as source: {$destination}", Logger::TYPE_INFO);
            return false;
        }

        // File is over batch size
        if ($fileSize >= $this->settings->batchSize) {
            return $this->copyBig($file, $destination, $this->settings->batchSize);
        }

        // Attempt to copy
        try {
            $this->filesystem->copyFile($file, $destination);
        } catch (RuntimeException $ex) {
            $this->log('Files: ' . $ex->getMessage(), Logger::TYPE_ERROR);
            return false;
        }

        // Set file permissions
        @chmod($destination, $this->permissions->getFilesOctal());

        $this->setDirPermissions($destination);

        return true;
    }

    /**
     * Set directory permissions
     * @param string $file
     * @return bool
     */
    private function setDirPermissions($file)
    {
        $dir = dirname($file);
        if (is_dir($dir)) {
            @chmod($dir, $this->permissions->getDirectoryOctal());
        }

        return false;
    }

    /**
     * Gets destination file and checks if the directory exists, if it does not attempts to create it.
     * If creating destination directory fails, it returns false, gives destination full path otherwise
     * @param string $file
     * @param string $basePath
     * @param bool   $isContent
     * @return string|false
     */
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

    /**
     * @return bool
     */
    protected function shouldUseDefaultWpContentPath(): bool
    {
        // If Wp Content outside ABSPATH let use default path
        return $this->siteInfo->isWpContentOutsideAbspath();
    }

    /**
     * Copy bigger files than $this->settings->batchSize
     * @param string $src
     * @param string $dst
     * @param int $bufferSize
     * @return bool
     */
    private function copyBig($src, $dst, $bufferSize)
    {
        $src  = fopen($src, 'rb');
        $dest = fopen($dst, 'wb');

        if (!$src || !$dest) {
            return false;
        }

        // Try first method:
        while (!feof($src)) {
            if (fwrite($dest, fread($src, $bufferSize)) === false) {
                $error = true;
            }
        }
        // Try second method if first one failed
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

        // Close any open handler
        fclose($src);
        fclose($dest);
        return true;
    }

    /**
     * Check if certain file is excluded from copying process
     *
     * @param string $file full path + filename
     * @return bool
     */
    private function isFileExcluded($file)
    {
        $excludedFiles = (array)$this->options->excludedFiles;

        // Remove .htaccess and web.config from 'excludedFiles' if staging site is copied to a subdomain
        // But skip during update
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

        // Do not copy wp-config.php if the clone gets updated. This is for security purposes,
        // because if the updating process fails, the staging site would not be accessible any longer
        if (
            isset($this->options->mainJob) && $this->options->mainJob === Job::UPDATE
            && stripos(strrev($file), strrev("wp-config.php")) === 0
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if production and staging hostname are identical
     * If they are not identical we assume website is cloned to a subdomain and not into a subfolder
     * @return bool
     */
    private function isIdenticalHostname()
    {
        // hostname of production site without scheme
        $siteurl            = get_site_url();
        $url                = parse_url($siteurl);
        $productionHostname = $url['host'];

        // hostname of staging site without scheme
        $cloneUrl       = empty($this->options->cloneHostname) ? $url : parse_url($this->options->cloneHostname);
        $targetHostname = $cloneUrl['host'];

        // Check if target hostname begins with the production hostname
        // Only compare the hostname without path
        if (wpstg_starts_with($productionHostname, $targetHostname)) {
            return true;
        }

        return false;
    }

    /**
     * Check if certain file is excluded from copying process
     *
     * @param string $file filename including ending + (part) path e.g wp-content/db.php
     * @return bool
     */
    private function isFileExcludedFullPath($file)
    {
        // If path + file exists
        foreach ($this->options->excludedFilesFullPath as $excludedFile) {
            // If the path is not transformable, it will throw exception, in that case we treat the original path already absolute path
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

    /**
     * Replace backward slash with forward slash directory separator
     * Windows Compatibility Fix
     *
     * @param string $path Path
     * @return string
     *
     * @todo replace usage with Strings::sanitizeDirectorySeparator
     */
    private function sanitizeDirectorySeparator($path)
    {
        return preg_replace('/[\\\\]+/', '/', $path);
    }

    /**
     * Check if directory is excluded from copying
     * @param string $directory
     * @return bool
     *
     * @todo Check can it be safely replaced with Directories::isDirectoryExcluded
     */
    private function isDirectoryExcluded($directory)
    {
        $abspath   = $this->sanitizeDirectorySeparator(ABSPATH);
        $directory = $this->sanitizeDirectorySeparator($directory);

        if ($abspath === $directory . '/') {
            return false;
        }

        // Make sure that wp-staging-pro directory / plugin is never excluded
        if (strpos($directory, 'wp-staging') !== false || strpos($directory, 'wp-staging-pro') !== false) {
            return false;
        }

        if ($this->isExtraDirectory($directory)) {
            return false;
        }

        return $this->pathChecker->isPathInPathsList($directory, $this->options->excludedDirectories, false);
    }

    /**
     * Check if directory is an extra directory and should be copied
     * @param string $directory
     * @return bool
     */
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
