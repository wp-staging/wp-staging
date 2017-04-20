<?php
namespace WPStaging\Frontend;

use WPStaging\DI\InjectionAware;

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
    }

    /**
     * Check permissions for the page to decide whether or not to disable the page
     */
    public function checkPermissions()
    {
        if ($this->shouldDisableLogin())
        {
            wp_die( sprintf ( __('Access denied. You need to <a href="%1$s" target="_blank">Login</a> first','wpstg'), wp_login_url()  ) );
        }

        $this->resetPermaLinks();
    }

    /**
     * Check if the page should be blocked
     * @return bool
     */
    private function shouldDisableLogin()
    {
        return (
            $this->isStagingSite() &&
            (!isset($this->settings->disableAdminLogin) || '1' !== $this->settings->disableAdminLogin) &&
            (!current_user_can("administrator") && !$this->isLoginPage() && !is_admin())
        );
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
        if (!$this->isStagingSite() || "true" === get_option("wpstg_rmpermalinks_executed"))
        {
            return;
        }
        
        $wpRewrite = $this->getDI()->get("wpRewrite");
        
        if (null === $wpRewrite)
        {
            return;
        }
        
        $wpRewrite->set_permalink_structure(null);
        
        flush_rewrite_rules();
        
        update_option("wpstg_rmpermalinks_executed", "true");
    }
}