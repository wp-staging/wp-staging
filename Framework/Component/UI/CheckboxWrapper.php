<?php

namespace WPStaging\Framework\Component\UI;

use WPStaging\Core\Forms\Elements\Check;
use WPStaging\Framework\Facades\Escape;

class CheckboxWrapper
{
    /**
     * @var Check|null
     */
    protected $checkbox = null;

    public function __construct()
    {
        $this->checkbox = new Check('');
    }

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
     *   'dirType' => string,
     *   'path' => string,
     *   'prefix' => string,
     *   'path' => bool,
     *   'isScanned' => bool,
     *   'isNavigatable' => bool,
     *   'deletePath'    => string,
     *  ]
     * @param bool $returnAsString
     * @return string|void
     */
    public function render(string $id, string $name, string $value = '', bool $isChecked = false, array $attributes = [], array $dataAttributes = [], bool $returnAsString = false)
    {
        $classes    = isset($attributes['classes']) ? $attributes['classes'] : '';
        $onChange   = isset($attributes['onChange']) ? $attributes['onChange'] : '';
        $isDisabled = isset($attributes['isDisabled']) ? $attributes['isDisabled'] : false;

        $dataId      = isset($dataAttributes['id']) ? $dataAttributes['id'] : '';
        $dataDirType = isset($dataAttributes['dirType']) ? $dataAttributes['dirType'] : '';
        $dataPath    = isset($dataAttributes['path']) ? $dataAttributes['path'] : '';
        $dataPrefix  = isset($dataAttributes['prefix']) ? $dataAttributes['prefix'] : '';
        $dataDeletePath  = isset($dataAttributes['deletePath']) ? $dataAttributes['deletePath'] : '';

        $isDataScanned     = isset($dataAttributes['isScanned']) ? $dataAttributes['isScanned'] : false;
        $isDataNavigatable = isset($dataAttributes['isNavigatable']) ? $dataAttributes['isNavigatable'] : false;

        if ($returnAsString) {
            ob_start();

            /** @noinspection PhpIncludeInspection */
            require trailingslashit(WPSTG_PLUGIN_DIR) . 'Framework/Component/UI/checkbox.php';
            return ob_get_clean();
        }

        /** @noinspection PhpIncludeInspection */
        require trailingslashit(WPSTG_PLUGIN_DIR) . 'Framework/Component/UI/checkbox.php';
    }
}
