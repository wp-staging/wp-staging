<?php

namespace WPStaging\Frontend;

use WPStaging\DI\InjectionAware;
use WPStaging\Frontend\loginForm;

/**
 * Class Frontend
 * @package WPStaging\Frontend
 */
class Frontend extends InjectionAware
{

    /**
     * @var object
     */
    private $settings;

    /**
     * Frontend initialization.
     */
    public function initialize()
    {
        $this->defineHooks();

        $this->settings = json_decode(json_encode(get_option("wpstg_settings", array())));

    }

    /**
     * Define Hooks
     */
    private function defineHooks()
    {
        // Get loader
        $loader = $this->di->get("loader");
        $loader->addAction("init", $this, "checkPermissions");
        $loader->addFilter("wp_before_admin_bar_render", $this, "changeSiteName");
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
        if ($this->isStagingSite()) {
            // Main Title
            $wp_admin_bar->add_menu(array(
                'id' => 'site-name',
                'title' => is_admin() ? ('STAGING - ' . get_bloginfo('name')) : ('STAGING - ' . get_bloginfo('name') . ' Dashboard'),
                'href' => is_admin() ? home_url('/') : admin_url(),
            ));
        }
    }

    /**
     * Check permissions for the page to decide whether or not to disable the page
     */
    public function checkPermissions()
    {
        $this->resetPermaLinks();

        if ($this->isLoginRequired()) {

            $args = array(
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
            );


            /**
             * Lines below are not used at the moment but are fully functional
             */
            $login = new loginForm();
            $login->renderForm($args);
            die();
        }
    }

    /**
     * Get path to wp-login.php
     * @return string
     */
    private function getLoginUrl()
    {
        return get_site_url() . '/wp-login.php';
    }

    /**
     * Check if the page should be blocked
     * @return bool
     */
    private function isLoginRequired()
    {

        if ($this->isLoginPage() || is_admin()) {
            return false;
        }

        if (!wpstg_is_stagingsite()) {
            return false;
        }

        // Allow access for administrator
        if (current_user_can('manage_options')) {
            return false;
        }

        // Simple check (free version only)
        if (!defined('WPSTGPRO_VERSION')) {
            return (!isset($this->settings->disableAdminLogin) || '1' !== $this->settings->disableAdminLogin);
        }

        // Allow access for wp staging user role "all"
        if (!empty($this->settings->userRoles) && in_array('all', $this->settings->userRoles)) {
            return false;
        }

        // Allow access only for administratorss if no user roles are defined
        if (!isset($this->settings->userRoles) || !is_array($this->settings->userRoles)) {
            return true;
        }


        // Disable access if current user is not allowed
        $currentUser = wp_get_current_user();
        $userRoles = $currentUser->roles;

        $result = isset($this->settings->userRoles) && is_array($this->settings->userRoles) ? array_intersect($userRoles, $this->settings->userRoles) : array();
        if (empty($result) && !$this->isLoginPage() && !is_admin()) {
            return true;
        }

    }

    /**
     * Check if it is a staging site
     * @return bool
     */
    private function isStagingSite()
    {
        return ("true" === get_option("wpstg_is_staging_site"));
    }

    /**
     * Check if it is the login page
     * @return bool
     */
    private function isLoginPage()
    {

        return (in_array($GLOBALS["pagenow"], array("wp-login.php")));
    }

    /**
     * Reset permalink structure of the clone to default; index.php?p=123
     */
    private function resetPermaLinks()
    {
        // Do nothing
        if (!$this->isStagingSite() || "true" === get_option("wpstg_rmpermalinks_executed")) {
            return;
        }

        // Do nothing
        if (defined('WPSTGPRO_VERSION')) {
            if (isset($this->settings->keepPermalinks) && $this->settings->keepPermalinks === "1") {
                return;
            }
        }

        // $wp_rewrite is not available before the init hook. So we need to use the global variable
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure(null);

        flush_rewrite_rules();

        update_option("wpstg_rmpermalinks_executed", "true");
    }

}