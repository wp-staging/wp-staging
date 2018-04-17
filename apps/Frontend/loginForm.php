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
      
      if(!isset($_POST['wpstg-username']) || !isset ($_POST['wpstg-pass'])){
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
         
         $rememberme = isset($_POST['rememberme']) ? true : false;
         
         wp_set_auth_cookie( $user_data->ID, $rememberme );
         wp_set_current_user( $user_data->ID, $_POST['wpstg-username'] );
         do_action( 'wp_login', $_POST['wpstg-username'], get_userdata( $user_data->ID ) );
         header( 'Location:' . get_site_url() . '/wp-admin/' );
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
                  <title>WordPress &rsaquo; Error</title>
                  <style type="text/css">
                      html {
                          background: #f1f1f1;
                      }
                      body {
                          background: #fff;
                          color: #444;
                          font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                          margin: 2em auto;
                          padding: 1em 2em;
                          max-width: 700px;
                          -webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.13);
                          box-shadow: 0 1px 3px rgba(0,0,0,0.13);
                      }
                      h1 {
                          border-bottom: 1px solid #dadada;
                          clear: both;
                          color: #666;
                          font-size: 24px;
                          margin: 30px 0 0 0;
                          padding: 0;
                          padding-bottom: 7px;
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
                      ul li {
                          margin-bottom: 10px;
                          font-size: 14px ;
                      }
                      a {
                          color: #0073aa;
                      }
                      a:hover,
                      a:active {
                          color: #00a0d2;
                      }
                      a:focus {
                          color: #124964;
                          -webkit-box-shadow:
                              0 0 0 1px #5b9dd9,
                              0 0 2px 1px rgba(30, 140, 190, .8);
                          box-shadow:
                              0 0 0 1px #5b9dd9,
                              0 0 2px 1px rgba(30, 140, 190, .8);
                          outline: none;
                      }
                      .button {
                          background: #f7f7f7;
                          border: 1px solid #ccc;
                          color: #555;
                          display: inline-block;
                          text-decoration: none;
                          font-size: 13px;
                          line-height: 26px;
                          height: 28px;
                          margin: 0;
                          padding: 0 10px 1px;
                          cursor: pointer;
                          -webkit-border-radius: 3px;
                          -webkit-appearance: none;
                          border-radius: 3px;
                          white-space: nowrap;
                          -webkit-box-sizing: border-box;
                          -moz-box-sizing:    border-box;
                          box-sizing:         border-box;

                          -webkit-box-shadow: 0 1px 0 #ccc;
                          box-shadow: 0 1px 0 #ccc;
                          vertical-align: top;
                      }

                      .button.button-large {
                          height: 30px;
                          line-height: 28px;
                          padding: 0 12px 2px;
                      }

                      .button:hover,
                      .button:focus {
                          background: #fafafa;
                          border-color: #999;
                          color: #23282d;
                      }

                      .button:focus  {
                          border-color: #5b9dd9;
                          -webkit-box-shadow: 0 0 3px rgba( 0, 115, 170, .8 );
                          box-shadow: 0 0 3px rgba( 0, 115, 170, .8 );
                          outline: none;
                      }

                      .button:active {
                          background: #eee;
                          border-color: #999;
                          -webkit-box-shadow: inset 0 2px 5px -3px rgba( 0, 0, 0, 0.5 );
                          box-shadow: inset 0 2px 5px -3px rgba( 0, 0, 0, 0.5 );
                          -webkit-transform: translateY(1px);
                          -ms-transform: translateY(1px);
                          transform: translateY(1px);
                      }

                  </style>
          </head>
          <?php
       }

       /**
        * Render login form by using native wp function wp_login_form
        * return string
        */
//       private function getLoginForm() {
//          $this->getHeader();
//          echo '<body id="error-page">';
//          echo __( 'Access denied. Login to access this site', 'wpstg' );
//
//          $args = array(
//              'redirect' => admin_url(),
//              'redirect' => admin_url(),
//              'form_id' => 'wpstg-loginform',
//              'label_username' => __( 'Username', 'wpstg' ),
//              'label_password' => __( 'Password', 'wpstg' ),
//              'label_remember' => __( 'Remember Me' ),
//              'label_log_in' => __( 'Log In Staging Site' ),
//              'remember' => true
//          );
//          wp_login_form( $args );
//          $this->getFooter();
//       }

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
              'echo' => true,
              // Default 'redirect' value takes the user back to the request URI.
              'redirect' => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
              'form_id' => 'loginform',
              'label_username' => __( 'Username or Email Address' ),
              'label_password' => __( 'Password' ),
              'label_remember' => __( 'Remember Me' ),
              'label_log_in' => __( 'Log In' ),
              'id_username' => 'user_login',
              'id_password' => 'user_pass',
              'id_remember' => 'rememberme',
              'id_submit' => 'wp-submit',
              'remember' => true,
              'value_username' => '',
              // Set 'value_remember' to true to default the "Remember me" checkbox to checked.
              'value_remember' => false
          );

          $args = empty( $this->args ) ? $arguments : $this->args;

          $form = '
		<form name="' . $args['form_id'] . '" id="' . $args['form_id'] . '" action="" method="post">
			<p class="login-username">
				<label for="' . esc_attr( $args['id_username'] ) . '">' . esc_html( $args['label_username'] ) . '</label>
				<input type="text" name="wpstg-username" id="' . esc_attr( $args['id_username'] ) . '" class="input" value="' . esc_attr( $args['value_username'] ) . '" size="20" />
			</p>
			<p class="login-password">
				<label for="' . esc_attr( $args['id_password'] ) . '">' . esc_html( $args['label_password'] ) . '</label>
				<input type="password" name="wpstg-pass" id="' . esc_attr( $args['id_password'] ) . '" class="input" value="" size="20" />
			</p>
			' . ( $args['remember'] ? '<p class="login-remember"><label><input name="rememberme" type="checkbox" id="' . esc_attr( $args['id_remember'] ) . '" value="forever"' . ( $args['value_remember'] ? ' checked="checked"' : '' ) . ' /> ' . esc_html( $args['label_remember'] ) . '</label></p>' : '' ) . '
			<p class="login-submit">
				<input type="submit" name="wpstg-submit" id="' . esc_attr( $args['id_submit'] ) . '" class="button button-primary" value="' . esc_attr( $args['label_log_in'] ) . '" />
				<input type="hidden" name="redirect_to" value="' . esc_url( $args['redirect'] ) . '" />
			</p>
                     <p>
                     ' . $this->error . '
                     </p>
                     
			
		</form>';

          if( $args['echo'] )
             echo $form;
          else
             return $form;
       }

    }
    