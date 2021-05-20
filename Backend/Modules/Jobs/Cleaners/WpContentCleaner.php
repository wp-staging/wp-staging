<?php

namespace WPStaging\Backend\Modules\Jobs\Cleaners;

use WPStaging\Backend\Modules\Jobs\Files;
use WPStaging\Framework\Utils\WpDefaultDirectories;
use WPStaging\Framework\Utils\SlashMode;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Core\Utils\Logger;

/**
 * This class is used to delete all uploads, themes and plugins
 * Currently it is used during push process
 * It will delete uploads, themes and plugins according to the options user selected.
 */
class WpContentCleaner
{
    /**
     * @var array
     */
    private $logs = [];

    /**
     * @var Object
     */
    private $job;

    /**
     * @param Files $job
     */
    public function __construct($job)
    {
        $this->job = $job;
    }

    /**
     * Return logs of this cleaning process
     * @return array
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * Remove Plugins/Themes/Uploads according to option selected
     * $directory param used in this method is mainly for mocking purpose but,
     * can also be used to give path of staging site
     * @param string $directory Root directory of target WordPress Installation
     * @return bool
     */
    public function tryCleanWpContent($directory)
    {
        $options = $this->job->getOptions();

        if ($options->statusContentCleaner === 'finished' || $options->statusContentCleaner === 'skipped') {
            return true;
        }

        // Skip cleaning if staging site is broken and not complete
        if (!is_dir($directory)) {
            return true;
        }

        $wpDirectories = new WpDefaultDirectories();
        $directory = trailingslashit($directory);
        $paths = [];
        if ($options->deleteUploadsFolder && !$options->backupUploadsFolder && $options->statusContentCleaner = 'pending') {
            $paths[] = trailingslashit($directory . $wpDirectories->getRelativeUploadPath());
        }

        if ($options->deletePluginsAndThemes) {
            $paths[] = trailingslashit($directory . $wpDirectories->getRelativeThemePath());
            $paths[] = trailingslashit($directory . $wpDirectories->getRelativePluginPath());
        }

        if (count($paths) === 0) {
            $options->statusContentCleaner = 'skipped';
            $this->job->saveOptions($options);
            return true;
        }

        if ($options->statusContentCleaner === 'pending') {
            $this->logs[] = [
                "msg" => __("Files: Cleaning up directories: Plugins, Themes, Uploads!", "wp-staging"),
                "type" => Logger::TYPE_INFO
            ];

            $options->statusContentCleaner = 'cleaning';
            $this->job->saveOptions($options);
        }

        $excludePaths = [
            $wpDirectories->getRelativePluginPath(SlashMode::BOTH_SLASHES) . "wp-staging*",
            $wpDirectories->getRelativeUploadPath(SlashMode::BOTH_SLASHES) . 'wp-staging', // exclude wp-staging from uploads dir too.
        ];
        $fs = (new Filesystem())
            ->setShouldStop([$this->job, 'isOverThreshold'])
            ->setExcludePaths($excludePaths)
            ->setWpRootPath($directory)
            ->setRecursive();
        try {
            if (!$fs->deletePaths($paths)) {
                return false;
            }
        } catch (\RuntimeException $ex) {
            $this->logs[] = [
                "msg" => sprintf(__("Files: Error - %s. Content cleaning.", "wp-staging"), $ex->getMessage()),
                "type" => Logger::TYPE_ERROR
            ];
            return false;
        }

        $options->statusContentCleaner = 'finished';
        $this->job->saveOptions($options);
        if (!$options->deletePluginsAndThemes) {
            $this->logs[] = [
                "msg" => __("Files: Skipped cleaning Plugins and Themes directories!", "wp-staging"),
                "type" => Logger::TYPE_INFO
            ];
        }

        $this->logs[] = [
            "msg" => __("Files: Finished cleaning!", "wp-staging"),
            "type" => Logger::TYPE_INFO
        ];

        return true;
    }
}
