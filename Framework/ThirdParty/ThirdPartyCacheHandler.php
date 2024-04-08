<?php

namespace WPStaging\Framework\ThirdParty;

/**
 * Class ThirdPartyCacheHandler
 *
 * This class handles third party cache system
 *
 * @package WPStaging\Framework\ThirdParty;
 */
class ThirdPartyCacheHandler
{
    public function purgeEnduranceCache()
    {
        if (is_user_logged_in()) {
            return;
        }

        if (!class_exists('Endurance_Page_Cache')) {
            return;
        }

        if (isset($_GET['epc_purge_all'])) {
            return;
        }

        $url = add_query_arg([
            'epc_purge_all' => true,
        ], home_url());

        wp_redirect($url);
        die;
    }
}
