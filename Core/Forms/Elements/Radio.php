<?php

namespace WPStaging\Core\Forms\Elements;

use WPStaging\Core\Forms\ElementsWithOptions;





class Radio extends ElementsWithOptions
{




    protected function prepareOutput()
    {
        $output = '';

        foreach ($this->options as $id => $value) {
            $checked = ($this->default && $this->default === $value) ? " checked=''" : '';

            $attributeId = $this->getId($id);

            $output .= "<input type='radio' name='{$this->getId()}' id='{$attributeId}' value='{$id}' {$checked}/>";
            $output .= "<label for='{$attributeId}'>{$value}</label>";
        }

        return $output;
    }




    public function render()
    {
        return ($this->renderFile) ? @file_get_contents($this->renderFile) : $this->prepareOutput();
    }
}
