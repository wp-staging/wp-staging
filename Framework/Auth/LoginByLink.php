<?php

namespace WPStaging\Framework\Auth;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Staging\Sites;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Adapter\SourceDatabase;

/**
 * @package WPStaging\Framework\Auth
 */
class LoginByLink
{
    /** @var string */
    const LOGIN_LINK_PREFIX = 'wpstg_login_link_';

    /** @var string */
    const WPSTG_ROUTE_NAMESPACE_V1 = 'wpstg-routes/v1';

    /**
     * @var array
     */
    private $loginLinkData;

    public function __construct()
    {
        $this->loginLinkData = get_option(Sites::STAGING_LOGIN_LINK_SETTINGS, []);
        $this->defineHooks();
    }

    /**
     * Define Hooks
     */
    private function defineHooks()
    {
        $siteInfo = WPStaging::make(SiteInfo::class);
        if ($siteInfo->isStagingSite()) {
            add_action("init", [$this, "loginUserByLink"]);
            add_action("init", [$this, "disconnectUnexistUser"]);
        }

        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        add_action('wpstg_clean_login_link_data', [$this, 'cleanLoginInfo'], 10, 1);
    }

    /**
     * Register routes for endpoints.
     *
     * @return void
     */
    public function registerRestRoutes()
    {
        register_rest_route(self::WPSTG_ROUTE_NAMESPACE_V1, '/check_magic_login', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'canUseMagicLogicEndpoint'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * @return \WP_REST_Response|\WP_Error
     */
    public function canUseMagicLogicEndpoint()
    {
        return rest_ensure_response(true);
    }

    /**
     * @return void
     */
    public function loginUserByLink()
    {
        if (!isset($_GET['wpstg_login']) || !$this->loginLinkData) {
            return;
        }

        $loginID = sanitize_text_field($_GET['wpstg_login']);
        if (!in_array($loginID, $this->loginLinkData, true)) {
            wp_die();
        }

        $login = self::LOGIN_LINK_PREFIX . $loginID;
        $user  = get_user_by('login', $login);
        if ($user) {
            $userId = $user->ID;
        } else {
            $userId = wp_insert_user([
                'user_login' => $login,
                'user_pass'  => uniqid('wpstg'),
                'role'       => $this->loginLinkData['role'],

            ]);
            if (is_wp_error($userId)) {
                wp_die();
            }
        }

        wp_clear_auth_cookie();
        wp_set_current_user($userId);
        wp_set_auth_cookie($userId);
        wp_redirect(admin_url());
    }

    /**
     * @return void
     */
    public function disconnectUnexistUser()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $currentUser = wp_get_current_user();
        $userData    = $currentUser->data;
        $loginID     = strpos($userData->user_login, self::LOGIN_LINK_PREFIX) === 0
            ? substr($userData->user_login, strlen(self::LOGIN_LINK_PREFIX))
            : false;
        if (empty($loginID) || ($this->loginLinkData && in_array($loginID, $this->loginLinkData, true))) {
            return;
        }

        if (function_exists('wp_delete_user')) {
            wp_delete_user($userData->ID);
        }

        wp_logout();
    }

    /**
     * Clean login information from staging sites
     *
     * @param  mixed $cloneID
     * @return void
     */
    public function cleanLoginInfo($cloneID)
    {
        $existingClones = get_option(Sites::STAGING_SITES_OPTION, []);
        if (!isset($existingClones[$cloneID])) {
            return;
        }

        $currentClone = $existingClones[$cloneID];

        $cloneDB           = (new SourceDatabase((object)$currentClone))->getDatabase();
        $cloneOptionsTable = $currentClone['prefix'] . 'options';
        $results           = $cloneDB->get_results("SELECT * FROM {$cloneOptionsTable}  WHERE option_name = '" . Sites::STAGING_LOGIN_LINK_SETTINGS . "'");
        if (!empty($results)) {
            $cloneDB->delete(
                $currentClone['prefix'] . 'options',
                [
                    'option_name' => Sites::STAGING_LOGIN_LINK_SETTINGS
                ]
            );
            $optionData = maybe_unserialize(current($results)->option_value);
            $cloneDB->delete(
                $currentClone['prefix'] . 'users',
                [
                    'user_login' => 'wpstg_' . $optionData['loginID'],
                ]
            );
        }

        wp_clear_scheduled_hook('wpstg_clean_login_link_data', [$cloneID]);
    }
}
