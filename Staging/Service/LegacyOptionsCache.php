<?php

namespace WPStaging\Staging\Service;

use stdClass;
use WPStaging\Backend\Modules\Jobs\Job;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Staging\Sites;

/**
 * Prepares the legacy staging options cache for unified setup screens.
 */
class LegacyOptionsCache
{
    public function prepare(string $mainJob, string $cloneId = '')
    {
        if (WPStaging::make(StagingEngine::class)->getEngine() !== StagingEngine::ENGINE_LEGACY) {
            return;
        }

        $cloneOptionCache = WPStaging::make(Cache::class);
        $cloneOptionCache->setLifetime(-1);
        $cloneOptionCache->setPath(WPStaging::getContentDir());
        $cloneOptionCache->setFileName(Job::CLONE_OPTIONS_KEY);

        $filesIndexCache = WPStaging::make(Cache::class);
        $filesIndexCache->setLifetime(-1);
        $filesIndexCache->setPath(WPStaging::getContentDir());
        $filesIndexCache->setFileName(Job::FILES_INDEX_KEY);
        $filesIndexCache->delete();

        $existingClones = get_option(Sites::STAGING_SITES_OPTION, []);
        $existingClones = is_array($existingClones) ? $existingClones : [];
        $currentCloneId = isset($existingClones[$cloneId]) ? $cloneId : null;

        $options = new stdClass();
        $options->root           = str_replace(["\\", '/'], DIRECTORY_SEPARATOR, ABSPATH);
        $options->current        = $currentCloneId;
        $options->clone          = $cloneId;
        $options->currentClone   = $currentCloneId !== null ? $existingClones[$cloneId] : null;
        $options->existingClones = $existingClones;

        $options->clonedTables = [];

        $options->totalFiles    = 0;
        $options->totalFileSize = 0;
        $options->copiedFiles   = 0;

        $options->includedDirectories      = [];
        $options->includedExtraDirectories = [];
        $options->excludedDirectories      = [];
        $options->extraDirectories         = [];
        $options->scannedDirectories       = [];

        $options->currentJob    = $mainJob === Job::PUSH ? '' : 'PreserveDataFirstStep';
        $options->currentStep   = 0;
        $options->totalSteps    = 0;
        $options->mainJob       = $mainJob;
        $options->stagingEngine = StagingEngine::ENGINE_LEGACY;

        $cloneOptionCache->save($options);
    }
}
