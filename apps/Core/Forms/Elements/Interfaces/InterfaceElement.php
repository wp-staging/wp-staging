<?php
namespace WPStaging\Forms\Elements\Interfaces;

/**
 * Interface InterfaceElement
 * @package WPStaging\Forms\Elements\Interfaces
 */
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
     * @return string
     */
    public function prepareAttributes();

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
     * @return string
     */
    public function prepareLabel();

    /**
     * @param array|string $filters
     */
    public function setFilters($filters);

    /**
     * @return array
     */
    public function getFilters();

    /**
     * @param string $value
     */
    public function setDefault($value);

    /**
     * @return null|string
     */
    public function getDefault();

    /**
     * @param object $validation
     */
    public function addValidation($validation);

    /**
     * @return array
     */
    public function getValidations();

    /**
     * @param string $file
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