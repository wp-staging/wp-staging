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

 
    const FILTER_ALLOW_REST_REQUEST_WITHOUT_LOGIN = 'wpstg.frontend.allowRestRequestWithoutLogin';



    const CONTENT_SELECTING_QUERY_VARS = ['p', 'pagename', 'page_id', 'name', 'attachment', 'attachment_id', 'post_type', 'post_type_archive', 'feed', 'feeds', 's', 'cat', 'category_name', 'tag', 'tag_id', 'author', 'author_name', 'year', 'monthnum', 'day', 'm', 'paged', 'static', 'subpost', 'subpost_id', 'preview', 'taxonomy', 'term', 'embed', 'rest_route'];




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

        if ($GLOBALS['pagenow'] === 'index.php' && $this->requestLooksLikeRest()) {
            add_action('parse_request', [$this, 'enforceLoginFormUnlessCoreServesRest'], PHP_INT_MIN);
            add_action('template_redirect', [$this, 'enforceLoginForm'], PHP_INT_MIN);
            return;
        }

        if ($this->isStagingSite() && $this->requestIsAtLoginUrl()) {
            add_action('parse_request', [$this, 'enforceLoginForm'], PHP_INT_MIN);
            add_action('template_redirect', [$this, 'enforceLoginForm'], PHP_INT_MIN);
            return;
        }

        $this->enforceLoginForm();
    }










    public function enforceLoginFormUnlessCoreServesRest($wp)
    {
        if (empty($wp->query_vars['rest_route']) || !has_action('parse_request', 'rest_api_loaded')) {
            $this->enforceLoginForm();
            return;
        }

        if (!$this->showLoginForm()) {
            return;
        }

        $restRoute                      = (string)$wp->query_vars['rest_route'];
        $authenticatedBeforeCookieCheck = false;
        add_filter('rest_authentication_errors', function ($result) use (&$authenticatedBeforeCookieCheck) {
            $authenticatedBeforeCookieCheck = $result === true;
            return $result;
        }, 99);
        add_filter('rest_authentication_errors', function ($result) use ($restRoute, &$authenticatedBeforeCookieCheck) {
            return $this->restAuthenticationErrorForStagingLogin($result, $restRoute, $authenticatedBeforeCookieCheck);
        }, PHP_INT_MAX);

        $this->serveRestRequest();
    }










    private function restAuthenticationErrorForStagingLogin($result, string $restRoute, bool $authenticatedBeforeCookieCheck)
    {
        if (is_wp_error($result) || is_user_logged_in() || ($authenticatedBeforeCookieCheck && $result === true)) {
            return $result;
        }

        $isWpStagingRoute = stripos(trailingslashit($restRoute), '/' . Rest::WPSTG_ROUTE_NAMESPACE_V1 . '/') === 0;
        if (Hooks::applyFilters(self::FILTER_ALLOW_REST_REQUEST_WITHOUT_LOGIN, $isWpStagingRoute, $restRoute)) {
            return $result;
        }

        return new \WP_Error(
            'rest_not_logged_in',
            __('Log in to use this staging site\'s REST API.', 'wp-staging'),
            ['status' => 401]
        );
    }






    public function enforceLoginForm()
    {
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

        $this->renderStagingLoginForm($loginForm);
    }







    protected function renderStagingLoginForm(LoginForm $loginForm)
    {
        $overrides = [
            'label_username' => __('Username or Email Address', 'wp-staging'),
        ];

        $this->preventCaching();
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





    protected function preventCaching()
    {
        nocache_headers();
        add_filter('wp_headers', function ($headers) {
            return array_merge((array)$headers, wp_get_nocache_headers());
        }, PHP_INT_MAX);
    }




    protected function serveRestRequest()
    {
        rest_api_loaded();
    }




    private function requestLooksLikeRest(): bool
    {
 
        $rest = WPStaging::make(Rest::class);

        return $rest->isRestUrl();
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

        if (!$loginForm->wasSubmissionRefused() || !$this->isLoginPage()) {
            return;
        }

        $this->renderStagingLoginForm($loginForm);
    }




    private function denyAccessWithoutLoginForm()
    {
        wp_die(
            esc_html__('This staging site is protected by WP STAGING and its login form could not be loaded. The WP STAGING debug log of this site holds the error behind it.', 'wp-staging'),
            esc_html__('Access Denied', 'wp-staging'),
            ['response' => 403]
        );
    }









    public function preventIndexPhpStripping($redirectUrl, string $requestedUrl)
    {
        if (!is_string($redirectUrl)) {
            return $redirectUrl;
        }

        $requestedPath = (string)parse_url($requestedUrl, PHP_URL_PATH);
        if (strpos($requestedPath, '/index.php') === false || !$this->isStagingSite()) {
            return $redirectUrl;
        }

        $pathWithoutIndexPhp = str_replace('/index.php', '', $requestedPath);
        $redirectPath        = (string)parse_url($redirectUrl, PHP_URL_PATH);
        $sameQuery           = parse_url($redirectUrl, PHP_URL_QUERY) === parse_url($requestedUrl, PHP_URL_QUERY);
        if (!$sameQuery || rtrim($redirectPath, '/') !== rtrim($pathWithoutIndexPhp, '/')) {
            return $redirectUrl;
        }

        return false;
    }





    private function defineHooks()
    {
        static $isRegistered = false;
        if ($isRegistered) {
            return;
        }

        add_action("init", [$this, "checkPermissions"]);
        add_action("init", [$this, "resavePermalinks"]);
        add_filter("redirect_canonical", [$this, "preventIndexPhpStripping"], 10, 2);

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

        if (!$this->isStagingSite()) {
            return false;
        }

        if ($this->isLoginPage() || is_admin()) {
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
        $pagenow = isset($GLOBALS['pagenow']) ? $GLOBALS['pagenow'] : '';

        return $pagenow === 'wp-login.php'
            || ($pagenow === 'index.php' && $this->requestIsAtLoginUrl());
    }






    private function requestIsAtLoginUrl(): bool
    {
        $loginUrl = wp_parse_url(wp_login_url());
        $homeUrl  = wp_parse_url(home_url());
        if (!is_array($loginUrl) || empty($loginUrl['path']) || !is_array($homeUrl) || empty($homeUrl['host'])) {
            return false;
        }

        if (!$this->loginUrlBelongsToThisSite($loginUrl, $homeUrl)) {
            return false;
        }

        $loginPath   = rtrim($loginUrl['path'], '/');
        $requestPath = isset($_SERVER['REQUEST_URI']) ? explode('?', (string) $_SERVER['REQUEST_URI'], 2)[0] : ''; // phpcs:ignore WPStagingCS.Security.SanitizeInput.InputNotSanitized -- matched only, never output or stored
        if ($loginPath !== rtrim($requestPath, '/')) {
            return false;
        }

        $homePath   = rtrim((string) (isset($homeUrl['path']) ? $homeUrl['path'] : ''), '/');
        $isSiteRoot = $loginPath === $homePath || $loginPath === $homePath . '/index.php';
        parse_str(isset($loginUrl['query']) ? $loginUrl['query'] : '', $loginArgs);
        if ($loginArgs === [] && $isSiteRoot) {
            return false;
        }

        $requestArgs = wp_unslash($_GET); // phpcs:ignore WPStagingCS.Security.SanitizeInput.InputNotSanitized -- matched only, never output or stored
        foreach ($loginArgs as $arg => $value) {
            if (!is_scalar($value) || !isset($requestArgs[$arg]) || !is_scalar($requestArgs[$arg]) || (string) $requestArgs[$arg] !== (string) $value) {
                return false;
            }
        }

        if ($loginArgs === []) {
            return !$this->addressResolvesToContent() && !$this->mainQueryServesContent();
        }

        return array_diff_key($this->queryVarsCarriedByTheRequest(), $loginArgs) === [];
    }








    private function addressResolvesToContent(): bool
    {
        if (!isset($GLOBALS["wp"]) || !is_array($GLOBALS["wp"]->query_vars)) {
            return false;
        }

        return array_intersect_key($GLOBALS["wp"]->query_vars, array_flip(self::CONTENT_SELECTING_QUERY_VARS)) !== [];
    }








    private function mainQueryServesContent(): bool
    {
        if (!isset($GLOBALS["wp_query"]) || !$GLOBALS["wp_query"] instanceof \WP_Query) {
            return false;
        }

        $query = $GLOBALS["wp_query"];

        return $query->is_page
            || $query->is_singular
            || $query->is_posts_page
            || !empty($query->posts)
            || (int) $query->found_posts > 0;
    }









    private function loginUrlBelongsToThisSite(array $loginUrl, array $homeUrl): bool
    {
        if (!isset($loginUrl['host'])) {
            return true;
        }

        $loginPort = isset($loginUrl['port']) ? (int) $loginUrl['port'] : 0;
        $homePort  = isset($homeUrl['port']) ? (int) $homeUrl['port'] : 0;

        return strcasecmp($loginUrl['host'], $homeUrl['host']) === 0 && $loginPort === $homePort;
    }









    private function queryVarsCarriedByTheRequest(): array
    {
        $carriedByRequest = $_GET + $_POST; // phpcs:ignore WPStagingCS.Security.SanitizeInput.InputNotSanitized -- its keys are matched only, never output or stored

        if (!isset($GLOBALS['wp'])) {
            return [];
        }

        $wp = $GLOBALS['wp'];

        if (is_array($wp->query_vars) && $wp->query_vars !== []) {
            return array_intersect_key($wp->query_vars, $carriedByRequest);
        }

        if (!is_array($wp->public_query_vars)) {
            return [];
        }

        return array_intersect_key($carriedByRequest, array_flip($wp->public_query_vars));
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
