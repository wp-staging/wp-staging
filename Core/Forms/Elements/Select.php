<?php

namespace WPStaging\Core\Forms\Elements;

use WPStaging\Core\Forms\ElementsWithOptions;

/**
 * Class Select
 * @package WPStaging\Core\Forms\Elements
 */
class Select extends ElementsWithOptions
{

    /**
     * @return string
     */
    protected function prepareOutput()
    {
        $output = "<select id='{$this->getId()}' name='{$this->name}' {$this->prepareAttributes()}>";

        foreach ($this->options as $id => $value) {
            $selected = ($this->isSelected($id)) ? " selected=''" : '';

            $output .= "<option value='{$id}'{$selected}>{$value}</option>";
        }

        $output .= "</select>";

        return $output;
    }

    /**
     * @param string $value
     * @return bool
     */
    private function isSelected($value)
    {
        if (
            $this->default &&
            (
                (is_string($this->default) && trim($this->default) === trim($value)) ||
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
