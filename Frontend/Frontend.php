<?php

namespace WPStaging\Frontend;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Rest\Rest;
use WPStaging\Framework\SiteInfo;

use function WPStaging\functions\debug_log;

/**
 * Class Frontend
 * @package WPStaging\Frontend
 */
class Frontend
{
    /**
     * @var object
     */
    protected $settings;

    /**
     * @var bool
     */
    protected $accessDenied = false;

    /**
     * @var LoginForm
     */
    protected $loginForm;

    public function __construct()
    {
        $this->defineHooks();

        $this->settings = json_decode(json_encode(get_option("wpstg_settings", [])));

        $this->loginForm = WPStaging::make(LoginForm::class);
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
        static $isRegistered = false;
        if ($isRegistered) {
            return;
        }

        add_action("init", [$this, "checkPermissions"]);
        add_action("init", [$this, "resavePermalinks"]);

        $isRegistered = true;
    }

    /**
     * Show a login form if user is not authorized
     * @return bool
     */
    protected function showLoginForm(): bool
    {
        $this->accessDenied = false;

        // Don't show login form if it is a cron job
        if (defined('DOING_CRON') && DOING_CRON) {
            return false;
        }

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

        if (!$this->isStagingSite()) {
            return false;
        }

        // Allow access for administrator
        if (current_user_can('manage_options')) {
            return false;
        }

        return (!isset($this->settings->disableAdminLogin) || $this->settings->disableAdminLogin !== '1');
    }

    /**
     * Check if it is a staging site
     * @return bool
     */
    protected function isStagingSite(): bool
    {
        return (new SiteInfo())->isStagingSite();
    }

    /**
     * Check if it is the login page
     * @return bool
     */
    protected function isLoginPage(): bool
    {
        return ($GLOBALS["pagenow"] === "wp-login.php");
    }

    /**
     * Reset permalink structure of the clone to default; index.php?p=123
     */
    protected function resetPermaLinks()
    {
        if (!$this->isStagingSite() || get_option("wpstg_rmpermalinks_executed") === "true") {
            return;
        }

        // $wp_rewrite is not available before the init hook. So we need to use the global variable
        global $wp_rewrite;

        // @see https://developer.wordpress.org/reference/classes/wp_rewrite/set_permalink_structure/
        $wp_rewrite->set_permalink_structure('');

        flush_rewrite_rules();

        update_option("wpstg_rmpermalinks_executed", "true");
    }

    /**
     * @return void
     */
    public function resavePermalinks()
    {
        if (!$this->isStagingSite() || get_option("wpstg_resave_permalinks_executed") === "true") {
            return;
        }

        try {
            include_once(ABSPATH . 'wp-admin/includes/misc.php'); // Include `misc.php` to ensure `save_mod_rewrite_rules` is available when `flush_rules` is executed.
            global $wp_rewrite;
            $wp_rewrite->init();
            $wp_rewrite->flush_rules(true);
            update_option("wpstg_resave_permalinks_executed", "true");
        } catch (\Throwable $e) {
            debug_log('File wp-admin/includes/misc.php does not exist. Error: ' . $e->getMessage());
        }
    }
}
