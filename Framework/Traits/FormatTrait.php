<?php

namespace WPStaging\Framework\Traits;




trait FormatTrait
{








    public function formatSize($size, int $decimals = 2, bool $binary = false): string
    {
        if ((int)$size < 1) {
            return '';
        }

        $units = ['B', "KB", "MB", "GB", "TB"];

        $size     = (int)$size;
        $unitStep = $binary ? 1024 : 1000;
        $base     = log($size) / log($unitStep); 
        $pow      = pow($unitStep, $base - floor($base)); 

        return round($pow, $decimals) . ' ' . $units[(int)floor($base)];
    }
}
