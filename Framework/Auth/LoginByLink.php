<?php

namespace WPStaging\Framework\Auth;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Staging\Sites;
use WPStaging\Framework\SiteInfo;

require_once ABSPATH . 'wp-admin/includes/user.php';
require_once ABSPATH . 'wp-admin/includes/ms.php';

/**
 * @package WPStaging\Framework\Auth
 */
class LoginByLink
{
    /** @var string */
    const LOGIN_LINK_PREFIX = 'wpstg_login_link_';

    /** @var string */
    const WPSTG_ROUTE_NAMESPACE_V1 = 'wpstg-routes/v1';

    /** @var string */
    const WPSTG_VISITOR_ROLE = 'wpstg_visitor';

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
        static $isRegistered = false;
        if ($isRegistered) {
            return;
        }

        $siteInfo = WPStaging::make(SiteInfo::class);
        if ($siteInfo->isStagingSite()) {
            add_action("init", [$this, "loginUserByLink"]);
            add_action("init", [$this, "disconnectNonExistingUser"]);
        }

        add_action('rest_api_init', [$this, 'registerRestRoutes']);

        $isRegistered = true;
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
        if (!isset($_GET['wpstg_login'])) {
            return;
        }

        $loginID = sanitize_text_field($_GET['wpstg_login']);
        $login   = self::LOGIN_LINK_PREFIX . $loginID;
        $user    = get_user_by('login', $login);
        $userId  = null;
        if (is_object($user)) {
            $userId = $user->ID;
        }

        if (!in_array($loginID, $this->loginLinkData, true)) {
            $this->cleanExistingLoginData($userId);
            wp_die('This link is invalid, please contact the administrator. Error code: 101');
        }

        if (empty($this->loginLinkData['expiration'])) {
            $this->cleanExistingLoginData($userId);
            wp_die('This link is invalid, please contact the administrator. Error code: 102');
        }

        if (time() > $this->loginLinkData['expiration']) {
            $this->cleanExistingLoginData($userId);
            wp_die('This link is invalid, please contact the administrator. Error code: 103');
        }

        if (empty($userId)) {
            $userId = wp_insert_user([
                'user_login' => $login,
                'user_pass'  => uniqid('wpstg'),
                'role'       => $this->loginLinkData['role'],

            ]);

            if (is_wp_error($userId)) {
                wp_die('Error creating user. Error: ' . $userId->get_error_message());
            }
        }

        if (!is_int($userId)) {
            wp_die('Something went wrong, please contact the administrator.');
        }

        wp_clear_auth_cookie();
        wp_set_current_user($userId);
        wp_set_auth_cookie($userId);

        if ($this->loginLinkData['role'] === self::WPSTG_VISITOR_ROLE) {
            wp_redirect(home_url());
            return;
        }

        wp_redirect(admin_url());
    }

    /**
     * @return void
     */
    public function disconnectNonExistingUser()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $currentUser = wp_get_current_user();
        $userData    = $currentUser->data;
        $loginID     = strpos($userData->user_login, self::LOGIN_LINK_PREFIX) === 0
            ? substr($userData->user_login, strlen(self::LOGIN_LINK_PREFIX))
            : false;

        if (empty($loginID)) {
            return;
        }

        if (!is_array($this->loginLinkData)) {
            return;
        }

        if (!in_array($loginID, $this->loginLinkData, true)) {
            return;
        }

        if (time() <= $this->loginLinkData['expiration']) {
            return;
        }

        $this->deleteUser($userData->ID);
        wp_logout();
    }

    /**
     * @param  int $userID
     * @return void
     */
    private function deleteUser(int $userID)
    {
        if (is_multisite()) {
            wpmu_delete_user($userID);
        } else {
            wp_delete_user($userID);
        }
    }

    /**
     * @param  int|null $userId
     * @return void
     */
    private function cleanExistingLoginData($userId)
    {
        if (is_int($userId)) {
            $this->deleteUser($userId);
        }

        delete_option(Sites::STAGING_LOGIN_LINK_SETTINGS);
    }
}
