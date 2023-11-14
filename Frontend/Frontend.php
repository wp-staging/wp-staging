<?php

namespace WPStaging\Frontend;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Rest\Rest;
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

    /** @var LoginForm  */
    private $loginForm;

    public function __construct()
    {
        $this->defineHooks();

        $this->settings = json_decode(json_encode(get_option("wpstg_settings", [])));

        $this->loginForm = WPStaging::make(LoginForm::class);
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
                    'id'    => 'site-name',
                    'title' => is_admin() ? ($siteTitle . ' - ' . get_bloginfo('name')) : ($siteTitle . ' - ' . get_bloginfo('name') . ' Dashboard'),
                    'href'  => is_admin() ? home_url('/') : admin_url(),
                ]
            );
        }
    }

    /**
     * Check permissions for the page to decide whether to disable the page
     * @return void
     */
    public function checkPermissions()
    {
        $this->resetPermaLinks();

        if ($this->showLoginForm()) {
            if ($this->accessDenied) {
                wp_logout();
                $this->loginForm->setError(__('Access Denied', 'wp-staging'));
            }

            $overrides = [
                'label_username' => __('Username or Email Address', 'wp-staging'),
            ];
            $this->loginForm->renderForm($this->loginForm->getDefaultArguments($overrides));
            die();
        }
    }

    /**
     * Define Hooks
     * @return void
     */
    private function defineHooks()
    {
        add_action("init", [$this, "checkPermissions"]);
        add_filter("wp_before_admin_bar_render", [$this, "changeSiteName"]);
    }

    /**
     * Show a login form if user is not authorized
     * @return bool
     */
    private function showLoginForm(): bool
    {
        $this->accessDenied = false;

        // Don't show login form if from wp-cli
        if ('cli' === PHP_SAPI && defined('WP_CLI')) {
            return false;
        }

        // Don't show login form if showLoginForm filter is set to false. Used by Real Cookie Banner plugin
        if (apply_filters('wpstg.frontend.showLoginForm', false)) {
            return false;
        }

        // Don't show login form for rest requests

        /** @var Rest $rest */
        $rest = WPStaging::make(Rest::class);
        if ($rest->isRestUrl()) {
            return false;
        }

        if ($this->isLoginPage() || is_admin()) {
            return false;
        }

        if (! $this->isStagingSite()) {
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

        if (!is_user_logged_in()) {
            return true;
        }

        // Allow access for administrators if no user roles are defined
        if (!isset($this->settings->userRoles) || !is_array($this->settings->userRoles)) {
            $this->accessDenied = true;
            return true;
        }

        $currentUser = wp_get_current_user();

        if (defined('WPSTGPRO_VERSION') && !empty($this->settings->usersWithStagingAccess)) {
            $usersWithStagingAccess = explode(',', $this->settings->usersWithStagingAccess);

            // check against usernames
            if (in_array($currentUser->user_login, $usersWithStagingAccess, true)) {
                return false;
            }

            // check against emails
            if (in_array($currentUser->user_email, $usersWithStagingAccess, true)) {
                return false;
            }
        }

        // Require login form if user is not in specific user role
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
    private function isStagingSite(): bool
    {
        return (new SiteInfo())->isStagingSite();
    }

    /**
     * Check if it is the login page
     * @return bool
     */
    private function isLoginPage(): bool
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
