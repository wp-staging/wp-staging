<?php

// TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Framework\Component;

trait AjaxTrait
{
    public function isAjax()
    {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

    /**
     * @param string|null $action
     * @param string|null $key
     *
     * @return bool
     */
    public function isSecureAjax($action = null, $key = null)
    {
        $_action = $action;
        if (null === $_action) {
            $_action = -1;
        }

        $_key = $key;
        if (null === $_key) {
            $_key = false;
        }

        return $this->isAjax() && (bool) check_ajax_referer($_action, $_key, false);
    }
}
