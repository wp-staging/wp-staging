<?php

namespace WPStaging\Framework\Facades\UI;

use WPStaging\Component\Toggle as ToggleComponent;
use WPStaging\Framework\Facades\Facade;

/**
 * Facade providing static access to the Toggle component
 *
 * This facade simplifies access to the Toggle component throughout the codebase
 * by providing a static interface. It allows calling Toggle::render() statically
 * instead of instantiating the component class directly.
 *
 * The facade pattern enables cleaner code and better testability while maintaining
 * dependency injection under the hood.
 *
 * @method static string|void render(string $id, string $name, string $value = '', bool $isChecked = false, array $attributes = [], array $dataAttributes = [])
 */
class Toggle extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return ToggleComponent::class;
    }
}
