<?php

namespace WPStaging\Framework\Traits;

/**
 * Provide methods related to formatting of text or numbers
 */
trait FormatTrait
{
    /**
     * Format bytes into human readable form
     *
     * @param int|float $size
     * @param int $decimals
     * @return string
     */
    public function formatSize($size, int $decimals = 2): string
    {
        if ((int)$size < 1) {
            return '';
        }

        $units = ['B', "KB", "MB", "GB", "TB"];

        $size = (int)$size;
        $base = log($size) / log(1000); // 1024 would be for MiB KiB etc
        $pow = pow(1000, $base - floor($base)); // Same rule for 1000

        return round($pow, $decimals) . ' ' . $units[(int)floor($base)];
    }
}
