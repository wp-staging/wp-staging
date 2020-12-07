<?php

namespace WPStaging\Bootstrap\V1;

if (!class_exists(WpstgRequirements::class)) {
    abstract class WpstgRequirements
    {
        protected $notificationTitle   = '';
        protected $notificationMessage = '';
        protected $pluginFile;

        public function __construct($pluginFile)
        {
            $this->pluginFile = $pluginFile;
        }

        abstract public function checkRequirements();

        public function _displayWarning()
        {
            $title   = esc_html($this->notificationTitle ?: __('WP STAGING', 'wp-staging'));
            $message = wp_kses_post($this->notificationMessage);

            echo <<<MESSAGE
<div class="notice-warning notice is-dismissible">
    <p style="font-weight: bold;">$title</p>
    <p>$message</p>
</div>
MESSAGE;

            // Cleanup the state.
            $this->notificationTitle   = '';
            $this->notificationMessage = '';
        }
    }
}
