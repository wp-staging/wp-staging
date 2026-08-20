<?php

namespace WPStaging\Framework\Security;








class Nonce
{





    const WPSTG_NONCE = 'wpstg_nonce';








    public function requestHasValidNonce($action)
    {
        return isset($_REQUEST['nonce']) && wp_verify_nonce(sanitize_text_field($_REQUEST['nonce']), $action) !== false;
    }
}
