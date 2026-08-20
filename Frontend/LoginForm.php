<?php

namespace WPStaging\Frontend;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\Sanitize;

class LoginForm
{
 
    private $args = [];







    private $error = '';

 
    private $sanitize;

    public function __construct()
    {
        $this->sanitize = WPStaging::make(Sanitize::class);
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


        if (isset($_POST['wpstg-submit']) && (empty($_POST['wpstg-username']) || empty($_POST['wpstg-pass']))) {
            $this->error = 'No username or password given!';
            return false;
        }

        $username = $this->sanitize->sanitizeString($_POST['wpstg-username']);
 
        $user_data = get_user_by('login', $username);

 
        if (!$user_data) {
            $user_data = get_user_by('email', $username);
        }

        $guideLink = esc_url('https://wp-staging.com/docs/can-not-login-to-staging-website/#Disable_WP_STAGING_Login_Form_or_Allow_Specific_Users_to_Pass_it');
        if (!$user_data) {
            $msg = sprintf(__('Incorrect credentials! Only administrators can access this page. Please try the default <a target="_blank" href="%s">login</a> form or read this <a target="_blank" href="%s">guide</a>.', 'wp-staging'), wp_login_url(), $guideLink);

            if (defined('WPSTGPRO_VERSION')) {
                $msg = sprintf(__('Incorrect credentials! Only administrators or explicitly authorized users can access this page. Please try the default <a target="_blank" href="%s">login</a> form or read this <a target="_blank" href="%s">guide</a>.', 'wp-staging'), wp_login_url(), $guideLink);
            }

            $this->error = $msg;
            return false;
        }

 
        $password = isset($_POST['wpstg-pass']) ? $this->sanitize->sanitizePassword($_POST['wpstg-pass']) : '';
        if (wp_check_password($password, $user_data->user_pass, $user_data->ID)) {
            $rememberme = isset($_POST['rememberme']) ? true : false;

            wp_set_auth_cookie($user_data->ID, $rememberme);
            wp_set_current_user($user_data->ID, $username);
            do_action('wp_login', $username, get_userdata($user_data->ID));

            if (!empty($_POST['redirect_to'])) {
                $redirectUrl = $this->sanitize->sanitizeUrl($_POST['redirect_to']);
            }

            set_transient('wpstg_user_logged_in_status', true, 5);

            header('Location:' . $redirectUrl);
        } else {
            $msg = sprintf(__('Login not possible! Only administrators can access this page. Please try the default <a target="_blank" href="%s">login</a> form or read this <a target="_blank" href="%s">guide</a>.', 'wp-staging'), wp_login_url(), $guideLink);

            if (defined('WPSTGPRO_VERSION')) {
                $msg = sprintf(__('Login not possible! Only administrators or explicitly authorized users can access this page. Please try the default <a target="_blank" href="%s">login</a> form or read this <a target="_blank" href="%s">guide</a>.', 'wp-staging'), wp_login_url(), $guideLink);
            }

            $this->error = $msg;
        }

        return false;
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
