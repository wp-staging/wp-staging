<?php

namespace WPStaging\Component;

class Alert
{














    public function render(string $title = '', string $desc = '', string $buttonText = '', string $buttonUrl = '', bool $closeable = false, array $attributes = [])
    {
        $style   = isset($attributes['style']) ? $attributes['style'] : '';
        $class   = isset($attributes['class']) ? $attributes['class'] : '';
        $id      = isset($attributes['id']) ? $attributes['id'] : '';
        $variant = isset($attributes['variant']) ? $attributes['variant'] : 'danger';
 
        require trailingslashit(WPSTG_VIEWS_DIR) . 'components/alert.php';
    }




    public function renderCloseable()
    {
        $attr = [
            'style' => 'display: none;',
        ];
        $this->render('', '', '', '', true, $attr);
    }
}
