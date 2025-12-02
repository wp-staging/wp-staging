<?php

namespace WPStaging\Core\Forms\Elements;

use WPStaging\Core\Forms\ElementsWithOptions;
use WPStaging\Framework\Facades\UI\Toggle as ToggleFacade;

/**
 * Form element class for toggle switches in the WP Staging settings forms
 *
 * This class extends ElementsWithOptions to provide toggle switch functionality
 * within the form builder system. It:
 * - Renders toggle switches with proper checked states
 * - Supports multiple value types (string, int, array)
 * - Integrates with the form rendering system
 * - Handles default values and state management
 * - Uses the Toggle facade for consistent UI rendering
 *
 * This element is commonly used for on/off settings like "Debug Mode" or "Optimizer".
 */
class Toggle extends ElementsWithOptions
{

    /**
     * @return string
     */
    protected function prepareOutput(): string
    {
        $output = '';

        foreach ($this->options as $id => $value) {
            $attributeId = $this->getId();
            $output .= ToggleFacade::render($attributeId, $this->name, $id, $this->isChecked($id));

            if ($value) {
                $output .= "<label for='{$attributeId}'>{$value}</label>";
            }
        }

        return $output;
    }

    /**
     * Tested against both types(int and string) due to string as parameter type https://github.com/wp-staging/wp-staging-pro/pull/3190
     * @param string $value
     * @return bool
     */
    private function isChecked(string $value): bool
    {
        if (
            $this->default &&
            (
                (is_string($this->default) && $this->default === $value) ||
                (is_int($this->default) && $this->default === (int)$value) ||
                (is_array($this->default) && in_array($value, $this->default))
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function render()
    {
        return ($this->renderFile) ? @file_get_contents($this->renderFile) : $this->prepareOutput();
    }
}
