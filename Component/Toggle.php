<?php

namespace WPStaging\Component;















class Toggle
{















    public function render(string $id, string $name, string $value = '', bool $isChecked = false, array $attributes = [], array $dataAttributes = [])
    {
        $classes           = isset($attributes['classes']) ? $attributes['classes'] : '';
        $onChange          = isset($attributes['onChange']) ? $attributes['onChange'] : '';
        $isDisabled        = isset($attributes['isDisabled']) ? $attributes['isDisabled'] : false;
        $dataId            = isset($dataAttributes['id']) ? $dataAttributes['id'] : '';
 
        require trailingslashit(WPSTG_VIEWS_DIR) . 'components/toggle.php';
    }
}
