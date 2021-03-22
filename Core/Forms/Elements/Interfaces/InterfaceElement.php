<?php

namespace WPStaging\Core\Forms\Elements\Interfaces;

/**
 * Interface InterfaceElement
 * @package WPStaging\Core\Forms\Elements\Interfaces
 */
interface InterfaceElement
{

    /**
     * @param string $name
     * return $this
     */
    public function setName($name);

    /**
     * @return null|string
     */
    public function getName();

    /**
     * @param string $name
     * @param string $value
     * return $this
     */
    public function setAttribute($name, $value);

    /**
     * @param array $attributes
     * return $this
     */
    public function setAttributes($attributes);

    /**
     * @return string
     */
    public function prepareAttributes();

    /**
     * @return array
     */
    public function getAttributes();

    /**
     * @param string $label
     * return $this
     */
    public function setLabel($label);

    /**
     * @return null|string
     */
    public function getLabel();

    /**
     * @return string
     */
    public function prepareLabel();

    /**
     * @param array|string $filters
     * return $this
     */
    public function setFilters($filters);

    /**
     * @return array
     */
    public function getFilters();

    /**
     * @param string $value
     * return $this
     */
    public function setDefault($value);

    /**
     * @return null|string
     */
    public function getDefault();

    /**
     * @param object $validation
     * return $this
     */
    public function addValidation($validation);

    /**
     * @return array
     */
    public function getValidations();

    /**
     * @param string $file
     * return $this
     */
    public function setRenderFile($file);

    /**
     * @return string
     */
    public function getRenderFile();

    /**
     * @param null|string $name
     * @return string
     */
    public function getId($name = null);

    /**
     * @return string
     */
    public function __toString();

    /**
     * @return mixed
     */
    public function render();
}
