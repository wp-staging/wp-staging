<?php

namespace WPStaging\Framework\Analytics\Actions;

use WPStaging\Framework\Analytics\AnalyticsEventDto;

class AnalyticsStagingCreate extends AnalyticsEventDto
{
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
            "emailsAllowed"   => ,
            "uploadsSymlinked" => ,
            "ownerId" => $this->options->ownerId,
            "includedTables"        => $this->options->tables,
            "excludeSizeRules"      => $this->options->excludeSizeRules,
            "excludeGlobRules"      => $this->options->excludeGlobRules,
            "excludedDirectories"   => $this->options->excludedDirectories,
            "extraDirectories"      => $this->options->extraDirectories,
         */

        $this->is_allowing_email = (bool)$eventData->emailsAllowed;
        $this->is_symlinking_uploads_folder = (bool)$eventData->uploadsSymlinked;
        $this->is_external_database = !(empty($this->options->databaseUser) && empty($this->options->databasePassword));
        $this->number_of_tables = count($eventData->tables);

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
