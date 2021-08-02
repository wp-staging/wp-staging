<?php

namespace WPStaging\Backend\Modules\Jobs;

use RuntimeException;
use SplFileObject;
use WPStaging\Backend\Modules\Jobs\Cleaners\WpContentCleaner;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\Permissions;
use WPStaging\Framework\Filesystem\WpUploadsFolderSymlinker;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\Utils\WpDefaultDirectories;

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

    /**
     * @var SplFileObject
     */
    private $file;

    /**
     * @var int
     */
    private $maxFilesPerRun;

    /**
     * @var string
     */
    private $destination;

    /**
     * @var Permissions
     */
    private $permissions;

    /**
     * Initialization
     */
    public function initialize()
    {

        $this->permissions = new Permissions();

        $this->destination = $this->options->destinationDir;

        $filePath = $this->cache->getCacheDir() . "files_to_copy." . $this->cache->getCacheExtension();

        if (is_file($filePath)) {
            $this->file = new SplFileObject($filePath, 'r');
        }

        $logStep = 0;
        if ($this->options->mainJob === 'resetting' || $this->options->mainJob === 'updating') {
            $logStep = 1;
        }

        // Informational logs
        if ($this->options->currentStep === $logStep) {
            $this->log("Copying files...");
        }

        $this->settings->batchSize = $this->settings->batchSize * 1000000;
        $this->maxFilesPerRun = $this->settings->fileLimit;
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
        if ($this->options->mainJob === 'resetting' || $this->options->mainJob === 'updating') {
            $this->options->totalSteps++;
        }
    }

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
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
     */
    private function cleanStagingDirectory()
    {
        if ($this->options->mainJob !== 'resetting') {
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
            if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                error_log('Destination is not a directory: ' . $this->destination);
            }
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

        $fs = (new Filesystem())
            ->setShouldStop([$this, 'isOverThreshold'])
            ->shouldPermissionExceptionsBypass(true)
            ->setRecursive(true);
        try {
            if (!$fs->delete($this->destination)) {
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
        if ($this->options->mainJob !== 'updating') {
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
     */
    private function getFilesAndCopy()
    {
        if ($this->options->currentStep === 0 && ($this->options->mainJob === 'resetting' || $this->options->mainJob === 'updating')) {
            return true;
        }

        // Over limits threshold
        if ($this->isOverThreshold()) {
            // Prepare response and save current progress
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }

        // Go to last copied line and than to next one
        if (isset($this->options->copiedFiles) && $this->options->copiedFiles != 0) {
            $this->file->seek($this->options->copiedFiles - 1);
        }

        $this->file->setFlags(SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);

        for ($i = 0; $i < $this->maxFilesPerRun; $i++) {
            // Increment copied files
            // Do this anytime to make sure to not stuck in the same step / files
            $this->options->copiedFiles++;

            // End of file
            if ($this->file->eof()) {
                break;
            }

            $file = str_replace(PHP_EOL, null, $this->file->fgets());


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
        if ($this->options->mainJob === 'updating') {
            return true;
        }

        if (!$this->options->uploadsSymlinked) {
            $this->log(__("Skipped symlinking Wp Uploads Folder", 'wp-staging'));
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
        $file = trim(WPStaging::getWPpath() . $file);

        $file = wpstg_replace_windows_directory_separator($file);

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
            $this->debugLog("Skipping file by rule: {$file}", Logger::TYPE_INFO);
            return false;
        }

        // Invalid file, skipping it as if succeeded
        if (!is_file($file)) {
            $this->log("File doesn't exist {$file}", Logger::TYPE_WARNING);
            return true;
        }
        // Invalid file, skipping it as if succeeded
        if (!is_readable($file)) {
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
        if (($destination = $this->getDestination($file)) === false) {
            $this->log("Can't get the destination of {$file}", Logger::TYPE_WARNING);
            return false;
        }

        // File is over batch size
        if ($fileSize >= $this->settings->batchSize) {
            return $this->copyBig($file, $destination, $this->settings->batchSize);
        }

        // Attempt to copy
        if (!@copy($file, $destination)) {
            $errors = error_get_last();
            $this->log("Files: Failed to copy file to destination. Error: {$errors['message']} {$file} -> {$destination}", Logger::TYPE_ERROR);
            return false;
        }

        // Set file permissions
        @chmod($destination, $this->permissions->getFilesOctal());

        $this->setDirPermissions($destination);

        return true;
    }

    /**
     * Get wp-content and wp-content/uploads destination dir
     * Necessary if these folders were customized and changed from the default ones.
     *
     * @return string
     */
    protected function getWpContentPath($file)
    {
        // Get upload directory information
        $uploads = wp_upload_dir();

        // Get absolute path to wordpress uploads directory e.g srv/www/htdocs/sitename/wp-content/uploads
        $uploadsAbsPath = trailingslashit($uploads['basedir']);

        // Get relative path to the uploads folder, e.g assets or wp-content/uploads
        $uploadsRelPath = (new WpDefaultDirectories())->getRelativeUploadPath();

        // Get absolute path to wp-content directory e.g srv/www/htdocs/sitename/wp-content
        $wpContentDir = trailingslashit(WP_CONTENT_DIR);

        // Check if there is a custom uploads directory, then do a search $ replace. Do this only if custom upload path is not identical to WP_CONTENT_DIR
        if ($uploadsAbsPath != $wpContentDir) {
            $file = str_replace($uploadsAbsPath, ABSPATH . $uploadsRelPath, $file, $count);
        }
        // If there is no custom upload directory do a search & replace of the custom wp-content directory
        if (empty($count) || $count === 0) {
            $file = str_replace($wpContentDir, ABSPATH . 'wp-content/', $file);
        }


        return $file;
    }

    /**
     * Set directory permissions
     * @param string $file
     * @return boolean
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
     * @return bool|string
     */
    private function getDestination($file)
    {
        $file = wpstg_replace_windows_directory_separator($file);
        $rootPath = wpstg_replace_windows_directory_separator(WPStaging::getWPpath());
        $relativePath = str_replace($rootPath, null, $file);
        $destinationPath = $this->destination . $relativePath;
        $destinationDirectory = dirname($destinationPath);

        if (!is_dir($destinationDirectory) && !(new Filesystem())->mkdir($destinationDirectory) && !is_dir($destinationDirectory)) {
            $this->log("Files: Can not create directory {$destinationDirectory}. Possible write permission error!", Logger::TYPE_ERROR);
            return false;
        }

        return $this->sanitizeDirectorySeparator($destinationPath);
    }

    /**
     * Copy bigger files than $this->settings->batchSize
     * @param string $src
     * @param string $dst
     * @param int $buffersize
     * @return boolean
     */
    private function copyBig($src, $dst, $buffersize)
    {
        $src = fopen($src, 'rb');
        $dest = fopen($dst, 'wb');

        if (!$src || !$dest) {
            return false;
        }

        // Try first method:
        while (!feof($src)) {
            if (fwrite($dest, fread($src, $buffersize)) === false) {
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
     * @return boolean
     */
    private function isFileExcluded($file)
    {
        $excludedFiles = (array)$this->options->excludedFiles;

        // Remove .htaccess and web.config from 'excludedFiles' if staging site is copied to a subdomain
        if ($this->isIdenticalHostname() === false) {
            $excludedFiles = \array_diff(
                $excludedFiles,
                ["web.config", ".htaccess"]
            );
        }

        if ((new Filesystem())->isFilenameExcluded($file, $excludedFiles)) {
            return true;
        }

        // Do not copy wp-config.php if the clone gets updated. This is for security purposes,
        // because if the updating process fails, the staging site would not be accessable any longer
        if (
            isset($this->options->mainJob) && $this->options->mainJob === "updating"
            && stripos(strrev($file), strrev("wp-config.php")) === 0
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if production and staging hostname are identical
     * If they are not identical we assume website is cloned to a subdomain and not into a subfolder
     * @return boolean
     */
    private function isIdenticalHostname()
    {
        // hostname of production site without scheme
        $siteurl = get_site_url();
        $url = parse_url($siteurl);
        $productionHostname = $url['host'];

        // hostname of staging site without scheme
        $cloneUrl = empty($this->options->cloneHostname) ? $url : parse_url($this->options->cloneHostname);
        $targetHostname = $cloneUrl['host'];

        // Check if target hostname beginns with the production hostname
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
     * @return boolean
     */
    private function isFileExcludedFullPath($file)
    {
        // If path + file exists
        foreach ($this->options->excludedFilesFullPath as $excludedFile) {
            if (strpos($file, $excludedFile) !== false) {
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

        // TODO: Inject Directory using DI.
        return WPStaging::make(Directory::class)->isPathInPathsList($directory, $this->options->excludedDirectories, true);
    }

    /**
     * Check if directory is an extra directory and should be copied
     * @param string $directory
     * @return boolean
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
