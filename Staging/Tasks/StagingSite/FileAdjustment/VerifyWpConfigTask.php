<?php

namespace WPStaging\Staging\Tasks\StagingSite\FileAdjustment;

use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Staging\Tasks\FileAdjustmentTask;

/**
 * Replacement for WPStaging\Framework\CloningProcess\Data\CopyWpConfig
 */
class VerifyWpConfigTask extends FileAdjustmentTask
{
    /**
     * @return string
     */
    public static function getTaskName()
    {
        return 'staging_verify_wp_config';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return 'Verifying staging site `wp-config.php` file';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute()
    {
        $this->logger->info('Verifying wp-config.php file for staging site...');
        if ($this->jobDataDto->getIsWpConfigExcluded()) {
            $this->logger->warning("wp-config.php is excluded by filter, skipping verification...");
            return $this->generateResponse();
        }

        $destination = trailingslashit($this->jobDataDto->getStagingSitePath()) . 'wp-config.php';
        // Check if there is already a valid wp-config.php in root of staging site
        if ($this->isValidWpConfig($destination)) {
            $this->logger->info('wp-config.php file exists in staging site.');
            return $this->generateResponse();
        }

        $this->logger->warning('wp-config.php file doesn\'t exists in staging site. Checking if wp-config exists outside of ABSPATH path...');
        $source = trailingslashit(dirname(ABSPATH)) . 'wp-config.php';
        // Check if there is a valid wp-config.php outside root of wp production site
        if ($this->isValidWpConfig($source)) {
            // Copy it to staging site
            $this->logger->info('wp-config.php file found outside ABSPATH.');
            if ($this->filesystem->copy($source, $destination)) {
                $this->logger->info("Successfully copied wp-config.php file from source {$source} to {$destination}.");
                return $this->generateResponse();
            } else {
                $this->logger->warning("Failed to copy wp-config.php file from source {$source} to {$destination}.");
            }
        }

        // No valid wp-config.php found so let's copy wp stagings default wp-config.php to staging site
        $source = trailingslashit(WPSTG_RESOURCES_DIR) . "helpers/wp-config.php";
        $this->logger->info("Will try copying default wp-config.php file from source {$source} to {$destination}.");

        if (!$this->filesystem->copy($source, $destination)) {
            $this->logger->error("Failed to copy default wp-config.php file from {$source} to {$destination}.");
            return $this->generateResponse();
        }

        $this->logger->info("Default wp-config.php file from source {$source} to {$destination} copied. Will now alter it for the staging site...");

        if (!$this->alterWpConfig($destination)) {
            $this->logger->error("Can not alter db credentials in wp-config.php.");
            return $this->generateResponse();
        }

        $this->logger->info("Default wp-config.php file from source {$source} to {$destination} altered.");

        return $this->generateResponse();
    }

    /**
     * Make sure wp-config.php contains correct db credentials
     * @param string $source
     * @return bool
     */
    protected function alterWpConfig(string $source): bool
    {
        if (($content = file_get_contents($source)) === false) {
            return false;
        }

        $search = "// ** MySQL settings ** //";

        $replace = "// ** MySQL settings ** //\r\n
define( 'DB_NAME', '" . DB_NAME . "' );\r\n
/** MySQL database username */\r\n
define( 'DB_USER', '" . DB_USER . "' );\r\n
/** MySQL database password */\r\n
define( 'DB_PASSWORD', '" . DB_PASSWORD . "' );\r\n
/** MySQL hostname */\r\n
define( 'DB_HOST', '" . DB_HOST . "' );\r\n
/** Database Charset to use in creating database tables. */\r\n
define( 'DB_CHARSET', '" . DB_CHARSET . "' );\r\n
/** The Database Collate type. Don't change this if in doubt. */\r\n
define( 'DB_COLLATE', '" . DB_COLLATE . "' );\r\n";

        $content = str_replace($search, $replace, $content);

        if ($this->filesystem->create($source, $content) === false) {
            return false;
        }

        return true;
    }

    /**
     * Check if wp-config.php contains important constants
     * @param string $source
     * @return bool
     */
    protected function isValidWpConfig(string $source): bool
    {
        if (!is_file($source) && !is_link($source)) {
            $this->logger->warning("Can not find {$source}.");
            return false;
        }


        if (($content = file_get_contents($source)) === false) {
            $this->logger->warning("Can not read {$source}.");
            return false;
        }

        //Check whether constants are present in wp-config.php
        $constants = [
            'DB_NAME',
            'DB_USER',
            'DB_PASSWORD',
            'DB_HOST',
        ];
        foreach ($constants as $constant) {
            preg_match($this->getDefineRegex($constant), $content, $matches);

            if (empty($matches[1])) {
                $this->logger->warning("Can not find " . $constant . " in wp-config.php.");
                return false;
            }
        }

        return true;
    }
}
