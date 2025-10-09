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
     * @param bool $visible
     * @return void
     */
    public function render(string $title = '', string $desc = '', string $buttonText = '', string $buttonUrl = '', bool $closeable = false, bool $visible = true)
    {
        /** @noinspection PhpIncludeInspection */
        require trailingslashit(WPSTG_VIEWS_DIR) . 'components/alert.php';
    }

    /**
     * @return void
     */
    public function renderCloseable()
    {
        $this->render('', '', '', '', true, false);
    }
}
