<?php

namespace WPStaging\Framework\Facades\UI;

use WPStaging\Component\Alert as AlertComponent;
use WPStaging\Framework\Facades\Facade;

/**
 * @method static void render(string $title = '', string $desc = '', string $buttonText = '', string $buttonUrl = '', bool $closeable = false, bool $visible = true)
 * @method static void renderCloseable()
 */
class Alert extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return AlertComponent::class;
    }
}
