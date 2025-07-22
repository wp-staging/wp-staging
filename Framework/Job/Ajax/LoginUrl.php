<?php

namespace WPStaging\Framework\Job\Ajax;

use WPStaging\Framework\Component\AbstractTemplateComponent;

class LoginUrl extends AbstractTemplateComponent
{
    /**
     * @return void
     */
    public function ajaxLoginUrl()
    {
        if (!$this->canRenderAjax()) {
            wp_send_json_error(null, 401);
        }

        wp_send_json_success(['loginUrl' => wp_login_url(null, true)]);
    }
}
