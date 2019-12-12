<?php

namespace WPStaging\Frontend;

class loginForm {

    /**
     *
     * @var type array
     */
    private $args = array();
    private $error;

    function __construct() {
        $this->login();
    }

    private function login() {


        if( is_user_logged_in() ) {
            return false;
        }

        if( !isset( $_POST['wpstg-username'] ) || !isset( $_POST['wpstg-pass'] ) ) {
            return false;
        }


        if( isset( $_POST['wpstg-submit'] ) && (empty( $_POST['wpstg-username'] ) || empty( $_POST['wpstg-pass'] ) ) ) {
            $this->error = 'No username or password given!';
            return false;
        }

        $user_data = get_user_by( 'login', $_POST['wpstg-username'] );

        if( !$user_data ) {
            $user_data = get_user_by( 'email', $_POST['wpstg-username'] );
        }

        if( !$user_data ) {
            return false;
        }

        if( wp_check_password( $_POST['wpstg-pass'], $user_data->user_pass, $user_data->ID ) ) {

            $rememberme = isset( $_POST['rememberme'] ) ? true : false;

            wp_set_auth_cookie( $user_data->ID, $rememberme );
            wp_set_current_user( $user_data->ID, $_POST['wpstg-username'] );
            do_action( 'wp_login', $_POST['wpstg-username'], get_userdata( $user_data->ID ) );

            $redirect_to = get_site_url() . '/wp-admin/';

            if( !empty( $_POST['redirect_to'] ) ) {
                $redirectTo = $_POST['redirect_to'];
            }

            header( 'Location:' . $redirectTo );
        } else {
            $this->error = 'Username or password wrong!';
        }
    }

    public function renderForm( $args = array() ) {
        $this->args = $args;
        $this->getHeader();
        $this->getLoginForm();
        $this->getFooter();
    }

    private function getHeader() {
        ?>

        <!DOCTYPE html>
        <html xmlns="http://www.w3.org/1999/xhtml" lang="en-US">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <meta name="viewport" content="width=device-width">
                    <meta name='robots' content='noindex,follow' />
                    <title>WordPress &rsaquo; You need to login to access that page</title>

                    <style type="text/css">

                        * {
                            -webkit-box-sizing: border-box;
                            box-sizing: border-box;
                        }

                        html {
                            background: #f1f1f1;
                        }

                        body {
                            color: #444;
                            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                            margin: 0;
                        }

                        .wp-staging-login {
                            padding: 1rem;
                        }

                        .wp-staging-form {
                            -webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.13);
                            box-shadow: 0 1px 3px rgba(0,0,0,0.13);
                            max-width: 500px;
                            width: 100%;
                            margin: 3rem auto;
                            background: #fff;
                            padding: 1rem;
                            overflow: hidden;
                        }

                        .form-control {
                            width: 100%;
                            border: 1px solid #ced4da;
                            border-radius: .25rem;
                            padding: 0.75rem 1rem;
                            font-size: 14px;
                        }

                        .form-control:focus {
                            outline: 0;
                            -webkit-box-shadow: 0 0 0 0.1rem rgba(221,221,221,.35);
                            box-shadow: 0 0 0 0.1rem rgba(221,221,221,.35);
                        }

                        .form-group {
                            margin: 0 0 1em;
                        }

                        .form-group label {
                            margin: 0 0 0.5em;
                            display: block;
                        }

                        .login-remember input {
                            margin-top: 0;
                            vertical-align: middle;
                        }

                        .btn {
                            background: #f7f7f7;
                            border: 1px solid #ccc;
                            color: #555;
                            display: inline-block;
                            text-decoration: none;
                            font-size: 14px;
                            margin: 0;
                            padding: 0.65rem 1.1rem;
                            cursor: pointer;
                            -webkit-border-radius: 3px;
                            -webkit-appearance: none;
                            border-radius: 3px;
                            white-space: nowrap;
                            vertical-align: top;
                            -webkit-transition: -webkit-box-shadow 0.2s ease;
                            transition: -webkit-box-shadow 0.2s ease;
                            -o-transition: box-shadow 0.2s ease;
                            transition: box-shadow 0.2s ease;
                            transition: box-shadow 0.2s ease, -webkit-box-shadow 0.2s ease;
                        }

                        .btn:hover {
                            -webkit-box-shadow: 0 0 0 0.1rem rgba(221,221,221,.35);
                            box-shadow: 0 0 0 0.1rem rgba(221,221,221,.35);
                        }

                        #error-page {
                            margin-top: 50px;
                        }

                        #error-page p {
                            font-size: 14px;
                            line-height: 1.5;
                            margin: 25px 0 20px;
                        }

                        #error-page code {
                            font-family: Consolas, Monaco, monospace;
                        }

                        .error-msg {
                            -webkit-animation: slideIn 0.3s ease;
                            animation: slideIn 0.3s ease;
                            color: #ff4c4c;
                        }

                        .password-lost{
                            padding-top:20px;
                        }

                        .wpstg-text-center {
                          text-align: center;
                        }
                        .wpstg-text-center img {
                          margin-top:30px;
                        }

                        @-webkit-keyframes slideIn {
                            0% {
                                -webkit-transform: translateX(-100%);
                                transform: translateX(-100%);
                            }
                            100% {
                                -webkit-transform: translateX(0);
                                transform: translateX(0);
                            }
                        }

                        @keyframes slideIn {
                            0% {
                                -webkit-transform: translateX(-100%);
                                transform: translateX(-100%);
                            }
                            100% {
                                -webkit-transform: translateX(0);
                                transform: translateX(0);
                            }
                        }
                    </style>
            </head>
            <?php
        }

        /**
         * Add footer
         *
         */
        private function getFooter() {
            echo '</html>';
        }

        /**
         * Provides a simple login form for use anywhere within WordPress.
         *
         * The login format HTML is echoed by default. Pass a false value for `$echo` to return it instead.
         *
         * @since 3.0.0
         *
         * @param array $args {
         *     Optional. Array of options to control the form output. Default empty array.
         *
         *     @type bool   $echo           Whether to display the login form or return the form HTML code.
         *                                  Default true (echo).
         *     @type string $redirect       URL to redirect to. Must be absolute, as in "https://example.com/mypage/".
         *                                  Default is to redirect back to the request URI.
         *     @type string $form_id        ID attribute value for the form. Default 'loginform'.
         *     @type string $label_username Label for the username or email address field. Default 'Username or Email Address'.
         *     @type string $label_password Label for the password field. Default 'Password'.
         *     @type string $label_remember Label for the remember field. Default 'Remember Me'.
         *     @type string $label_log_in   Label for the submit button. Default 'Log In'.
         *     @type string $id_username    ID attribute value for the username field. Default 'user_login'.
         *     @type string $id_password    ID attribute value for the password field. Default 'user_pass'.
         *     @type string $id_remember    ID attribute value for the remember field. Default 'rememberme'.
         *     @type string $id_submit      ID attribute value for the submit button. Default 'wp-submit'.
         *     @type bool   $remember       Whether to display the "rememberme" checkbox in the form.
         *     @type string $value_username Default value for the username field. Default empty.
         *     @type bool   $value_remember Whether the "Remember Me" checkbox should be checked by default.
         *                                  Default false (unchecked).
         *
         * }
         * @return string|void String when retrieving.
         */
        private function getLoginForm() {

            $arguments = array(
                'echo'           => true,
                // Default 'redirect' value takes the user back to the request URI.
                'redirect'       => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                'form_id'        => 'loginform',
                'label_username' => __( 'Username' ),
                'label_password' => __( 'Password' ),
                'label_remember' => __( 'Remember Me' ),
                'label_log_in'   => __( 'Log In' ),
                'id_username'    => 'user_login',
                'id_password'    => 'user_pass',
                'id_remember'    => 'rememberme',
                'id_submit'      => 'wp-submit',
                'remember'       => true,
                'value_username' => '',
                // Set 'value_remember' to true to default the "Remember me" checkbox to checked.
                'value_remember' => false
            );

            $args = empty( $this->args ) ? $arguments : $this->args;

            $form = '
        <main class="wp-staging-login" >
            <div class="wpstg-text-center">
              <img src="' . esc_url(plugins_url('Backend/public/img/logo_clean_small_212_25.png', dirname(__FILE__))) . '" alt="WP Staging Login" />
            </div>
            <form class="wp-staging-form" name="' . $args['form_id'] . '" id="' . $args['form_id'] . '" action="" method="post">
                <div class="form-group login-username">
                    <label for="' . esc_attr( $args['id_username'] ) . '">' . esc_html( $args['label_username'] ) . '</label>
                    <input type="text" name="wpstg-username" id="' . esc_attr( $args['id_username'] ) . '" class="input form-control" value="' . esc_attr( $args['value_username'] ) . '" size="20" />
                </div>
                <div class="form-group login-password">
                    <label for="' . esc_attr( $args['id_password'] ) . '">' . esc_html( $args['label_password'] ) . '</label>
                    <input type="password" name="wpstg-pass" id="' . esc_attr( $args['id_password'] ) . '" class="input form-control" value="" size="20" />
                </div>
                ' . ( $args['remember'] ? '
                <div class="form-group login-remember"><label><input name="rememberme" type="checkbox" id="' . esc_attr( $args['id_remember'] ) . '" value="forever"' . ( $args['value_remember'] ? ' checked="checked"' : '' ) . ' /> <span>' . esc_html( $args['label_remember'] ) . '</span></label></div>
                ' : '' ) . '
                <div class="login-submit">
                    <button type="submit" name="wpstg-submit" id="' . esc_attr( $args['id_submit'] ) . '" class="btn" value="' . esc_attr( $args['label_log_in'] ) . '">Login</button>
                    <input type="hidden" name="redirect_to" value="' . esc_url( $args['redirect'] ) . '" />
                </div>
                <div class="password-lost">
                    <a href="' . trailingslashit(esc_url( $args['redirect'] )) . 'wp-login.php?action=lostpassword">Lost your password?</a>
                </div>
                <p class="error-msg">
                    ' . $this->error . '
                </p>
            </form>
        </main>';

            if( $args['echo'] )
                echo $form;
            else
                return $form;
        }

    }

