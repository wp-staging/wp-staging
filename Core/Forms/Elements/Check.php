<?php

namespace WPStaging\Core\Forms\Elements;

use WPStaging\Core\Forms\ElementsWithOptions;

/**
 * Class Check
 * @package WPStaging\Core\Forms\Elements
 */
class Check extends ElementsWithOptions
{

    /**
     * @return string
     */
    protected function prepareOutput()
    {
        $output = '';

        foreach ($this->options as $id => $value) {
            $checked = ($this->isChecked($id)) ? " checked=''" : '';

            $attributeId = $this->getId() . '_' . $this->getId($id);

            $output .= "<input type='checkbox' class='wpstg-checkbox' name='{$this->getId()}' id='{$attributeId}' value='{$id}' {$checked}/>";

            if ($value) {
                $output .= "<label for='{$attributeId}'>{$value}</label>";
            }
        }

        return $output;
    }

    /**
     * @param string $value
     * @return bool
     */
    private function isChecked($value)
    {
        if (
            $this->default &&
            (
                (is_string($this->default) && $this->default === $value) ||
                (is_int($value) && $value == (int) $this->default) ||
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
