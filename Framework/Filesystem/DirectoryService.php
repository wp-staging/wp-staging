<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Framework\Filesystem;

use WPStaging\Vendor\Symfony\Component\Finder\Finder;
use WPStaging\Framework\Adapter\Directory;

class DirectoryService
{

    /** @var Directory */
    private $directory;

    public function __construct(Directory $directory)
    {
        $this->directory = $directory;
    }

    /**
     * @param string $directory
     * @param string $depth
     * @param array|null $excludedDirectories
     *
     * @return Finder|null
     */
    public function scan($directory, $depth = null, array $excludedDirectories = null)
    {
        $finder = (new Finder)
            ->ignoreUnreadableDirs()
            ->directories()
            ->in($directory)
        ;

        if ($excludedDirectories) {
            foreach($excludedDirectories as $excludedDirectory) {
                $notPath = str_replace($directory, null, $excludedDirectory);
                $notPath = '#' . trim($notPath, '#') . '#';
                $finder->notPath($notPath);
            }
        }

        if ($depth !== null) {
            $finder->depth($depth);
        }

        $finderHasResults = count($finder) > 0;

        if (!$finderHasResults) {
            return null;
        }

        return $finder;
    }

    /**
     * @return Directory
     */
    public function getDirectory()
    {
        return $this->directory;
    }
}
