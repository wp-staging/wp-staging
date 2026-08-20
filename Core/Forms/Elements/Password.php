<?php

namespace WPStaging\Core\Forms\Elements;

use WPStaging\Core\Forms\Elements;





class Password extends Elements
{




    protected function prepareOutput()
    {
        return "<input id='{$this->getId()}' name='{$this->getName()}' type='password' {$this->prepareAttributes()} value='" . esc_attr($this->default) . "' />";
    }




    public function render()
    {
        return ($this->renderFile) ? @file_get_contents($this->renderFile) : $this->prepareOutput();
    }
}
