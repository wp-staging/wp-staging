<?php

namespace WPStaging\Core\Forms\Elements;

use WPStaging\Core\Forms\Elements;

/**
 * Class TextArea
 * @package WPStaging\Core\Forms\Elements
 */
class TextArea extends Elements
{

    /**
     * @return string
     */
    protected function prepareOutput()
    {
        return "<textarea id='{$this->getId()}' name='{$this->getName()}' {$this->prepareAttributes()}>{$this->default}</textarea>";
    }

    /**
     * @return string
     */
    public function render()
    {
        return ($this->renderFile) ? @file_get_contents($this->renderFile) : $this->prepareOutput();
    }
}
