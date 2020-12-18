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
        $content = $this->readWpConfig();

        $oldUrl = (!$this->dto->isMultisite()) ? $this->dto->getHomeUrl() : $this->dto->getBaseUrl();

        // Replace table prefix
        $pattern = '/\$table_prefix\s*=\s*(.*)/';
        $replacement = '$table_prefix = \'' . $prefix . '\'; // Changed by WP Staging';
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
