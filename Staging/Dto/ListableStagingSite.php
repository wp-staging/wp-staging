<?php

namespace WPStaging\Staging\Dto;

/**
 * Class ListStagingSite
 *
 * Provide fields that you want to display in the staging site list.
 *
 * @package WPStaging\Staging\Dto
 */
class ListableStagingSite
{
    /** @var string */
    public $cloneId;

    /** @var string */
    public $siteName;

    /** @var string */
    public $path;

    /** @var string */
    public $url;

    /** @var bool */
    public $isNetworkClone;

    /** @var string */
    public $cloneName;

    /** @var string */
    public $directoryName;

    /** @var string */
    public $status;

    /**
     * For external database connection, it will be `databaseDataase` from the dto
     * Otherwise it will be `DB_NAME` const of the current site
     * @var string
     */
    public $databaseName;

    /**
     * For external database connection, it will be `databasePrefix` from the dto
     * Otherwise it will be `prefix` from the dto
     * @var string
     */
    public $databasePrefix;

    /**
     * Formatted time when the staging site was created or last modified.
     * @var string
     */
    public $modifiedAt;

    /**
     * Name of the user who created the staging site.
     * If created by user was not set, tt will use the information of the user who modified this staging site.
     * If that that is also not set then it will be `N/A`
     * @var string
     */
    public $createdBy;
}
