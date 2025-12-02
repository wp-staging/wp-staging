<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\DirectoryListing;

/**
 * Class that checks staging site data
 * @package WPStaging\Staging\Ajax
 */
class StagingSiteDataChecker
{
    /** @var Auth */
    private $auth;

    /** @var Directory */
    private $dirAdapter;

    public function __construct(Auth $auth, Directory $directory)
    {
        $this->auth       = $auth;
        $this->dirAdapter = $directory;
    }

    /**
     * @return void
     */
    public function ajaxIsWritableCloneDestinationDir()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            return;
        }

        $cloneDir = !empty($_POST['cloneDir']) ? sanitize_text_field($_POST['cloneDir']) : '';

        if (!empty($cloneDir)) {
            wp_mkdir_p($cloneDir);
            if (!is_writable($cloneDir)) {
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

                $supportLink = '<a href="https://wp-staging.com/support/" target="_blank" rel="noopener noreferrer">' . esc_html__('open a support ticket', 'wp-staging') . '</a>';
                $message = sprintf(
                    __('The directory <strong>%s</strong> is not writable due to %s.<br/>
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

        if (!defined('WPSTGPRO_VERSION')) {
            wp_send_json_error([
                'message' => sprintf(__('Clone destination dir is not writable. Please make <code>%s</code> writable to proceed!', 'wp-staging'), esc_html($cloneDirRootPath)),
            ]);
        }

        $cloneDir = $this->dirAdapter->getStagingSiteDirectoryInsideWpcontent();
        if ($cloneDir === false) {
            wp_send_json_error([
                'message' => sprintf(__('Clone destination dir cannot be created. Please choose another path.', 'wp-staging')),
            ]);
        }

        if (is_writable($cloneDir)) {
            wp_send_json_success();
        }

        wp_send_json_error([
            'message' => sprintf(__('Clone destination dir is not writable. Please make <code>%s</code> or <code>%s</code> writable to proceed!', 'wp-staging'), esc_html($cloneDirRootPath), esc_html($cloneDir)),
        ]);
    }
}
