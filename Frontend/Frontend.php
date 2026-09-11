<?php

namespace WPStaging\Frontend;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Rest\Rest;
use WPStaging\Framework\SiteInfo;

use function WPStaging\functions\debug_log;





class Frontend
{
 
    const FILTER_FRONTEND_SHOW_LOGIN_FORM = 'wpstg.frontend.showLoginForm';




    protected $settings;




    protected $accessDenied = false;




    protected $loginForm;




    private $loginFormBuildAttempted = false;

    public function __construct()
    {
        $this->settings = (object)get_option("wpstg_settings", []);

        $this->defineHooks();
    }





    public function checkPermissions()
    {
        $this->resetPermaLinks();

        $this->authenticateSubmittedCredentials();

        if (!$this->showLoginForm()) {
            return;
        }

        $loginForm = $this->getLoginForm();

        if (!$loginForm instanceof LoginForm) {
            $this->denyAccessWithoutLoginForm();
            return;
        }

        if ($this->accessDenied) {
            wp_logout();
            $loginForm->setError(__('Access Denied', 'wp-staging'));
        }

        $overrides = [
            'label_username' => __('Username or Email Address', 'wp-staging'),
        ];

        $loginForm->renderForm($loginForm->getDefaultArguments($overrides));
        die();
    }






    protected function getLoginForm()
    {
        if ($this->loginFormBuildAttempted) {
            return $this->loginForm;
        }

        $this->loginFormBuildAttempted = true;

        try {
            $this->loginForm = WPStaging::make(LoginForm::class);
        } catch (\Throwable $e) {
            debug_log(sprintf(
                'Frontend: The staging site login form could not be created. %s: %s in %s:%d',
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
        }

        return $this->loginForm;
    }




    private function authenticateSubmittedCredentials()
    {
        if (!$this->isStagingSite()) {
            return;
        }

        $loginForm = $this->getLoginForm();

        if (!$loginForm instanceof LoginForm) {
            return;
        }

        $loginForm->authenticate();
    }




    private function denyAccessWithoutLoginForm()
    {
        wp_die(
            esc_html__('This staging site is protected by WP STAGING and its login form could not be loaded. The WP STAGING debug log of this site holds the error behind it.', 'wp-staging'),
            esc_html__('Access Denied', 'wp-staging'),
            ['response' => 403]
        );
    }





    private function defineHooks()
    {
        static $isRegistered = false;
        if ($isRegistered) {
            return;
        }

        add_action("init", [$this, "checkPermissions"]);
        add_action("init", [$this, "resavePermalinks"]);

        $isRegistered = true;
    }





    protected function showLoginForm(): bool
    {
        $this->accessDenied = false;

 
        if (defined('DOING_CRON') && DOING_CRON) {
            return false;
        }

 
        if ('cli' === PHP_SAPI && defined('WP_CLI')) {
            return false;
        }

 
        if (Hooks::applyFilters(self::FILTER_FRONTEND_SHOW_LOGIN_FORM, false)) {
            return false;
        }

 

 
        $rest = WPStaging::make(Rest::class);
        if ($rest->isRestUrl()) {
            return false;
        }

        if ($this->isLoginPage() || is_admin()) {
            return false;
        }

        if (!$this->isStagingSite()) {
            return false;
        }

 
        if (current_user_can('manage_options')) {
            return false;
        }

        return (!isset($this->settings->disableAdminLogin) || $this->settings->disableAdminLogin !== '1');
    }





    protected function isStagingSite(): bool
    {
        return (new SiteInfo())->isStagingSite();
    }





    protected function isLoginPage(): bool
    {
        return ($GLOBALS["pagenow"] === "wp-login.php");
    }




    protected function resetPermaLinks()
    {
        if (!$this->isStagingSite() || get_option("wpstg_rmpermalinks_executed") === "true") {
            return;
        }

 
        global $wp_rewrite;

 
        $wp_rewrite->set_permalink_structure('');

        flush_rewrite_rules();

        update_option("wpstg_rmpermalinks_executed", "true");
    }




    public function resavePermalinks()
    {
        if (!$this->isStagingSite() || get_option("wpstg_resave_permalinks_executed") === "true") {
            return;
        }

        try {
            include_once(ABSPATH . 'wp-admin/includes/misc.php'); 
            global $wp_rewrite;
            $wp_rewrite->init();
            $wp_rewrite->flush_rules(true);
            update_option("wpstg_resave_permalinks_executed", "true");
        } catch (\Throwable $e) {
            debug_log('File wp-admin/includes/misc.php does not exist. Error: ' . $e->getMessage());
        }
    }
}
