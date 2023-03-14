<?php

/**
 * @see \WPStaging\Frontend\LoginForm::getHeader()
 */

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width">
        <meta name='robots' content='noindex,follow' />
        <title>WordPress &rsaquo; You need to login to access that page</title>
        <style>
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
            .wpstg-text-justify {
                text-align: justify;
            }
            .wpstg-text-center img {
                margin-top:30px;
            }
            .wpstg-alert {
                padding: 16px;
                border: 1px solid transparent;
                margin-bottom: 8px;
            }
            .wpstg-alert > b {
                margin-bottom: 8px;
            }
            .wpstg-alert > p {
                margin: 0px;
            }
            .wpstg-alert.wpstg-alert-info {
                color: #fff;
                background: #185abc;
                border-color: #185abc;
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
    <body>
