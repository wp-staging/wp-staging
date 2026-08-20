<?php

namespace WPStaging\Staging\Tasks;

use WPStaging\Framework\Exceptions\WPStagingException;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

abstract class FileAdjustmentTask extends DataAdjustmentTask
{



    protected $filesystem;




    protected $siteInfo;










    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Urls $urls, Filesystem $filesystem, SiteInfo $siteInfo)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue, $urls);
        $this->filesystem = $filesystem;
        $this->siteInfo   = $siteInfo;
    }





    protected function getDefineRegex(string $string): string
    {
        return "/define\s*\(\s*['\"]" . $string . "['\"]\s*,\s*(.*)\s*\);/";
    }






    protected function readFile(string $file): string
    {
        $path = trailingslashit($this->jobDataDto->getStagingSitePath()) . $file;
        if (($content = file_get_contents($path)) === false) {
            throw new WPStagingException("Error - can't read " . $file);
        }

        return $content;
    }







    protected function writeFile(string $file, string $content)
    {
        $path = trailingslashit($this->jobDataDto->getStagingSitePath()) . $file;
        if ($this->filesystem->create($path, $content) === false) {
            throw new WPStagingException("Error - can't write to " . $file . ".");
        }
    }




    protected function readWpConfig(): string
    {
        return $this->readFile('wp-config.php');
    }





    protected function writeWpConfig(string $content)
    {
        $this->writeFile('wp-config.php', $content);
    }
}
