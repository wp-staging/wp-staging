<?php

namespace WPStaging\Framework\Filesystem;

/**
 * Recursive directory size shared by the staging and push size estimates.
 */
class DirectorySize
{
    /**
     * Size in bytes of all files below the directory.
     *
     * @param string   $directory
     * @param callable $isExcluded Gets the absolute path of every file and folder; return
     *                             true to skip it (a folder is skipped with its whole subtree).
     * @return int
     */
    public function getSizeInclSubdirs(string $directory, callable $isExcluded): int
    {
        $entries = glob(rtrim($directory, '/') . '/*', GLOB_NOSORT);
        if ($entries === false) {
            return 0;
        }

        $size = 0;
        foreach ($entries as $each) {
            // Symlinks are not copied, and a circular link would loop forever.
            if (is_link($each)) {
                continue;
            }

            if ($isExcluded($each)) {
                continue;
            }

            if (is_file($each)) {
                $size += (int)filesize($each);
                continue;
            }

            $size += $this->getSizeInclSubdirs($each, $isExcluded);
        }

        return $size;
    }
}
