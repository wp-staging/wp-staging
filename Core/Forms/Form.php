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

    /**
     * Add an element to the form.
     *
     * @param InterfaceElement|InterfaceElementWithOptions $element
     * @param string $id
     */
    public function add($element, $id)
    {
        if (!($element instanceof InterfaceElement) && !($element instanceof InterfaceElementWithOptions)) {
            return;
        }

        $element->setId($id);

        $this->elements[$element->getName()] = $element;
    }

    /**
     * Render an element by name.
     *
     * @param string $name
     *
     * @return string|false
     */
    public function render($name)
    {
        if (!isset($this->elements[$name])) {
            return false;
        }

        return $this->elements[$name]->render();
    }

    /**
     * Prepare the label for an element by name.
     *
     * @param string $name
     *
     * @return string|false
     */
    public function label($name)
    {
        if (!isset($this->elements[$name])) {
            return false;
        }

        return $this->elements[$name]->prepareLabel();
    }

    /**
     * Render the label for an element by name.
     *
     * @param string $name
     */
    public function renderLabel($name)
    {
        echo wp_kses($this->label($name), ['label' => ['for' => []]]);
    }

    /**
     * Render the input for an element by name.
     *
     * @param string $name
     */
    public function renderInput($name)
    {
        echo $this->render($name);
    }
}
