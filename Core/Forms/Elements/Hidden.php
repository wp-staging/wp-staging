<?php

namespace WPStaging\Core\Forms\Elements;

use WPStaging\Core\Forms\Elements;





class Hidden extends Elements
{




    protected function prepareOutput()
    {
        return "<input id='{$this->getId()}' name='{$this->getName()}' type='hidden' {$this->prepareAttributes()} value='{$this->default}' />";
    }




    public function render()
    {
        return ($this->renderFile) ? @file_get_contents($this->renderFile) : $this->prepareOutput();
    }
}
