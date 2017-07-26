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
        $loader->addFilter("wp_before_admin_bar_render", $this, "changeSiteName");
    }
    
    /**
     * Change admin_bar site_name
     * 
     * @global object $wp_admin_bar
     * @return void
     */
    public function changeSiteName() {
        global $wp_admin_bar;
        if( $this->isStagingSite() ) {
            // Main Title
            $wp_admin_bar->add_menu( array(
                'id' => 'site-name',
                'title' => is_admin() ? ('STAGING - ' . get_bloginfo( 'name' ) ) : ( 'STAGING - ' . get_bloginfo( 'name' ) . ' Dashboard' ),
                'href' => is_admin() ? home_url( '/' ) : admin_url(),
            ) );
        }
    }
    
    /**
     * Load css files
     */
//    public function enqueueElements() {
//        if( $this->isStagingSite() ) {
//            wp_enqueue_style( "wpstg-admin-bar", $this->admin_url . "/css/wpstg-admin-bar.css", $this->di->getVersion() );
//        }
//    }

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
        // $wp_redirect is not available before the init hook. So we need to use the global declaration
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure(null);

        flush_rewrite_rules();

        update_option("wpstg_rmpermalinks_executed", "true");
    }
}