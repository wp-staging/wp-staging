<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Framework\SiteInfo;

//TODO: Class may not be needed in the future due to DTO introduction. Remove if unnecessary
abstract class FileCloningService extends CloningService
{
    /**
     * @return false|string
     */
    protected function readFile($file)
    {
        $path = $this->dto->getDestinationDir() . $file;
        if (($content = file_get_contents($path)) === false) {
            throw new FatalException("Error - can't read " . $file);
        }
        return $content;
    }

    /**
     * @param string $content
     */
    protected function writeFile($file, $content)
    {
        $path = $this->dto->getDestinationDir() . $file;
        if (@wpstg_put_contents($path, $content) === false) {
            throw new FatalException("Error - can't write to " . $file);
        }
    }

    /**
     * @return false|string
     */
    protected function readWpConfig()
    {
        return $this->readFile('wp-config.php');
    }

    /**
     * @param string $content
     */
    protected function writeWpConfig($content)
    {
        $this->writeFile('wp-config.php', $content);
    }

    /**
     * Check if WP is installed in subdir
     * @return bool
     */
    protected function isSubDir()
    {
        return (new SiteInfo())->isInstalledInSubDir();
    }

    /**
     * @return bool
     */
    protected function isExcludedWpConfig()
    {
        return $this->dto->getJob()->excludeWpConfigDuringUpdate();
    }
}
