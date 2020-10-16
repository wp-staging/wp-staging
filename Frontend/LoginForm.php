<?php

namespace WPStaging\Frontend;

class LoginForm
{

    /** @var array $args */
    private $args = [];

    /** @var string */
    private $error;

    function __construct()
    {
        $this->login();
    }

    /**
     * @return false
     */
    private function login()
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

        // Try to find user by username
        $user_data = get_user_by('login', $_POST['wpstg-username']);

        // Try to find user by email address
        if (!$user_data) {
            $user_data = get_user_by('email', $_POST['wpstg-username']);
        }

        if (!$user_data) {
            $this->error = 'Login not possible.';
            return false;
        }

        // Validate provided password and login
        if (wp_check_password($_POST['wpstg-pass'], $user_data->user_pass, $user_data->ID)) {

            $rememberme = isset($_POST['rememberme']) ? true : false;

            wp_set_auth_cookie($user_data->ID, $rememberme);
            wp_set_current_user($user_data->ID, $_POST['wpstg-username']);
            do_action('wp_login', $_POST['wpstg-username'], get_userdata($user_data->ID));

            if (!empty($_POST['redirect_to'])) {
                $redirectTo = $_POST['redirect_to'];
            }

            header('Location:' . $redirectTo);
        } else {
            $this->error = 'Username or password wrong!';
        }

        return false;
    }

    public function renderForm($args = [])
    {
        $this->args = $args;
        $this->getHeader();
        $this->getLoginForm();
        $this->getFooter();
    }

    private function getHeader()
    {
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'views/header.php');
    }

    /**
     * Add footer
     *
     */
    private function getFooter()
    {
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'views/footer.php');
    }

    /**
     * Provides a simple login form for use anywhere within WordPress.
     *
     * The login format HTML is echoed by default. Pass a false value for `$echo` to return it instead.
     *
     * @param array $args {
     *     Optional. Array of options to control the form output. Default empty array.
     *
     * @type bool $echo Whether to display the login form or return the form HTML code.
     *                                  Default true (echo).
     * @type string $redirect URL to redirect to. Must be absolute, as in "https://example.com/mypage/".
     *                                  Default is to redirect back to the request URI.
     * @type string $form_id ID attribute value for the form. Default 'loginform'.
     * @type string $label_username Label for the username or email address field. Default 'Username or Email Address'.
     * @type string $label_password Label for the password field. Default 'Password'.
     * @type string $label_remember Label for the remember field. Default 'Remember Me'.
     * @type string $label_log_in Label for the submit button. Default 'Log In'.
     * @type string $id_username ID attribute value for the username field. Default 'user_login'.
     * @type string $id_password ID attribute value for the password field. Default 'user_pass'.
     * @type string $id_remember ID attribute value for the remember field. Default 'rememberme'.
     * @type string $id_submit ID attribute value for the submit button. Default 'wp-submit'.
     * @type bool $remember Whether to display the "rememberme" checkbox in the form.
     * @type string $value_username Default value for the username field. Default empty.
     * @type bool $value_remember Whether the "Remember Me" checkbox should be checked by default.
     *                                  Default false (unchecked).
     *
     * }
     * @return string|void String when retrieving.
     * @since 3.0.0
     *
     */
    private function getLoginForm()
    {
        $arguments = [
            'echo' => true,
            // Default 'redirect' value takes the user back to the request URI.
            'redirect' => (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            'form_id' => 'loginform',
            'label_username' => __('Username'),
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
        ];


        $args = empty($this->args) ? $arguments : $this->args;

        // Don't delete! This is used in the views below
        $notice = __('Enter your administrator credentials to access the cloned site. (This message will be displayed only once!)', 'wp-staging');
        $showNotice = (new LoginNotice())->isLoginNoticeActive();

        if ($args['echo']) {
            require(__DIR__ . DIRECTORY_SEPARATOR . 'views/loginForm.php');
        } else {
            ob_start();
            require(__DIR__ . DIRECTORY_SEPARATOR . 'views/loginForm.php');
            return ob_get_clean();
        }
    }

    /**
     * set error to show
     * @param string $error Error message to set
     * @return null
     */
    public function setError($error)
    {
        $this->error = $error;
    }

}

