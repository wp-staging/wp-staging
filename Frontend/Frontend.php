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

    public function __construct()
    {
        $this->defineHooks();

        $this->settings = (object)get_option("wpstg_settings", []);

        $this->loginForm = WPStaging::make(LoginForm::class);
    }





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
