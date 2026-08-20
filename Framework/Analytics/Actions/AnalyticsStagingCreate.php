<?php

namespace WPStaging\Framework\Analytics\Actions;

use WPStaging\Framework\Analytics\AnalyticsEventDto;

class AnalyticsStagingCreate extends AnalyticsEventDto
{
    public $is_allowing_email;
    public $is_symlinking_uploads_folder;
    public $is_external_database;
    public $number_of_tables;
    public $staging_engine;

    public function getEventAction()
    {
        return 'event_staging_create';
    }

    public function enqueueStartEvent($eventId, $eventData)
    {

























        $tables = $this->getEventDataValue(
            $eventData,
            'tables',
            $this->getEventDataValue($eventData, 'includedTables', [])
        );

        $this->is_allowing_email             = (bool)$this->getEventDataValue($eventData, 'isEmailsAllowed', true);
        $this->is_symlinking_uploads_folder = (bool)$this->getEventDataValue(
            $eventData,
            'uploadsSymlinked',
            $this->getEventDataValue($eventData, 'isUploadsSymlinked', false)
        );
        $this->is_external_database         = !(
            empty($this->getEventDataValue($eventData, 'databaseUser', '')) &&
            empty($this->getEventDataValue($eventData, 'databasePassword', ''))
        );
        $this->number_of_tables             = is_array($tables) ? count($tables) : 0;
        $this->staging_engine               = $this->getStagingEngine($eventData);

        parent::enqueueStartEvent($eventId, $eventData);
    }




    public function enqueueFinishEvent($jobId, $eventData, $eventOverrides = [])
    {
        parent::enqueueFinishEvent($jobId, $eventData, $eventOverrides);
    }
}
