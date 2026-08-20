<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Facades\Hooks;

class LogCleanup
{
 
    const FILTER_LOGS_DELETE_OLDER_THAN_DAYS = 'wpstg.logs.deleteOlderThanDays';

 
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
 
            return;
        }

 
        $deleteOlderThanDays = absint(Hooks::applyFilters(self::FILTER_LOGS_DELETE_OLDER_THAN_DAYS, 14));

 
        $deleteBiggerThan = absint(Hooks::applyFilters(self::FILTER_LOGS_DELETE_BIGGER_THAN_BYTES, 5 * MB_IN_BYTES));

 
        foreach ($it as $splFileInfo) {
            if ($splFileInfo->isFile() && !$splFileInfo->isLink() && $splFileInfo->getExtension() === 'log') {
                if ($splFileInfo->getSize() > $deleteBiggerThan) {
                    unlink($splFileInfo->getPathname());
                    continue;
                }

                if ($splFileInfo->getMTime() < strtotime("-$deleteOlderThanDays days")) {
 
                    unlink($splFileInfo->getPathname());
                }
            }
        }
    }
}
