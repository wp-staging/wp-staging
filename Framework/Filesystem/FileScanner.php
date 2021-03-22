<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Framework\Filesystem;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class FileScanner
{
    private $directory;
    private $filesystem;
    private $logger;

    public function __construct(Directory $directory, Filesystem $filesystem, LoggerInterface $logger)
    {
        $this->directory  = $directory;
        $this->filesystem = $filesystem;
        $this->logger     = $logger;
    }

    /**
     * @param string $directory
     * @param bool   $includeOtherFilesInWpContent
     * @param array  $excludedDirectories
     *
     * @return array
     */
    public function scan($directory, $includeOtherFilesInWpContent, $excludedDirectories = [])
    {
        try {
            $it = new \DirectoryIterator($directory);
        } catch (\Exception $e) {
            return [];
        }

        /**
         * Allow user to exclude certain file extensions from being exported.
         */
        $ignoreFileExtensions = (array)apply_filters('wpstg.export.site.ignore.file_extension', [
            'log',
        ]);

        /**
         * Allow user to exclude files larger than given size from being exported.
         */
        $ignoreFileBiggerThan = (int)apply_filters('wpstg.export.site.ignore.max_size_in_bytes', 200 * MB_IN_BYTES);

        /**
         * Allow user to exclude files with extension larger than given size from being exported.
         */
        $ignoreFileExtensionFilesBiggerThan = (array)apply_filters('wpstg.export.site.ignore.file_extension_max_size_in_bytes', [
            'zip' => 10 * MB_IN_BYTES,
        ]);

        /*
         * If "Include Other Files in WP Content" is false, only the files inside
         * these folders will be added to the export.
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
                if (in_array($item->getExtension(), $ignoreFileExtensions)) {
                    // Early bail: File has an ignored extension
                    $this->logger->info(sprintf(
                        __('%s: Ignored file "%s" because the extension "%s" is ignored.', 'wp-staging'),
                        /*
                             * We can't use FileScanerTask::TASK_NAME due to an architectural problem.
                             * It's not a trully static method. It uses sprintf that relies on the state of the class,
                             * thus it can only be used inside the context of the task itself.
                             */
                        __('File Scanner', 'wp-staging'),
                        $item->getPathname(),
                        $item->getExtension()
                    ));
                    continue;
                }

                if ($item->getSize() > $ignoreFileBiggerThan) {
                    // Early bail: File is larger than max allowed size.
                    $this->logger->info(sprintf(
                        __('%s: Ignored file "%s" because it exceeds the maximum file size for exporting (%s).', 'wp-staging'),
                        __('File Scanner', 'wp-staging'),
                        $item->getPathname(),
                        size_format($item->getSize())
                    ));
                    continue;
                }

                if (
                    array_key_exists($item->getExtension(), $ignoreFileExtensionFilesBiggerThan) &&
                    $item->getSize() > $ignoreFileExtensionFilesBiggerThan[$item->getExtension()]
                ) {
                    // Early bail: File bigger than expected for given extension
                    $this->logger->info(sprintf(
                        __('%s: Ignored file "%s" because it exceeds the maximum file size for exporting (%s) for files with the "%s" extension.', 'wp-staging'),
                        __('File Scanner', 'wp-staging'),
                        $item->getPathname(),
                        size_format($item->getSize()),
                        $item->getExtension()
                    ));
                    continue;
                }

                $path = $this->filesystem->safePath($item->getPathname());

                foreach ($excludedDirectories as $excludedDirectory) {
                    if (strpos($path, trailingslashit($excludedDirectory)) === 0) {
                        // Early bail: File is inside an ignored folder
                        $this->logger->info(sprintf(
                            __('%s: Ignored file "%s" because it is inside an ignored directory (%s).', 'wp-staging'),
                            __('File Scanner', 'wp-staging'),
                            $item->getPathname(),
                            $excludedDirectory
                        ));
                        continue;
                    }
                }

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
