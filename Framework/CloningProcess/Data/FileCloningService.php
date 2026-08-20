<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\SiteInfo;

 
abstract class FileCloningService extends CloningService
{



    protected function readFile($file)
    {
        $path = $this->dto->getDestinationDir() . $file;
        if (($content = file_get_contents($path)) === false) {
            throw new FatalException("Error - can't read " . $file);
        }

        return $content;
    }




    protected function writeFile($file, $content)
    {
        $path       = $this->dto->getDestinationDir() . $file;
        $filesystem = WPStaging::make(Filesystem::class);
        if ($filesystem->create($path, $content) === false) {
            throw new FatalException("Error - can't write to " . $file);
        }
    }




    protected function readWpConfig()
    {
        $fileContent = $this->readFile('wp-config.php');
        return $this->normalizeFileContent($fileContent);
    }




    protected function writeWpConfig($content)
    {
        $this->writeFile('wp-config.php', $content);
    }





    protected function isSubDir()
    {
        return (new SiteInfo())->isInstalledInSubDir();
    }




    protected function isExcludedWpConfig()
    {
        return $this->dto->getJob()->excludeWpConfigDuringUpdate();
    }






    protected function normalizeFileContent(string $fileContent): string
    {
        if ($fileContent === '' || strpos($fileContent, "\r") === false) {
 
            return $fileContent;
        }

        return str_replace(
            ["\r\r\n", "\r\n", "\r\r", "\r"],
            ["\r\n", "\r\n", "\r\n", "\n"],
            $fileContent
        );
    }
}
