<?php

namespace WPStaging\Framework\Performance;

use WPStaging\Backend\Modules\Jobs\Cloning;
use WPStaging\Backup\Ajax\Backup;
use WPStaging\Backup\Ajax\Restore;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\ErrorHandler;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

use function WPStaging\functions\debug_log;

class MemoryExhaust extends AbstractTemplateComponent
{
    /**
     * @param TemplateEngine $templateEngine
     */
    public function __construct(TemplateEngine $templateEngine)
    {
        parent::__construct($templateEngine);
    }

    /**
     * @return void
     */
    public function ajaxResponse()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $wpstgRequest = isset($_POST['requestType']) ? Sanitize::sanitizePath($_POST['requestType']) : '';

        $validWpstgRequests = [
            Backup::WPSTG_REQUEST,
            Restore::WPSTG_REQUEST,
            Cloning::WPSTG_REQUEST,
        ];

        if ($wpstgRequest === '') {
            wp_send_json([
                'status'  => false,
                'message' => 'No Response Type Given!',
            ]);
        }

        if (!in_array($wpstgRequest, $validWpstgRequests)) {
            wp_send_json([
                'status'  => false,
                'message' => 'Invalid Response Type Given!',
            ]);
        }

        if (!defined('WPSTG_UPLOADS_DIR')) {
            debug_log('WPSTG_UPLOADS_DIR is not defined!');
            wp_send_json([
                'status'  => false,
                'message' => 'Something Went Wrong!',
            ]);
        }

        $exhaustLockFile = WPSTG_UPLOADS_DIR . $wpstgRequest . ErrorHandler::ERROR_FILE_EXTENSION;
        if (!file_exists($exhaustLockFile)) {
            wp_send_json([
                'status'  => true,
                'error'   => false
            ]);
        };

        $json = file_get_contents($exhaustLockFile);
        unlink($exhaustLockFile);
        $data = json_decode($json, true);

        $result = wp_send_json([
            'status' => true,
            'error' => true,
            'data' => $data,
            'message' => sprintf(
                esc_html__('Memory exhaust issue is detected during the process. Error occurred when allocating more %s on top of current usage of %s. Peak memory usage: %s, Allowed memory limit: %s, PHP memory limit: %s, WP memory limit: %s', 'wp-staging'),
                size_format($data['exhaustedMemorySize']),
                size_format($data['memoryUsage']),
                size_format($data['peakMemoryUsage']),
                size_format($data['allowedMemoryLimit']),
                $data['phpMemoryLimit'],
                $data['wpMemoryLimit']
            )
        ]);

        wp_send_json($result);
    }
}
