<?php

// TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Service\Utils;

class FileSize
{
    public function humanReadable($bytes)
    {
        $value = floor(log($bytes) / log(1024));
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        return sprintf('%.02F', $bytes / pow(1024, $value)) * 1 . ' ' . $sizes[$value];
    }
}
