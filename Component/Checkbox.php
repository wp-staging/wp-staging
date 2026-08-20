<?php

namespace WPStaging\Component;

use WPStaging\Core\Forms\Elements\Check;

class Checkbox
{



    protected $checkbox = null;

    public function __construct()
    {
        $this->checkbox = new Check('');
    }

























    public function render(string $id, string $name, string $value = '', bool $isChecked = false, array $attributes = [], array $dataAttributes = [], bool $returnAsString = false)
    {
        $classes            = isset($attributes['classes']) ? $attributes['classes'] : '';
        $onChange           = isset($attributes['onChange']) ? $attributes['onChange'] : '';
        $isDisabled         = isset($attributes['isDisabled']) ? $attributes['isDisabled'] : false;
        $usePrimitive       = isset($attributes['usePrimitive']) ? $attributes['usePrimitive'] : false;
        $displayDependency  = isset($attributes['displayDependency']) ? $attributes['displayDependency'] : false;

        $dataId      = isset($dataAttributes['id']) ? $dataAttributes['id'] : '';
        $dataDirType = isset($dataAttributes['dirType']) ? $dataAttributes['dirType'] : '';
        $dataPath    = isset($dataAttributes['path']) ? $dataAttributes['path'] : '';
        $dataPrefix  = isset($dataAttributes['prefix']) ? $dataAttributes['prefix'] : '';
        $dataDeletePath  = isset($dataAttributes['deletePath']) ? $dataAttributes['deletePath'] : '';

        $isDataScanned     = isset($dataAttributes['isScanned']) ? $dataAttributes['isScanned'] : false;
        $isDataNavigatable = isset($dataAttributes['isNavigatable']) ? $dataAttributes['isNavigatable'] : false;

        if ($returnAsString) {
            ob_start();

 
            require trailingslashit(WPSTG_VIEWS_DIR) . 'components/checkbox.php';

            return ob_get_clean();
        }

 
        require trailingslashit(WPSTG_VIEWS_DIR) . 'components/checkbox.php';
    }
}
