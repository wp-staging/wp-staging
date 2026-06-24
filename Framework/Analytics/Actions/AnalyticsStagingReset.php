<?php

namespace WPStaging\Framework\Analytics\Actions;

use WPStaging\Framework\Analytics\AnalyticsEventDto;

class AnalyticsStagingReset extends AnalyticsEventDto
{
    public $staging_engine;

    public function getEventAction()
    {
        return 'event_staging_reset';
    }

    public function enqueueStartEvent($eventId, $eventData)
    {
        $this->staging_engine = $this->getStagingEngine($eventData);

        parent::enqueueStartEvent($eventId, $eventData);
    }

    /**
     * Overriding so that it's easier to find specific usages;
     */
    public function enqueueFinishEvent($jobId, $eventData, $eventOverrides = [])
    {
        parent::enqueueFinishEvent($jobId, $eventData, $eventOverrides);
    }
}
