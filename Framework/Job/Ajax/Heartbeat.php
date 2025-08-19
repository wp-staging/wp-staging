<?php

namespace WPStaging\Framework\Job\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Job\JobTransientCache;

class Heartbeat extends AbstractTemplateComponent
{
    /**
     * @return void
     */
    public function ajaxProcess()
    {
        if (!$this->canRenderAjax()) {
            wp_send_json_error(null, 401);
        }

        $jobTransientCache = WPStaging::make(JobTransientCache::class);
        if (!$jobTransientCache->getJob()) {
            wp_send_json_error([
                'running' => false,
            ]);

            return;
        }

        if ($jobTransientCache->getJobStatus() === JobTransientCache::STATUS_CANCELLED) {
            wp_send_json_error([
                'running' => false,
            ]);

            return;
        }

        wp_send_json_error([
            'running' => true,
        ]);
    }
}
