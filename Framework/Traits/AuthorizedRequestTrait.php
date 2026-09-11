<?php

namespace WPStaging\Framework\Traits;







trait AuthorizedRequestTrait
{



    private function requireAuthorizedRequest()
    {
        if (!$this->accessToken->requestHasValidToken()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized', 'wp-staging')], 401);
        }

        if (!current_user_can($this->capabilities->manageWPSTG())) {
            wp_send_json_error(['message' => esc_html__('Forbidden', 'wp-staging')], 403);
        }
    }
}
