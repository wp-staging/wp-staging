<?php

namespace WPStaging\Backend\Feedback;

use WP_User;

class Feedback
{

    /**
     * Current page is plugins.php
     * @global array $pagenow
     * @return bool
     */
    private function isPluginsPage()
    {
        global $pagenow;
        return ( $pagenow === 'plugins.php' );
    }

    /**
     * Load feedback form
     *
     * @todo check if this is being used, remove otherwise.
     */
    public function loadForm()
    {

        $screen = get_current_screen();
        if (!is_admin() && !$this->isPluginsPage()) {
            return;
        }

        $current_user = wp_get_current_user();
        if (!($current_user instanceof WP_User)) {
            $email = '';
        } else {
            $email = trim($current_user->user_email);
        }

        include WPSTG_PLUGIN_DIR . 'Backend/views/feedback/deactivate-feedback.php';
    }

    public function sendMail()
    {

        if (isset($_POST['data'])) {
            parse_str(sanitize_text_field($_POST['data']), $form);
        }

        $text = '';
        if (isset($form['wpstg_disable_text'])) {
            $text = implode("\n\r", $form['wpstg_disable_text']);
        }

        $headers = [];

        $from = isset($form['wpstg_disable_from']) ? $form['wpstg_disable_from'] : '';
        if ($from) {
            $headers[] = "From: $from";
            $headers[] = "Reply-To: $from";
        }

        $subject = isset($form['wpstg_disable_reason']) ? 'WP Staging Free: ' . $form['wpstg_disable_reason'] : 'WP Staging Free: (no reason given)';

        $success = wp_mail('feedback@wp-staging.com', $subject, $text, $headers);

        //\WPStaging\functions\debug_log(print_r($success, true));
        //\WPStaging\functions\debug_log($from . $subject . var_dump($form));

        if ($success) {
            wp_die(1);
        }
        wp_die(0);
    }
}

/**
 * Helper method to check if user is in the plugins page.
 *
 * @author René Hermenau
 * @since  3.3.7
 *
 * @return bool
 */
//function mashsb_is_plugins_page() {
//    global $pagenow;
//
//    return ( 'plugins.php' === $pagenow );
//}

/**
 * display deactivation logic on plugins page
 *
 * @since 3.3.7
 */
//function mashsb_add_deactivation_feedback_modal() {
//
//    $screen = get_current_screen();
//    if( !is_admin() && !mashsb_is_plugins_page() ) {
//        return;
//    }
//
//    $current_user = wp_get_current_user();
//    if( !($current_user instanceof WP_User) ) {
//        $email = '';
//    } else {
//        $email = trim( $current_user->user_email );
//    }
//
//    include WPSTG_PLUGIN_DIR . 'Backend/views/feedback/deactivate-feedback.php';
//}

/**
 * send feedback via email
 *
 * @since 1.4.0
 */
//function wpstg_send_feedback() {
//
//    if( isset( $_POST['data'] ) ) {
//        parse_str( $_POST['data'], $form );
//    }
//
//    $text = '';
//    if( isset( $form['wpstg_disable_text'] ) ) {
//        $text = implode( "\n\r", $form['wpstg_disable_text'] );
//    }
//
//    $headers = array();
//
//    $from = isset( $form['wpstg_disable_from'] ) ? $form['wpstg_disable_from'] : '';
//    if( $from ) {
//        $headers[] = "From: $from";
//        $headers[] = "Reply-To: $from";
//    }
//
//    $subject = isset( $form['wpstg_disable_reason'] ) ? $form['wpstg_disable_reason'] : '(no reason given)';
//
//    $success = wp_mail( 'makebetter@mashshare.net', $subject, $text, $headers );
//
//    if( $success ) {
//        wp_die( 1 );
//    }
//    wp_die( 0 );
//    //\WPStaging\functions\debug_log(print_r($success, true));
//    //\WPStaging\functions\debug_log($from . $subject . var_dump($form));
//    die();
//}
//
//add_action( 'wp_ajax_wpstg_send_feedback', 'wpstg_send_feedback' );
