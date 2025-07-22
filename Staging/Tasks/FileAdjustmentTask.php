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
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var SiteInfo
     */
    protected $siteInfo;

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param SeekableQueueInterface $taskQueue
     * @param Urls $urls
     * @param Filesystem $filesystem
     * @param SiteInfo $siteInfo
     */
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Urls $urls, Filesystem $filesystem, SiteInfo $siteInfo)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue, $urls);
        $this->filesystem = $filesystem;
        $this->siteInfo   = $siteInfo;
    }

    /**
     * @param string $string
     * @return string
     */
    protected function getDefineRegex(string $string): string
    {
        return "/define\s*\(\s*['\"]" . $string . "['\"]\s*,\s*(.*)\s*\);/";
    }

    /**
     * @param string $file
     * @return string
     * @throws WPStagingException
     */
    protected function readFile(string $file): string
    {
        $path = trailingslashit($this->jobDataDto->getStagingSitePath()) . $file;
        if (($content = file_get_contents($path)) === false) {
            throw new WPStagingException("Error - can't read " . $file);
        }

        return $content;
    }

    /**
     * @param string $file
     * @param string $content
     * @return void
     * @throws WPStagingException
     */
    protected function writeFile(string $file, string $content)
    {
        $path = trailingslashit($this->jobDataDto->getStagingSitePath()) . $file;
        if ($this->filesystem->create($path, $content) === false) {
            throw new WPStagingException("Error - can't write to " . $file . ".");
        }
    }

    /**
     * @return string
     */
    protected function readWpConfig(): string
    {
        return $this->readFile('wp-config.php');
    }

    /**
     * @param string $content
     * @return void
     */
    protected function writeWpConfig(string $content)
    {
        $this->writeFile('wp-config.php', $content);
    }
}
