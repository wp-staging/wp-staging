<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Core\Utils\Logger;

class CopyWpConfig extends FileCloningService
{
    /**
     * Copy wp-config.php from the staging site if it is located outside of root one level up or
     * copy default wp-config.php if production site uses bedrock or any other boilerplate solution that stores wp default config data elsewhere.
     * @return boolean
     */
    protected function internalExecute()
    {
        $this->log("Copy wp-config.php file");

        if ($this->isExcludedWpConfig()) {
            $this->log("Excluded: wp-config.php is excluded by filter");
            return true;
        }

        $dir = trailingslashit(dirname(ABSPATH));

        $source = $dir . 'wp-config.php';

        $destination = $this->dto->getDestinationDir() . 'wp-config.php';

        // Check if there is already a valid wp-config.php in root of staging site
        if ($this->isValidWpConfig($destination)) {
            $this->log("Skipping: wp-config.php already exists in {$destination}");
            return true;
        }

        // Check if there is a valid wp-config.php outside root of wp production site
        if ($this->isValidWpConfig($source)) {
            // Copy it to staging site
            if ($this->copy($source, $destination)) {
                $this->log("Successfully copied wp-config.php file from source {$source} to {$destination}");
                return true;
            }
        }

        // No valid wp-config.php found so let's copy wp stagings default wp-config.php to staging site
        $source = WPSTG_PLUGIN_DIR . "Backend/helpers/wp-config.php";

        $this->log("Copy default wp-config.php file from source {$source} to {$destination}");

        if ($this->copy($source, $destination)) {
            // add missing db credentials to wp-config.php
            if (!$this->alterWpConfig($destination)) {
                throw new FatalException("Can not alter db credentials in wp-config.php");
            }
        } else {
            throw new FatalException("Could not copy wp-config.php to " . $destination);
        }

        //$this->log("Done");
        return true;
    }


    /**
     * Copy files with symlink support
     * @param string $source
     * @param string $destination
     * @return boolean
     */
    protected function copy($source, $destination)
    {
        // Copy symbolic link
        if (is_link($source)) {
            $this->log("Symbolic link found...", Logger::TYPE_INFO);
            if (!@copy(readlink($source), $destination)) {
                $errors = error_get_last();
                $this->log("Failed to copy {$source} Error: {$errors['message']} {$source} -> {$destination}", Logger::TYPE_ERROR);
                return false;
            }
        }

        // Copy file
        if (!@copy($source, $destination)) {
            $errors = error_get_last();
            $this->log("Failed to copy {$source}! Error: {$errors['message']} {$source} -> {$destination}", Logger::TYPE_ERROR);
            return false;
        }

        return true;
    }

    /**
     * Make sure wp-config.php contains correct db credentials
     * @param string $source
     * @return boolean
     */
    protected function alterWpConfig($source)
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

        if (@wpstg_put_contents($source, $content) === false) {
            $this->log("Can't save wp-config.php", Logger::TYPE_ERROR);
            return false;
        }

        return true;
    }

    /**
     * Check if wp-config.php contains important constants
     * @param string $source
     * @return boolean
     */
    protected function isValidWpConfig($source)
    {
        if (!is_file($source) && !is_link($source)) {
            $this->log("Can not find {$source}", Logger::TYPE_INFO);
            return false;
        }


        if (($content = file_get_contents($source)) === false) {
            $this->log("Can not read {$source}", Logger::TYPE_INFO);
            return false;
        }

        //Check whether constants are present in wp-config.php
        $constants = [
            'DB_NAME',
            'DB_USER',
            'DB_PASSWORD',
            'DB_HOST'
        ];
        foreach ($constants as $constant) {
            preg_match($this->getDefineRegex($constant), $content, $matches);

            if (empty($matches[1])) {
                $this->log("Can not find " . $constant . " in wp-config.php", Logger::TYPE_INFO);
                return false;
            }
        }

        return true;
    }
}
