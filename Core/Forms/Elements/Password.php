<?php
namespace WPStaging\Forms\Elements;

use WPStaging\Forms\Elements;

/**
 * Class Password
 * @package WPStaging\Forms\Elements
 */
class Password extends Elements
{

    /**
     * @return string
     */
    protected function prepareOutput()
    {
        return "<input id='{$this->getId()}' name='{$this->getName()}' type='password' {$this->prepareAttributes()} value='{$this->default}' />";
    }

    /**
     * @return string
     */
    public function render()
    {
        return ($this->renderFile) ? @file_get_contents($this->renderFile) : $this->prepareOutput();
    }
}