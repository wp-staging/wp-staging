<?php
namespace WPStaging\Forms;

use WPStaging\Forms\Elements\Interfaces\InterfaceElement;
use WPStaging\Forms\Elements\Interfaces\InterfaceElementWithOptions;

/**
 * Class Form
 * @package WPStaging\Forms
 */
class Form
{

    protected $elements = array();

    public function __construct()
    {

    }

    public function add($element)
    {
        if (!($element instanceof InterfaceElement) && !($element instanceof InterfaceElementWithOptions))
        {
            return;
        }

        $this->elements[$element->getName()] = $element;
    }

    public function render($name)
    {
        if (!isset($this->elements[$name]))
        {
            return false;
        }

        return $this->elements[$name]->render();
    }

    public function label($name)
    {
        if (!isset($this->elements[$name]))
        {
            return false;
        }

        return $this->elements[$name]->prepareLabel();
    }
}