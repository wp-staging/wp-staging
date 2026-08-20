<?php

namespace WPStaging\Framework\Utils;

use WPStaging\Framework\Traits\FormatTrait;

class Math
{
    use FormatTrait;







    public function convertUnitToMB($fileSize)
    {
        $units = [
            'B'  => 1 / (1024 * 1024), 
            'KB' => 1 / 1024, 
            'MB' => 1,
            'GB' => 1024, 
            'TB' => 1024 * 1024, 
        ];

        if (preg_match('/^(\d+)\s*(B|KB|MB|GB|TB)$/i', $fileSize, $matches)) {
            $size = (float) $matches[1];
            $unit = strtoupper($matches[2]);

            if (!empty($units[$unit])) {
                return $size * $units[$unit];
            }
        }

        return 0;
    }
}
