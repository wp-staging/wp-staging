<?php

namespace WPStaging\Core\Forms;

use WPStaging\Core\Forms\Elements\Interfaces\InterfaceElement;
use WPStaging\Core\Forms\Elements\Interfaces\InterfaceElementWithOptions;

/**
 * Class Form
 * @package WPStaging\Core\Forms
 */
class Form
{

    protected $elements = [];

    public function __construct()
    {
    }

    public function add($element)
    {
        if (!($element instanceof InterfaceElement) && !($element instanceof InterfaceElementWithOptions)) {
            return;
        }

        $this->elements[$element->getName()] = $element;
    }

    public function render($name)
    {
        if (!isset($this->elements[$name])) {
            return false;
        }

        return $this->elements[$name]->render();
    }

    public function label($name)
    {
        if (!isset($this->elements[$name])) {
            return false;
        }

        return $this->elements[$name]->prepareLabel();
    }

    /** @param string $name */
    public function renderLabel($name)
    {
        echo wp_kses($this->label($name), ['label' => []]);
    }

    /** @param string $name */
    public function renderInput($name)
    {
        echo $this->render($name);
    }
}
