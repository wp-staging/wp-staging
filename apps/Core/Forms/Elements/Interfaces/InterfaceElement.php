<?php
namespace WPStaging\Forms\Elements\Interfaces;

interface InterfaceElement
{
    /**
     * @param string $name
     */
    public function setName($name);

    /**
     * @return null|string
     */
    public function getName();

    /**
     * @param string $name
     * @param string $value
     */
    public function setAttribute($name, $value);

    /**
     * @param array $attributes
     */
    public function setAttributes($attributes);

    /**
     * @return array
     */
    public function getAttributes();

    /**
     * @param string $label
     */
    public function setLabel($label);

    /**
     * @return null|string
     */
    public function getLabel();

    /**
     * @param array|string $filters
     */
    public function setFilters($filters);

    /**
     * @return array
     */
    public function getFilters();

    /**
     * @return mixed
     */
    public function render();
}