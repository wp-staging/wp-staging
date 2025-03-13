<?php

namespace WPStaging\Basic\Feedback;

use WP_User;
use WPStaging\Core\WPStaging;
use WPStaging\Notifications\Notifications;

class Feedback
{
    /**
     * @var string
     */
    const WPSTG_FEEDBACK_EMAIL = "feedback@wp-staging.com";

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

        include WPSTG_VIEWS_DIR . 'feedback/deactivate-feedback.php';
    }

    /**
     * @return void
     */
    public function sendDeactivateFeedback()
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

        $message = empty($body) ? 'No reason given' : $body;
        $from    = isset($form['wpstg_disable_from']) ? sanitize_email($form['wpstg_disable_from']) : '';
        $subject = empty($subject) ? '(no reason given)' : (count($subject) > 1 ? '(multiple reasons given)' : $subject[0]);
        $success = WPStaging::make(Notifications::class)->sendEmail(self::WPSTG_FEEDBACK_EMAIL, $subject, $message, $from, [], false);

        if ($success) {
            wp_die(1);
        }

        wp_die(0);
    }
}
