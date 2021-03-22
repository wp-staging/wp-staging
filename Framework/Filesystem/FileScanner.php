<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Framework\Filesystem;

use WPStaging\Framework\Adapter\Directory;

class FileScanner
{
    private $directory;
    private $filesystem;

    public function __construct(Directory $directory, Filesystem $filesystem)
    {
        $this->directory  = $directory;
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $directory
     * @param bool   $includeOtherFilesInWpContent
     *
     * @return array
     */
    public function scan($directory, $includeOtherFilesInWpContent)
    {
        try {
            $it = new \DirectoryIterator($directory);
        } catch (\Exception $e) {
            return [];
        }

        /**
         * Allow user to exclude certain file extensions from being exported.
         */
        $excludedFileExtensions = (array)apply_filters('wpstg.export.site.file_extension.excluded', []);

        /**
         * Allow user to exclude files larger than given size from being exported.
         */
        $ignoreFilesBiggerThan = (int)apply_filters('wpstg.export.site.file.max_size_in_bytes', PHP_INT_MAX);

        /*
         * If "Include Other Files in WP Content" is false, only the files inside
         * these folders will be added to the export.
         *
         * @todo: Do we want to add the commitment of a filter for this?
         */
        $defaultWpContentFolders = [
            WP_PLUGIN_DIR,
            $this->directory->getUploadsDirectory(),
            get_theme_root(),
            WPMU_PLUGIN_DIR,
        ];

        $files = [];

        /** @var \SplFileInfo $item */
        foreach ($it as $item) {
            $shouldScan = $item->isFile() &&
                          !$item->isLink() &&
                          $item->getFilename() != "." &&
                          $item->getFilename() != "..";

            if ($shouldScan) {
                if (in_array($item->getExtension(), $excludedFileExtensions)) {
                    // Early bail: File has an ignored extension
                    continue;
                }

                if ($item->getSize() > $ignoreFilesBiggerThan) {
                    // Early bail: File is larger than max allowed size.
                    continue;
                }

                $path = $this->filesystem->safePath($item->getPathname());

                if (!$includeOtherFilesInWpContent) {
                    foreach ($defaultWpContentFolders as $defaultFolder) {
                        /*
                         * Only include files that are inside allowed folders.
                         */
                        if (strpos($path, trailingslashit($defaultFolder)) === 0) {
                            $files[] = $path;
                        }
                    }
                } else {
                    $files[] = $path;
                }
            }
        }

        return $files;
    }
}
