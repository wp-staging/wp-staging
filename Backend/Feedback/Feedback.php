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

    /**
     * @return void
     */
    public function sendMail()
    {

        if (!empty($_POST['data'])) {
            // phpcs:ignore
            parse_str($_POST['data'], $form);  // This is a js serialised string. It needs to be parsed first. It will be sanitised on the next lines after parsing it.
        }

        $text = '';
        if (!empty($form['wpstg_disable_text'])) {
            $text = sanitize_text_field(implode("\n\r", (array)$form['wpstg_disable_text']));
        }

        $headers = [];

        $from = isset($form['wpstg_disable_from']) ? sanitize_email($form['wpstg_disable_from']) : '';
        if ($from) {
            $headers[] = "From: $from";
            $headers[] = "Reply-To: $from";
        }

        $subject = isset($form['wpstg_disable_reason']) ? 'WP Staging Free: ' . sanitize_text_field($form['wpstg_disable_reason']) : 'WP Staging Free: (no reason given)';

        $success = wp_mail('feedback@wp-staging.com', $subject, $text, $headers);

        if ($success) {
            wp_die(1);
        }
        wp_die(0);
    }
}
