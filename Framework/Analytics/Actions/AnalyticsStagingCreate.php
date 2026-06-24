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
        /**
            "cloneName" => $this->options->cloneName,
            "directoryName" => $this->options->cloneDirectoryName,
            "path" => trailingslashit($this->options->destinationDir),
            "url" => $this->getDestinationUrl(),
            "number" => $this->options->cloneNumber,
            "version" => WPStaging::getVersion(),
            "status" => "unfinished or broken (?)",
            "prefix" => $this->options->prefix,
            "datetime" => time(),
            "databaseUser" => $this->options->databaseUser,
            "databasePassword" => $this->options->databasePassword,
            "databaseDatabase" => $this->options->databaseDatabase,
            "databaseServer" => $this->options->databaseServer,
            "databasePrefix" => $this->options->databasePrefix,
            "isEmailsAllowed"   => ,
            "uploadsSymlinked" => ,
            "ownerId" => $this->options->ownerId,
            "includedTables"        => $this->options->tables,
            "excludeSizeRules"      => $this->options->excludeSizeRules,
            "excludeGlobRules"      => $this->options->excludeGlobRules,
            "excludedDirectories"   => $this->options->excludedDirectories,
            "extraDirectories"      => $this->options->extraDirectories,
         */

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

    /**
     * Overriding so that it's easier to find specific usages;
     */
    public function enqueueFinishEvent($jobId, $eventData, $eventOverrides = [])
    {
        parent::enqueueFinishEvent($jobId, $eventData, $eventOverrides);
    }
}
