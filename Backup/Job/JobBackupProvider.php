<?php

namespace WPStaging\Backup\Job;

use WPStaging\Backup\Job\JobProvider;

/**
 * This class is used to get Lazy initialized JobBackup which can be dynamically changed by dependency injection for Pro or Basic Version
 */
class JobBackupProvider extends JobProvider
{
}
