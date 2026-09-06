<?php

namespace WPStaging\Frontend;

use WP_Error;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\Sanitize;

class LoginForm
{
 
    const CREDENTIAL_ERROR_CODES = [
        'authentication_failed',
        'empty_password',
        'empty_username',
        'incorrect_password',
        'invalid_email',
        'invalid_username',
    ];

 
    const TRANSIENT_USER_LOGGED_IN_STATUS = 'wpstg_user_logged_in_status';

 
    private $args = [];







    private $error = '';

 
    private $sanitize;

 
    private $siteInfo;

    public function __construct()
    {
        $this->sanitize = WPStaging::make(Sanitize::class);
        $this->siteInfo = WPStaging::make(SiteInfo::class);
        $this->login();
    }




    private function login(): bool
    {
        if (is_user_logged_in()) {
            return false;
        }

        if (!isset($_POST['wpstg-username']) || !isset($_POST['wpstg-pass'])) {
            return false;
        }

        if (!$this->siteInfo->isStagingSite()) {
            return false;
        }

        if (isset($_POST['wpstg-submit']) && (empty($_POST['wpstg-username']) || empty($_POST['wpstg-pass']))) {
            $this->error = 'No username or password given!';
            return false;
        }

        $user = wp_signon([
            'user_login'    => $this->sanitize->sanitizeString($_POST['wpstg-username']),
            'user_password' => $this->sanitize->sanitizePassword($_POST['wpstg-pass']),
            'remember'      => !empty($_POST['rememberme']),
        ]);

        if (is_wp_error($user)) {
            $this->setFailedLoginError($user);
            return false;
        }

        wp_set_current_user($user->ID, $user->user_login);
        set_transient(self::TRANSIENT_USER_LOGGED_IN_STATUS, true, 5);

        $redirectUrl = !empty($_POST['redirect_to']) ? $this->sanitize->sanitizeUrl($_POST['redirect_to']) : '';
        if (!empty($redirectUrl)) {
            wp_safe_redirect($redirectUrl);
            exit;
        }

        return false;
    }





    private function setFailedLoginError(WP_Error $error)
    {
        if (in_array($error->get_error_code(), self::CREDENTIAL_ERROR_CODES, true)) {
            $this->error = $this->getLoginErrorMessage();
            return;
        }

        set_transient(self::TRANSIENT_USER_LOGGED_IN_STATUS, true, 5);
        $this->error = $error->get_error_message();
    }




    private function getLoginErrorMessage(): string
    {
        $guideLink = esc_url('https://wp-staging.com/docs/can-not-login-to-staging-website/#Disable_WP_STAGING_Login_Form_or_Allow_Specific_Users_to_Pass_it');

        if (defined('WPSTGPRO_VERSION')) {
            return sprintf(__('Incorrect credentials! Only administrators or explicitly authorized users can access this page. Please try the default <a target="_blank" href="%s">login</a> form or read this <a target="_blank" href="%s">guide</a>.', 'wp-staging'), wp_login_url(), $guideLink);
        }

        return sprintf(__('Incorrect credentials! Only administrators can access this page. Please try the default <a target="_blank" href="%s">login</a> form or read this <a target="_blank" href="%s">guide</a>.', 'wp-staging'), wp_login_url(), $guideLink);
    }





    public function renderForm(array $args = [])
    {
        $this->args = $args;
        $this->getHeader();
        $this->getLoginForm();
        $this->getFooter();
    }




    private function getHeader()
    {
        require_once WPSTG_VIEWS_DIR . 'frontend/header.php';
    }





    private function getFooter()
    {
        require_once WPSTG_VIEWS_DIR . 'frontend/footer.php';
    }
































    private function getLoginForm()
    {
        $args = empty($this->args) ? $this->getDefaultArguments() : $this->args;

 
        $notice     = __('Enter your administrator credentials to access this site. (This message will be displayed only once!)', 'wp-staging');
        $showNotice = (new LoginNotice())->isLoginNoticeActive();

 
        $isCustomLogin2faEnabled = class_exists('wordfence', false) && get_option('wordfenceActivated');

        $loginFileView = WPSTG_VIEWS_DIR . 'frontend/loginForm.php';

        if ($args['echo']) {
            require($loginFileView);
        } else {
            ob_start();
            require($loginFileView);
            return ob_get_clean();
        }
    }






    public function setError(string $error)
    {
        $this->error = $error;
    }










    public function getDefaultArguments(array $overrides = []): array
    {
 
        $httpHost        = !empty($_SERVER['HTTP_HOST']) ? $this->sanitize->sanitizeString($_SERVER['HTTP_HOST']) : '';
        $requestURI      = !empty($_SERVER['REQUEST_URI']) ? $this->sanitize->sanitizeString($_SERVER['REQUEST_URI']) : '';
        $redirect        = $this->sanitize->sanitizeUrl((is_ssl() ? 'https://' : 'http://') . $httpHost . $requestURI);
        $lostPasswordUrl = wp_lostpassword_url($redirect);
        $arguments       = wp_parse_args(
            $overrides,
            [
                'echo'              => true,
                'redirect'          => $redirect,
                'lost_password_url' => $lostPasswordUrl,
                'form_id'           => 'loginform',
                'label_username'    => __('Username', 'wp-staging'),
                'label_password'    => __('Password', 'wp-staging'),
                'label_remember'    => __('Remember Me', 'wp-staging'),
                'label_log_in'      => __('Log In', 'wp-staging'),
                'id_username'       => 'user_login',
                'id_password'       => 'user_pass',
                'id_remember'       => 'rememberme',
                'id_submit'         => 'wp-submit',
                'remember'          => true,
                'value_username'    => '',
 
                'value_remember'    => false,
            ]
        );

        return $arguments;
    }
}
