<?php

namespace WPStaging\Core\Forms\Elements;

use WPStaging\Core\Forms\Elements;

/**
 * Class Color
 * @package WPStaging\Core\Forms\Elements
 */
class Color extends Elements
{

    /**
     * @return string
     */
    protected function prepareOutput()
    {
        return "<input id='{$this->getId()}' name='{$this->getName()}' type='color' {$this->prepareAttributes()} value='{$this->default}' />";
    }

    /**
     * @return string
     */
    public function render()
    {
        return ($this->renderFile) ? @file_get_contents($this->renderFile) : $this->prepareOutput();
    }
}
