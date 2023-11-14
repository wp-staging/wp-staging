<?php

namespace WPStaging\Backend\Feedback;

use WP_User;

/**
 * @todo Move to src/Basic/Feedback/Feedback.php
 */
class Feedback
{

    /**
     * Current page is plugins.php
     * @global array $pagenow
     * @return bool
     */
    private function isPluginsPage(): bool
    {
        global $pagenow;
        return ( $pagenow === 'plugins.php' );
    }

    /**
     * Load feedback form
     * @return void
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

        $reasons = isset($form['wpstg_disable_reason']) ? (array)$form['wpstg_disable_reason'] : [];

        $body    = '';
        $subject = [];
        foreach ($reasons as $reason) {
            $reasonText = ucwords(str_replace('_', ' ', sanitize_text_field($reason)));
            $subject[]  = $reasonText;
            if (isset($form['wpstg_disable_text'][$reason])) {
                $body .= $reasonText . ": " . sanitize_text_field($form['wpstg_disable_text'][$reason]) . "\n\r";
            } else {
                $body .= $reasonText . "\n\r";
            }
        }

        $body = empty($body) ? 'No reason given' : $body;

        $headers = [];

        $from = isset($form['wpstg_disable_from']) ? sanitize_email($form['wpstg_disable_from']) : '';
        if ($from) {
            $headers[] = "From: $from";
            $headers[] = "Reply-To: $from";
        }

        $subject = empty($subject) ? '(no reason given)' : (count($subject) > 1 ? '(multiple reasons given)' : $subject[0]);
        $subject = 'WP Staging Free: ' . $subject;
        $success = wp_mail('feedback@wp-staging.com', $subject, $body, $headers);

        if ($success) {
            wp_die(1);
        }

        wp_die(0);
    }
}
