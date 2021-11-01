<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Core\Utils\Logger;

class LogCleanup
{
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function cleanOldLogs()
    {
        try {
            $it = new \DirectoryIterator($this->logger->getLogDir());
        } catch (\Exception $e) {
            // Early bail: Couldn't open directory.
            return;
        }

        // Delete logs older than 7 days by default
        $deleteOlderThanDays = absint(apply_filters('wpstg.logs.deleteOlderThanDays', 7));

        // Delete logs bigger than 5mb by default
        $deleteBiggerThan = absint(apply_filters('wpstg.logs.deleteBiggerThanBytes', 5 * MB_IN_BYTES));

        /** @var \SplFileInfo $splFileInfo */
        foreach ($it as $splFileInfo) {
            if ($splFileInfo->isFile() && !$splFileInfo->isLink() && $splFileInfo->getExtension() === 'log') {
                if ($splFileInfo->getSize() > $deleteBiggerThan) {
                    unlink($splFileInfo->getPathname());
                    continue;
                }
                if ($splFileInfo->getMTime() < strtotime("-$deleteOlderThanDays days")) {
                    // Not silenced nor logged
                    unlink($splFileInfo->getPathname());
                }
            }
        }
    }
}
