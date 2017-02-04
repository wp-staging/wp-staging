<?php
namespace WPStaging\Forms;

use WPStaging\Forms\Elements\Interfaces\InterfaceElement;

/**
 * Class Elements
 * @package WPStaging\Forms
 */
abstract class Elements implements InterfaceElement
{
    /**
     * @var null|string
     */
    private $name;

    /**
     * @var array
     */
    private $attributes = array();

    /**
     * @var null|string
     */
    private $label;

    /**
     * @var array
     */
    private $filters = array();

    /**
     * Text constructor.
     * @param string $name
     * @param array $attributes
     */
    public function __construct($name, $attributes)
    {
        $this->setName($name);
        $this->setAttributes($attributes);
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
       return $this->name;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        foreach ($attributes as $name => $value)
        {
            $this->setAttribute($name, $value);
        }
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return null|string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param array|string $filters
     */
    public function setFilters($filters)
    {
        if (is_string($filters))
        {
            $this->filters[] = $filters;
        }
        else
        {
            array_merge($this->filters, $filters);
        }
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @return mixed
     */
    abstract public function render();
}