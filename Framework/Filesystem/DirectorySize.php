<?php

namespace WPStaging\Framework\Filesystem;




class DirectorySize
{








    public function getSizeInclSubdirs(string $directory, callable $isExcluded): int
    {
        $entries = glob(rtrim($directory, '/') . '/*', GLOB_NOSORT);
        if ($entries === false) {
            return 0;
        }

        $size = 0;
        foreach ($entries as $each) {
 
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
