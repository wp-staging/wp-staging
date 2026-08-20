<?php

namespace WPStaging\Core\Forms\Elements;

use WPStaging\Core\Forms\Elements;





class TextArea extends Elements
{




    protected function prepareOutput()
    {
        return "<textarea id='{$this->getId()}' name='{$this->getName()}' {$this->prepareAttributes()}>{$this->default}</textarea>";
    }




    public function render()
    {
        return ($this->renderFile) ? @file_get_contents($this->renderFile) : $this->prepareOutput();
    }
}
