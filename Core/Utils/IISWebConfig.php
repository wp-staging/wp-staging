<?php

namespace WPStaging\Core\Utils;

use WPStaging\Framework\Filesystem\Filesystem;

 
if (!defined("WPINC")) {
    die;
}






class IISWebConfig
{
 
    const STATIC_FILE_HANDLER = '<add name="StaticFile" path="*" verb="*" modules="StaticFileModule,DefaultDocumentModule,DirectoryListingModule" resourceType="Either" requireAccess="Read" />';





    private $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }







    public function create($path)
    {
        return $this->filesystem->create($path, implode(PHP_EOL, [
            '<configuration>',
            '<system.webServer>',
            '<handlers>',
            '<clear/>',
            self::STATIC_FILE_HANDLER,
            '</handlers>',
            '<staticContent>',
            '<clear/>',
            '<mimeMap fileExtension=".log" mimeType="application/octet-stream" />',
            '<mimeMap fileExtension=".wpstg" mimeType="application/octet-stream" />',
            '<mimeMap fileExtension=".wpstgtmp" mimeType="application/octet-stream" />',
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
