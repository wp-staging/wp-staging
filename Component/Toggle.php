<?php

namespace WPStaging\Component;

/**
 * Renders toggle switch UI components for settings and forms
 *
 * This component generates HTML markup for toggle switches (on/off switches) used
 * throughout the WP Staging admin interface. It supports:
 * - Custom CSS classes and styling
 * - Checked/unchecked states
 * - Disabled state
 * - onChange event handlers
 * - Custom data attributes
 *
 * The component loads its template from the views directory and passes all
 * configuration to the template for rendering.
 */
class Toggle
{
    /**
     * @param string $id
     * @param string $name
     * @param string $value
     * @param bool $isChecked
     * @param array $attributes [
     *   'classes' => string,
     *   'onChange' => string,
     *   'isDisabled' => bool
     *  ]
     * @param array $dataAttributes [
     *   'id' => string,
     *  ]
     * @return void
     */
    public function render(string $id, string $name, string $value = '', bool $isChecked = false, array $attributes = [], array $dataAttributes = [])
    {
        $classes           = isset($attributes['classes']) ? $attributes['classes'] : '';
        $onChange          = isset($attributes['onChange']) ? $attributes['onChange'] : '';
        $isDisabled        = isset($attributes['isDisabled']) ? $attributes['isDisabled'] : false;
        $dataId            = isset($dataAttributes['id']) ? $dataAttributes['id'] : '';
         /** @noinspection PhpIncludeInspection */
        require trailingslashit(WPSTG_VIEWS_DIR) . 'components/toggle.php';
    }
}
