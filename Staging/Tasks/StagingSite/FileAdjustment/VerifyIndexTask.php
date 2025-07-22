<?php

namespace WPStaging\Staging\Tasks\StagingSite\FileAdjustment;

use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Staging\Tasks\FileAdjustmentTask;

/**
 * Replacement for WPStaging\Framework\CloningProcess\Data\ResetIndex
 */
class VerifyIndexTask extends FileAdjustmentTask
{
    /**
     * @return string
     */
    public static function getTaskName()
    {
        return 'staging_verify_index';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return 'Verifying staging site `index.php` file';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute()
    {
        $this->logger->info('Verifying index.php file for staging site');
        if (!$this->siteInfo->isInstalledInSubDir()) {
            $this->logger->info("index.php is verified to be in root of staging site.");
            return $this->generateResponse();
        }

        $this->logger->warning('Current site installation is in a subdirectory. Trying fixing index.php file...');
        /**
         * Before WordPress 5.4: require( dirname( __FILE__ ) . '/wp-blog-header.php' );
         * Since WordPress 5.4:  require __DIR__ . '/wp-blog-header.php';
         */
        $pattern = "/require(.*)wp-blog-header.php(.*)/";
        $content = $this->readFile('index.php');

        if (!preg_match($pattern, $content, $matches)) {
            $this->logger->error("Failed to reset index.php for sub directory. Can not find code 'require(.*)wp-blog-header.php' or require __DIR__ . '/wp-blog-header.php'; in index.php.");
            return $this->generateResponse();
        }

        $replace = "require __DIR__ . '/wp-blog-header.php'; // " . $matches[0] . " Changed by WP-Staging";
        if (($content = preg_replace([$pattern], $replace, $content)) === null) {
            $this->logger->error("Failed to reset index.php for sub directory: Regex replace was not successful.");
            return $this->generateResponse();
        }

        $this->writeFile('index.php', $content);
        $this->logger->info("index.php is reset for the staging site.");

        return $this->generateResponse();
    }
}
