<?php

namespace WPStaging\Framework\Traits;

/**
 * Provide own implementation on trailingslashit and untrailingslashit functions
 * Useful in standalone tool
 * Trait SlashTrait
 * @package WPStaging\Framework\Traits
 */
trait SlashTrait
{
    protected function untrailingslashit(string $string): string
    {
        return rtrim($string, '/');
    }

    protected function trailingslashit(string $string): string
    {
        return $this->untrailingslashit($string) . '/';
    }
}
