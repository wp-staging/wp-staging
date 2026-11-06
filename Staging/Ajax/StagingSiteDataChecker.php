<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Language\Language;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\DirectoryListing;
use WPStaging\Staging\Service\AbstractStagingSetup;







class StagingSiteDataChecker
{
 
    private $auth;

 
    private $dirAdapter;

 
    private $stagingSetup;

    public function __construct(Auth $auth, Directory $directory, AbstractStagingSetup $stagingSetup)
    {
        $this->auth         = $auth;
        $this->dirAdapter   = $directory;
        $this->stagingSetup = $stagingSetup;
    }




    public function ajaxIsWritableCloneDestinationDir()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            return;
        }

        $cloneDir = !empty($_POST['cloneDir']) ? Sanitize::sanitizePath($_POST['cloneDir']) : '';
        if (!empty($cloneDir)) {
            wp_mkdir_p($cloneDir);
            if (!is_writable($cloneDir) || !is_readable($cloneDir)) {
                $directoryListing = WPStaging::getInstance()->getContainer()->get(DirectoryListing::class);
                $reason   = __('restricted folder permissions', 'wp-staging');
                $howToFix = sprintf(
                    __('Adjust the permissions for <strong>%s</strong> to <code>755</code> and follow %s step-by-step guide', 'wp-staging'),
                    esc_html($cloneDir),
                    '<a href="https://wp-staging.com/docs/folder-permission-error-folder-xy-is-not-write-and-or-readable/" target="_blank" rel="noopener noreferrer">' . esc_html__('this', 'wp-staging') . '</a>'
                );

                if (!$directoryListing->isPathInOpenBaseDir($cloneDir)) {
                    $reason   = __('the PHP open_basedir setting', 'wp-staging');
                    $howToFix = sprintf(
                        __('Add <strong>%s</strong> to your PHP <code>open_basedir</code> setting and follow %s step-by-step guide', 'wp-staging'),
                        esc_html($cloneDir),
                        '<a href="https://wp-staging.com/docs/how-to-fix-open_basedir-restriction-error/" target="_blank" rel="noopener noreferrer">' . esc_html__('this', 'wp-staging') . '</a>'
                    );
                }

                $supportLink = '<a href="' . esc_url(Language::localizeSupportUrl('https://wp-staging.com/support/')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('open a support ticket', 'wp-staging') . '</a>';
                $message = sprintf(
                    /* translators: 1: directory path, 2: reason, 3: how to fix it, 4: link to open a support ticket. */
                    __('The directory <strong>%s</strong> is not writable or not readable due to %s.<br/>
                    <p class="wpstg-mb-10px wpstg-mt-10px"><strong>How to fix this:</strong></p>
                    <ul style="list-style:circle;" class="wpstg-ml-15px wpstg-mt-5px">
                    <li>%s.</li>
                    <li>Or %s.</li>
                    </ul>', 'wp-staging'),
                    esc_html($cloneDir),
                    esc_html($reason),
                    $howToFix,
                    $supportLink
                );

                wp_send_json_error(['message' => $message]);
            }

            wp_send_json_success();
        }

        $cloneDirRootPath = $this->dirAdapter->getAbsPath();
        if (is_writable($cloneDirRootPath)) {
            wp_send_json_success();
        }

        $cloneDir = $this->dirAdapter->getStagingSiteDirectoryInsideWpcontent();
        if ($cloneDir === false) {
            wp_send_json_error([
                'message' => Escape::escapeHtml($this->getDefaultDestinationNotWritableMessage()),
            ]);
        }

        if (is_writable($cloneDir)) {
            wp_send_json_success();
        }

        wp_send_json_error([
            'message' => sprintf(__('Clone destination dir is not writable. Please make <code>%s</code> or <code>%s</code> writable to proceed!', 'wp-staging'), esc_html($cloneDirRootPath), esc_html($cloneDir)),
        ]);
    }




    private function getDefaultDestinationNotWritableMessage(): string
    {
        if (!$this->stagingSetup->isProLicenseActive()) {
            return sprintf(
                /* translators: %s: URL of the WP STAGING Pro pricing page. */
                __('The staging site cannot be created because neither the WordPress root folder nor the wp-content folder is writable. Make one of them writable and try again, or <a href="%s" target="_blank" rel="noopener noreferrer">upgrade to WP STAGING Pro</a> to set a custom path.', 'wp-staging'),
                esc_url(Language::getUpgradeUrl('staging_destination_not_writable'))
            );
        }

        return __('The staging site cannot be created because neither the WordPress root folder nor the wp-content folder is writable. Make one of them writable, or customize the path in staging settings.', 'wp-staging');
    }
}
