<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Core\Utils\Logger;

class UpdateWpConfigTablePrefix extends FileCloningService
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
        //$this->log('Done');
        return true;
    }
}
