<?php

namespace WPStaging\Framework\Utils;

use WPStaging\Framework\Traits\FormatTrait;

class Math
{
    use FormatTrait;

    /**
     * Convert a file size from various units to megabytes (MB).
     *
     * @param string $fileSize like 100MB, 100KB, 100B, 100GB, 100TB
     * @return float|int The file size in megabytes (MB).
     */
    public function convertUnitToMB($fileSize)
    {
        $units = [
            'B' => 1 / (1024 * 1024),  // Bytes to MB
            'KB' => 1 / 1024,          // KB to MB
            'MB' => 1,
            'GB' => 1024,              // GB to MB
            'TB' => 1024 * 1024,       // TB to MB
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
