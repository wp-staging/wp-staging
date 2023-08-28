<?php

namespace WPStaging\Framework\Staging\Ajax;

use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Adapter\Directory;

/**
 * Class that checks staging site data
 * @package WPStaging\Framework\Staging\Ajax
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
                wp_send_json_error([
                    'message' => sprintf(__('The provided clone destination dir <code>%s</code> is not writable. Please fix this to proceed!', 'wp-staging'), esc_html($cloneDir))
                ]);
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
