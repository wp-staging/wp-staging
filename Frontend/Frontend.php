<?php

namespace WPStaging\Frontend;

use WPStaging\Framework\SiteInfo;

/**
 * Class Frontend
 * @package WPStaging\Frontend
 */
class Frontend
{

    /**
     * @var object
     */
    private $settings;

    /**
     * @var bool
     */
    private $accessDenied = false;

    public function __construct()
    {
        $this->defineHooks();

        $this->settings = json_decode(json_encode(get_option("wpstg_settings", [])));
    }

    /**
     * Define Hooks
     */
    private function defineHooks()
    {
        add_action("init", [$this, "checkPermissions"]);
        add_filter("wp_before_admin_bar_render", [$this, "changeSiteName"]);
    }

    /**
     * Change admin_bar site_name
     *
     * @return void
     * @global object $wp_admin_bar
     */
    public function changeSiteName()
    {
        global $wp_admin_bar;
        $siteTitle = apply_filters('wpstg_staging_site_title', 'STAGING');
        if ($this->isStagingSite()) {
            // Main Title
            $wp_admin_bar->add_menu(
                [
                    'id' => 'site-name',
                    'title' => is_admin() ? ($siteTitle . ' - ' . get_bloginfo('name')) : ($siteTitle . ' - ' . get_bloginfo('name') . ' Dashboard'),
                    'href' => is_admin() ? home_url('/') : admin_url(),
                ]
            );
        }
    }

    /**
     * Check permissions for the page to decide whether or not to disable the page
     */
    public function checkPermissions()
    {
        $this->resetPermaLinks();

        if ($this->showLoginForm()) {

            $args = [
                'echo' => true,
                // Default 'redirect' value takes the user back to the request URI.
                'redirect' => (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                'form_id' => 'loginform',
                'label_username' => __('Username or Email Address'),
                'label_password' => __('Password'),
                'label_remember' => __('Remember Me'),
                'label_log_in' => __('Log In'),
                'id_username' => 'user_login',
                'id_password' => 'user_pass',
                'id_remember' => 'rememberme',
                'id_submit' => 'wp-submit',
                'remember' => true,
                'value_username' => '',
                // Set 'value_remember' to true to default the "Remember me" checkbox to checked.
                'value_remember' => false,
            ];

            $login = new LoginForm();
            if ($this->accessDenied) {
                wp_logout();
                $login->setError(__('Access Denied'));
            }
            $login->renderForm($args);
            die();
        }
    }


    /**
     * Show a login form if user is not authorized
     * @return bool
     */
    private function showLoginForm()
    {
        $this->accessDenied = false;

        if ($this->isLoginPage() || is_admin()) {
            return false;
        }

        if (! $this->isStagingSite() ) {
            return false;
        }

        // Allow access for administrator
        if (current_user_can('manage_options')) {
            return false;
        }

        // Simple check (free version only)
        if (!defined('WPSTGPRO_VERSION')) {
            return (!isset($this->settings->disableAdminLogin) || $this->settings->disableAdminLogin !== '1');
        }

        // Allow access for wp staging user role "all"
        if (!empty($this->settings->userRoles) && in_array('all', $this->settings->userRoles)) {
            return false;
        }

        if (defined('WPSTGPRO_VERSION') && !empty($this->settings->usersWithStagingAccess)) {
            $usersWithStagingAccess = explode(',', $this->settings->usersWithStagingAccess);
            if (in_array(wp_get_current_user()->user_login, $usersWithStagingAccess, true)) {
                return false;
            }
        }

        if( !is_user_logged_in() ) {
            return true;
        }

        // Allow access for administrators if no user roles are defined
        if (!isset($this->settings->userRoles) || !is_array($this->settings->userRoles)) {
            $this->accessDenied = true;
            return true;
        }

        // Require login form if user is not in specific user role
        $currentUser = wp_get_current_user();
        $activeUserRoles = $currentUser->roles;

        $result = isset($this->settings->userRoles) && is_array($this->settings->userRoles) ?
            array_intersect($activeUserRoles, $this->settings->userRoles) :
            [];

        if (empty($result) && !$this->isLoginPage() && !is_admin()) {
            $this->accessDenied = true;
            return true;
        }

        // Don't show login form if no other rule apply
        return false;
    }

    /**
     * Check if it is a staging site
     * @return bool
     */
    private function isStagingSite()
    {
        return (new SiteInfo())->isStaging();
    }

    /**
     * Check if it is the login page
     * @return bool
     */
    private function isLoginPage()
    {
        return ($GLOBALS["pagenow"] === "wp-login.php");
    }

    /**
     * Reset permalink structure of the clone to default; index.php?p=123
     */
    private function resetPermaLinks()
    {
        // Do nothing
        if (!$this->isStagingSite() || get_option("wpstg_rmpermalinks_executed") === "true") {
            return;
        }

        // Do nothing
        if (defined('WPSTGPRO_VERSION') && isset($this->settings->keepPermalinks) && $this->settings->keepPermalinks === "1") {
            return;
        }

        // $wp_rewrite is not available before the init hook. So we need to use the global variable
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure(null);

        flush_rewrite_rules();

        update_option("wpstg_rmpermalinks_executed", "true");
    }

}
