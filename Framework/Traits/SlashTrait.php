<?php

namespace WPStaging\Framework\Traits;







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
