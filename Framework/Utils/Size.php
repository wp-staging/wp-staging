<?php

// TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Framework\Utils;

class Size
{
    public function toUnit($bytes)
    {
        $value = floor(log($bytes) / log(1024));
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        return (float) sprintf('%.02F', $bytes / pow(1024, $value)) * 1 . ' ' . $sizes[$value];
    }

    public function toBytes($value)
    {
        if (empty($value)) {
            return 0;
        }
        // Commented code below does not work in PHP 7.0.33 or due to another reason. Error: PHP Notice:  Uninitialized string offset: -1
        //$unit = strtolower($value)[-1];
        $unit = strtolower(substr(trim($value), -1));
        $sizes = ['b' => 0, 'k' => 1, 'm' => 2, 'g' => 3];
        return (int)$value * pow(1024, $sizes[$unit]);
    }
}
