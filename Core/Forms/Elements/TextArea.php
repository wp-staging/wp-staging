<?php
namespace WPStaging\Forms\Elements;

use WPStaging\Forms\Elements;

/**
 * Class TextArea
 * @package WPStaging\Forms\Elements
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