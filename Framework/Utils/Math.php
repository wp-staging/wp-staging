<?php

namespace WPStaging\Framework\Utils;

class Math
{
    /**
     * Format bytes into human readable form
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public function formatSize($bytes, $precision = 2)
    {
        if ((int)$bytes < 1) {
            return '';
        }

        $units = ['B', "KB", "MB", "GB", "TB"];

        $bytes = (int)$bytes;
        $base = log($bytes) / log(1000); // 1024 would be for MiB KiB etc
        $pow = pow(1000, $base - floor($base)); // Same rule for 1000

        return round($pow, $precision) . ' ' . $units[(int)floor($base)];
    }

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
