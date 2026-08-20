<?php

namespace WPStaging\Core\Forms\Elements;

use WPStaging\Core\Forms\ElementsWithOptions;





class Select extends ElementsWithOptions
{




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




    public function render()
    {
        return ($this->renderFile) ? @file_get_contents($this->renderFile) : $this->prepareOutput();
    }
}
