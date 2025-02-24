<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Filesystem;

class UpdateWpConfig extends FileCloningService
{
    /**
     * @inheritDoc
     */
    protected function internalExecute()
    {
        $path = $this->dto->getDestinationDir() . "wp-config.php";
        $prefix = $this->dto->getPrefix();
        $this->log("Updating table_prefix in {$path} to " . $prefix);

        if ($this->isExcludedWpConfig()) {
            $this->log("Excluded: wp-config.php is excluded by filter");
            return true;
        }

        $content = $this->readWpConfig();

        $oldUrl = (!$this->dto->isMultisite()) ? $this->dto->getHomeUrl() : $this->dto->getBaseUrl();

        // Don't update the table prefix if the line starts with //, /* or * (ignoring space before them),
        // Otherwise replace table prefix
        $pattern = '/^\s*((?!\/\/|\/\*|\*))\$table_prefix\s*=\s*(.*)/m';
        $replacement = '$table_prefix = \'' . $prefix . '\'; // Changed by WP STAGING';
        $content = preg_replace($pattern, $replacement, $content);

        if ($content === null) {
            throw new FatalException("Failed to update table_prefix in {$path}. Regex error", Logger::TYPE_ERROR);
        }

        // Replace URLs
        $content = str_replace($oldUrl, $this->dto->getStagingSiteUrl(), $content);

        $this->writeWpConfig($content);

        $this->writeFileHeader($path);

        //$this->log('Done');
        return true;
    }

    /**
     * Modify wp-config.php to add staging site information
     *
     * @param string $filePath
     * @return boolean
     */
    protected function writeFileHeader($filePath)
    {
        if (($content = file_get_contents($filePath)) === false) {
            $this->log("Can't read wp-config.php", Logger::TYPE_ERROR);

            return false;
        }

        $search  = "<?php";
        $marker  = "@wp-staging";
        $replace = "<?php\r\n
/**
 * " . $marker . "
 * Site         : " . $this->dto->getStagingSiteUrl() . "
 * Parent       : " . $this->dto->getBaseUrl() . "
 * Created at   : " . current_time('d.m.Y H:i:s') . "
 * Updated at   : " . current_time('d.m.Y H:i:s') . "
 * Read more    : https://wp-staging.com/docs/create-a-staging-site-clone-wordpress/
 */\r\n";

        $filesystem = WPStaging::make(Filesystem::class);

        // Check if the text already exists
        if (strpos($content, $marker) === false) {
            $content = str_replace($search, $replace, $content);

            if ($filesystem->create($filePath, $content) === false) {
                $this->log("Can't save wp-config.php", Logger::TYPE_ERROR);

                return false;
            }
        }

        return true;
    }
}
