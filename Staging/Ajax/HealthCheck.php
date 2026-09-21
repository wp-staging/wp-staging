<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Hosting\StagingSiteHttpDetector;
use WPStaging\Framework\TemplateEngine\TemplateEngine;




class HealthCheck extends AbstractTemplateComponent
{
 
    private $stagingSiteHttpDetector;





    public function __construct(TemplateEngine $templateEngine, StagingSiteHttpDetector $stagingSiteHttpDetector)
    {
        parent::__construct($templateEngine);
        $this->stagingSiteHttpDetector = $stagingSiteHttpDetector;
    }




    public function ajaxCheck()
    {
        if (!$this->canRenderAjax() || !current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('You are not allowed to perform this action.', 'wp-staging')], 403);
        }

        $cloneId = isset($_POST['clone']) ? Sanitize::sanitizeString($_POST['clone']) : '';
        if (empty($cloneId)) {
            wp_send_json_error(['message' => esc_html__('Missing staging site id', 'wp-staging')], 400);
        }

        wp_send_json_success(['health' => $this->stagingSiteHttpDetector->checkStagingSite($cloneId)]);
    }
}
