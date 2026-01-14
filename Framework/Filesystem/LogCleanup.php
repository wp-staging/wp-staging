<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Facades\Hooks;

class LogCleanup
{
    /** @var string */
    const FILTER_LOGS_DELETE_OLDER_THAN_DAYS = 'wpstg.logs.deleteOlderThanDays';

    /** @var string */
    const FILTER_LOGS_DELETE_BIGGER_THAN_BYTES = 'wpstg.logs.deleteBiggerThanBytes';

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

        // Delete logs older than 14 days by default
        $deleteOlderThanDays = absint(Hooks::applyFilters(self::FILTER_LOGS_DELETE_OLDER_THAN_DAYS, 14));

        // Delete logs bigger than 5mb by default
        $deleteBiggerThan = absint(Hooks::applyFilters(self::FILTER_LOGS_DELETE_BIGGER_THAN_BYTES, 5 * MB_IN_BYTES));

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
