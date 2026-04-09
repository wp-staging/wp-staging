<?php

namespace WPStaging\Framework\Logger;

/**
 * Event logger process and settings prefix identifiers used by maintenance status logging.
 */
class EventLoggerConst
{
    /**
     * Process prefixes.
     *
     * @var string
     */
    const PROCESS_PREFIX_PUSH = 'P';

    /** @var string */
    const PROCESS_PREFIX_BACKUP = 'B';

    /** @var string */
    const PROCESS_PREFIX_CLONE = 'C';

    /** @var string */
    const PROCESS_PREFIX_CLONE_UPDATE = 'CU';

    /** @var string */
    const PROCESS_PREFIX_CLONE_RESET = 'CR';

    /** @var string */
    const PROCESS_PREFIX_RESTORE = 'R';

    /** @var string */
    const PROCESS_PREFIX_REMOTE_SYNC = 'RS';

    /** @var string */
    const PROCESS_PREFIX_BACKUP_UPLOAD = 'BU';

    /** @var string */
    const PROCESS_PREFIX_PUSH_RELOAD = 'PR';

    /** @var string */
    const PROCESS_PREFIX_BACKUP_EXTRACTION = 'BE';

    /**
     * Backup settings prefixes.
     *
     * @var string
     */
    const BACKUP_SETTING_UPLOADS = 'U';

    /** @var string */
    const BACKUP_SETTING_THEMES = 'T';

    /** @var string */
    const BACKUP_SETTING_MU_PLUGINS = 'MU';

    /** @var string */
    const BACKUP_SETTING_PLUGINS = 'P';

    /** @var string */
    const BACKUP_SETTING_OTHER_WP_CONTENT = 'OW';

    /** @var string */
    const BACKUP_SETTING_OTHER_ROOT = 'OR';

    /** @var string */
    const BACKUP_SETTING_DATABASE = 'D';

    /**
     * Backup storage prefixes.
     *
     * @var string
     */
    const BACKUP_STORAGE_GOOGLE_DRIVE = 'GD';

    /** @var string */
    const BACKUP_STORAGE_AMAZON_S3 = 'AS3';

    /** @var string */
    const BACKUP_STORAGE_DROPBOX = 'DB';

    /** @var string */
    const BACKUP_STORAGE_SFTP = 'S';

    /** @var string */
    const BACKUP_STORAGE_DIGITALOCEAN_SPACES = 'DOS';

    /** @var string */
    const BACKUP_STORAGE_WASABI_S3 = 'WS3';

    /** @var string */
    const BACKUP_STORAGE_GENERIC_S3 = 'GS3';

    /** @var string */
    const BACKUP_STORAGE_ONE_DRIVE = 'OD';

    /** @var string */
    const BACKUP_STORAGE_PCLOUD = 'PC';
}
