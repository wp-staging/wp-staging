<?php

namespace WPStaging\Component;

class Alert
{
    /**
     * @param string $title
     * @param string $desc
     * @param string $buttonText
     * @param string $buttonUrl
     * @param bool $closeable
     * @param array $attributes [
     *   'style' => string,
     *   'class' => string,
     *   'id'    => string
     * ]
     * @return void
     */
    public function render(string $title = '', string $desc = '', string $buttonText = '', string $buttonUrl = '', bool $closeable = false, array $attributes = [])
    {
        $style = isset($attributes['style']) ? $attributes['style'] : '';
        $class = isset($attributes['class']) ? $attributes['class'] : '';
        $id    = isset($attributes['id']) ? $attributes['id'] : '';
        /** @noinspection PhpIncludeInspection */
        require trailingslashit(WPSTG_VIEWS_DIR) . 'components/alert.php';
    }

    /**
     * @return void
     */
    public function renderCloseable()
    {
        $attr = [
            'style' => 'display: none;',
        ];
        $this->render('', '', '', '', true, $attr);
    }
}
