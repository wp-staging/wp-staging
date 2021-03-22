<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Framework\Filesystem;

use WPStaging\Vendor\Psr\Log\LoggerInterface;

class DirectoryScanner
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $directory
     * @param array  $excludedDirectories
     *
     * @return array
     */
    public function scan($directory, array $excludedDirectories = [])
    {
        try {
            $it = new \DirectoryIterator($directory);
        } catch (\Exception $e) {
            return [];
        }

        $excludedDirectories = array_map(function ($item) {
            return trailingslashit($item);
        }, $excludedDirectories);

        $dirs = [];

        /** @var \SplFileInfo $item */
        foreach ($it as $item) {
            if ($item->isDir() && $item->getFilename() != "." && $item->getFilename() != "..") {
                if (in_array(trailingslashit($item->getRealPath()), $excludedDirectories)) {
                    // Early bail: Directory is ignored
                    $this->logger->info(sprintf(
                        __('%s: Ignored directory "%s" because it\'s in the ignored directories list.', 'wp-staging'),
                        __('Directory Scanner', 'wp-staging'),
                        $item->getRealPath()
                    ));
                    continue;
                }
                $dirs[] = $item->getRealPath();
            }
        }

        return $dirs;
    }
}
