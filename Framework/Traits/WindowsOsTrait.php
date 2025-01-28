<?php

namespace WPStaging\Framework\Traits;

/**
 * Useful in standalone tool
 * Trait WindowsOsTrait
 * @package WPStaging\Framework\Traits
 */
trait WindowsOsTrait
{
    public function isWindowsOs(): bool
    {
        return strncasecmp(PHP_OS, 'WIN', 3) === 0;
    }
}
