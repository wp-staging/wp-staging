<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Filesystem\Filesystem;

class CopyWpConfig extends FileCloningService
{



    protected $filesystem;






    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }






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

 
        if ($this->isValidWpConfig($destination)) {
            $this->log("Skipping: wp-config.php already exists in {$destination}");
            return true;
        }

 
        if ($this->isValidWpConfig($source)) {
 
            if ($this->copy($source, $destination)) {
                $this->log("Successfully copied wp-config.php file from source {$source} to {$destination}");
                return true;
            }
        }

 
        $source = WPSTG_RESOURCES_DIR . "helpers/wp-config.php";

        $this->log("Copy default wp-config.php file from source {$source} to {$destination}");

        if ($this->copy($source, $destination)) {
 
            if (!$this->alterWpConfig($destination)) {
                throw new FatalException("Can not alter db credentials in wp-config.php");
            }
        } else {
            throw new FatalException("Could not copy wp-config.php to " . $destination);
        }

        return true;
    }








    protected function copy($source, $destination)
    {
 
        if (is_link($source)) {
            $this->log("Symbolic link found...", Logger::TYPE_INFO);
            if (!@copy(readlink($source), $destination)) {
                $errors = error_get_last();
                $this->log("Failed to copy {$source} Error: {$errors['message']} {$source} -> {$destination}", Logger::TYPE_ERROR);
                return false;
            }
        }

 
        if (!@copy($source, $destination)) {
            $errors = error_get_last();
            $this->log("Failed to copy {$source}! Error: {$errors['message']} {$source} -> {$destination}", Logger::TYPE_ERROR);
            return false;
        }

        return true;
    }






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
define( 'DB_COLLATE', '" . (defined('DB_COLLATE') ? DB_COLLATE : '') . "' );\r\n";

        $content = $this->normalizeFileContent($content);
        $content = str_replace($search, $replace, $content);

        if ($this->filesystem->create($source, $content) === false) {
            $this->log("Can't save wp-config.php", Logger::TYPE_ERROR);

            return false;
        }

        return true;
    }






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

 
        $constants = [
            'DB_NAME',
            'DB_USER',
            'DB_PASSWORD',
            'DB_HOST',
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
