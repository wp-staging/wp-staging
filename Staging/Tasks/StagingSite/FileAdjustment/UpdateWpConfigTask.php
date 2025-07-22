<?php

namespace WPStaging\Staging\Tasks\StagingSite\FileAdjustment;

use WPStaging\Framework\Exceptions\WPStagingException;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Staging\Tasks\FileAdjustmentTask;

/**
 * Replacement for WPStaging\Framework\CloningProcess\Data\UpdateWpConfig
 */
class UpdateWpConfigTask extends FileAdjustmentTask
{
    /**
     * @return string
     */
    public static function getTaskName()
    {
        return 'staging_update_wp_config';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return 'Adding site header and adjusting prefix in the staging site `wp_config.php` file.';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute()
    {
        $path = trailingslashit($this->jobDataDto->getStagingSitePath()) . "wp-config.php";
        $this->logger->info('Adjusting database prefix in wp-config.php file for staging site.');
        if ($this->jobDataDto->getIsWpConfigExcluded()) {
            $this->logger->warning("wp-config.php is excluded by filter, skipping adjustments.");
            return $this->generateResponse();
        }

        $content = $this->readWpConfig();
        $prefix  = $this->jobDataDto->getDatabasePrefix();

        // Don't update the table prefix if the line starts with //, /* or * (ignoring space before them),
        // Otherwise replace table prefix
        $pattern     = '/^\s*((?!\/\/|\/\*|\*))\$table_prefix\s*=\s*(.*)/m';
        $replacement = '$table_prefix = \'' . $prefix . '\'; // Changed by WP STAGING';
        $content     = preg_replace($pattern, $replacement, $content);

        if ($content === null) {
            $this->logger->error("Failed to update table prefix in {$path}. Regex couldn't replace the prefix.");
            return $this->generateResponse();
        }

        // Replace URLs
        $oldUrl  = $this->siteInfo->isMultisite() ? $this->urls->getBaseUrl() : $this->urls->getHomeUrl();
        $content = str_replace($oldUrl, $this->jobDataDto->getStagingSiteUrl(), $content);
        $this->writeWpConfig($content);
        $this->maybeAddFileHeader();

        return $this->generateResponse();
    }

    /**
     * Modify wp-config.php to add staging site information
     * @return bool
     */
    protected function maybeAddFileHeader(): bool
    {
        $content = $this->readWpConfig();
        $search  = "<?php";
        $marker  = "@wp-staging";
        $replace = "<?php\r\n
/**
 * " . $marker . "
 * Site         : " . $this->jobDataDto->getStagingSiteUrl() . "
 * Parent       : " . $this->urls->getBaseUrl() . "
 * Created at   : " . current_time('d.m.Y H:i:s') . "
 * Updated at   : " . current_time('d.m.Y H:i:s') . "
 * Read more    : https://wp-staging.com/docs/create-a-staging-site-clone-wordpress/
 */\r\n";

        // Check if the text already exists
        if (strpos($content, $marker) !== false) {
            return true;
        }

        $content = str_replace($search, $replace, $content);
        try {
            $this->writeWpConfig($content);
        } catch (WPStagingException $e) {
            $this->logger->warning("Can't add staging site header into wp-config.php.");
        }

        return true;
    }
}
