<?php

namespace WPStaging\Core\Forms\Elements\Interfaces;

/**
 * Interface InterfaceElementWithOptions
 * @package WPStaging\Core\Forms\Elements\Interfaces
 */
interface InterfaceElementWithOptions
{

    /**
     * @param string $id
     * @param string $name
     * @return void
     */
    public function addOption($id, $name);

    /**
     * @param string $id
     * @return void
     */
    public function removeOption($id);

    /**
     * @param array $options
     * @return void
     */
    public function addOptions($options);

    /**
     * @return array
     */
    public function getOptions();
}
