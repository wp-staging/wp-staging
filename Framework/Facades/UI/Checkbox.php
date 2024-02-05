<?php

namespace WPStaging\Framework\Facades\UI;

use WPStaging\Framework\Component\UI\CheckboxWrapper;
use WPStaging\Framework\Facades\Facade;

/**
 * @method static string|void render(string $id, string $name, string $value = '', bool $isChecked = false, array $attributes = [], array $dataAttributes = [], bool $returnAsString = false)
 */
class Checkbox extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return CheckboxWrapper::class;
    }
}
