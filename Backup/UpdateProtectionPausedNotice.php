<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Service\UpdateProtectionHealth;
use WPStaging\Backup\Service\UpdateProtectionSettings;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Traits\PagesTrait;








class UpdateProtectionPausedNotice
{
    use PagesTrait;

 
    private $health;

 
    private $settings;

 
    private $capabilities;






    public function __construct(UpdateProtectionHealth $health, UpdateProtectionSettings $settings, Capabilities $capabilities)
    {
        $this->health       = $health;
        $this->settings     = $settings;
        $this->capabilities = $capabilities;
    }





    public function render()
    {
        if (!$this->isWordPressUpdatePage() || !current_user_can($this->capabilities->manageWPSTG())) {
            return;
        }

        if (!$this->settings->isEnabled() || !$this->health->isPaused()) {
            return;
        }

        ?>
        <div class="notice notice-warning wpstg-update-protection-paused">
            <p>
                <strong><?php esc_html_e('WP STAGING Update Protection is temporarily unavailable', 'wp-staging'); ?></strong><br>
                <?php esc_html_e('A recovery backup couldn\'t be created. Plugin updates will continue normally until backup protection is available again.', 'wp-staging'); ?>
                <?php echo $this->getReasonSentence(); // phpcs:ignore WPStaging.Security.EscapeOutput -- Both branches return an escaped string. ?>
            </p>
            <p>
                <button type="button" class="button" data-wpstg-resume-update-protection>
                    <?php esc_html_e('Try protection again', 'wp-staging'); ?>
                </button>
            </p>
        </div>
        <?php
    }




    private function getReasonSentence(): string
    {
        if ($this->health->getReason() === UpdateProtectionHealth::REASON_DISK_SPACE) {
            return esc_html__('There was not enough free disk space to create the backup.', 'wp-staging');
        }

        if ($this->health->getReason() === UpdateProtectionHealth::REASON_PERMISSIONS) {
            return esc_html__('WP STAGING could not write to the backup directory.', 'wp-staging');
        }

        return '';
    }
}
