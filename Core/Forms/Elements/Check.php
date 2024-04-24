<?php

namespace WPStaging\Core\Forms\Elements;

use WPStaging\Core\Forms\ElementsWithOptions;
use WPStaging\Framework\Facades\UI\Checkbox;

/**
 * Class Check
 * @package WPStaging\Core\Forms\Elements
 */
class Check extends ElementsWithOptions
{

    /**
     * @return string
     */
    protected function prepareOutput(): string
    {
        $output = '';

        foreach ($this->options as $id => $value) {
            $attributeId = $this->getId();
            $output .= Checkbox::render($attributeId, $this->getId(), $id, $this->isChecked($id), [], [], true);

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
