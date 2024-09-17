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

                if (!$directoryListing->isPathInOpenBaseDir($cloneDir)) {
                    wp_send_json_error([
                        'message' => sprintf(
                            __('The directory is not writable due to open_basedir restriction. Follow our documentation %s to resolve this issue or %s', 'wp-staging'),
                            '<a href="https://wp-staging.com/docs/how-to-fix-open_basedir-restriction-error/" target="_blank">' . esc_html__('how to fix open_basedir restriction error', 'wp-staging') . '</a>',
                            '<a href="https://wp-staging.com/support/" target="_blank">' . esc_html__('open a ticket.', 'wp-staging') . '</a>'
                        )
                    ]);
                } else {
                    wp_send_json_error([
                        'message' => sprintf(
                            __('The directory is not writable due to restricted permissions. Follow our documentation %s to resolve this issue or %s', 'wp-staging'),
                            '<a href="https://wp-staging.com/docs/folder-permission-error-folder-xy-is-not-write-and-or-readable/" target="_blank">' . esc_html__('how to fix folder permission error', 'wp-staging') . '</a>',
                            '<a href="https://wp-staging.com/support/" target="_blank">' . esc_html__('open a ticket.', 'wp-staging') . '</a>'
                        )
                    ]);
                }
            }

            wp_send_json_success();
        }

        $cloneDirRootPath = $this->dirAdapter->getAbsPath();
        if (is_writable($cloneDirRootPath)) {
            wp_send_json_success();
        }

        if (!defined('WPSTGPRO_VERSION')) {
            wp_send_json_error([
                'message' => sprintf(__('Clone destination dir is not writable. Please make <code>%s</code> writable to proceed!', 'wp-staging'), esc_html($cloneDirRootPath))
            ]);
        }

        $cloneDir = $this->dirAdapter->getStagingSiteDirectoryInsideWpcontent();
        if ($cloneDir === false) {
            wp_send_json_error([
                'message' => sprintf(__('Clone destination dir cannot be created. Please choose another path.', 'wp-staging'))
            ]);
        }

        if (is_writable($cloneDir)) {
            wp_send_json_success();
        }

        wp_send_json_error([
            'message' => sprintf(__('Clone destination dir is not writable. Please make <code>%s</code> or <code>%s</code> writable to proceed!', 'wp-staging'), esc_html($cloneDirRootPath), esc_html($cloneDir))
        ]);
    }
}
