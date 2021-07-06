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
}
