<?php

namespace WPStaging\Core\Utils;

use WPStaging\Framework\Filesystem\Filesystem;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

/**
 * Description of IISWEbConfig
 *
 * @author IronMan
 */
class IISWebConfig
{

    /**
     *
     * @var obj
     */
    private $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * Create web.config file
     *
     * @param  string  $path Path to file
     * @return boolean
     */
    public function create($path)
    {
        return $this->filesystem->create($path, implode(PHP_EOL, [
                    '<configuration>',
                    '<system.webServer>',
                    '<staticContent>',
                    '<clear/>',
                    '<mimeMap fileExtension=".log" mimeType="application/octet-stream" />',
                    '<mimeMap fileExtension=".wpstg" mimeType="application/octet-stream" />',
                    '</staticContent>',
                    '<defaultDocument>',
                    '<files>',
                    '<clear/>',
                    '<add value="index.php" />',
                    '</files>',
                    '</defaultDocument>',
                    '<directoryBrowse enabled="false" />',
                    '</system.webServer>',
                    '</configuration>',
                ]));
    }
}
