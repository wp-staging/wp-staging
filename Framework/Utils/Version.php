<?php

namespace WPStaging\Framework\Utils;






class Version
{







    public function convertStringFormatToIntFormat(string $versionString): int
    {
        $versionParts = explode('.', $versionString);
        if (count($versionParts) !== 3) {
            throw new \InvalidArgumentException('Invalid version string format');
        }

        foreach ($versionParts as $part) {
            if (!is_numeric($part)) {
                throw new \InvalidArgumentException('Version parts must be positive integers');
            }
        }

        $versionParts = array_map('intval', $versionParts);

        if ($versionParts[0] < 0 || $versionParts[1] < 0 || $versionParts[2] < 0) {
            throw new \InvalidArgumentException('Version parts must be positive integers');
        }

        if ($versionParts[0] === 0 && $versionParts[1] === 0 && $versionParts[2] === 0) {
            throw new \InvalidArgumentException('Invalid version string format');
        }

        if ($versionParts[1] > 100 || $versionParts[2] > 100) {
            throw new \InvalidArgumentException('Version Minor and Patch parts must be less than 100');
        }

        return $versionParts[0] * 10000 + $versionParts[1] * 100 + $versionParts[2];
    }








    public function convertIntFormatToStringFormat(int $version): string
    {
        if ($version < 1) {
            throw new \InvalidArgumentException('Version must be a positive integer');
        }

        $major = floor($version / 10000);
        $minor = floor(($version % 10000) / 100);
        $patch = $version % 100;

        return sprintf('%d.%d.%d', $major, $minor, $patch);
    }
}
