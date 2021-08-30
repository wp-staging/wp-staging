<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Framework\CloningProcess\ExcludedPlugins;
use WPStaging\Framework\Staging\CloneOptions;
use WPStaging\Framework\Staging\FirstRun;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Staging\Sites;
use WPStaging\Framework\Support\ThirdParty\FreemiusScript;

class UpdateStagingOptionsTable extends DBCloningService
{
    /**
     * @inheritDoc
     */
    protected function internalExecute()
    {
        $this->log("Updating {$this->dto->getPrefix()}options {$this->dto->getStagingDb()->last_error}");
        if ($this->skipOptionsTable()) {
            return true;
        }

        $updateOrInsert = [
            'wpstg_is_staging_site' => 'true',
            'wpstg_rmpermalinks_executed' => ' ',
            'blog_public' => 0,
            FirstRun::FIRST_RUN_KEY => 'true',
        ];
        // only insert or update clone option if job is not updating
        // during update this data will be preserved
        if ($this->dto->getMainJob() !== 'updating') {
            $updateOrInsert[CloneOptions::WPSTG_CLONE_SETTINGS_KEY] = serialize((object) [
                FirstRun::MAILS_DISABLED_KEY => !((bool) $this->dto->getJob()->getOptions()->emailsAllowed),
                ExcludedPlugins::EXCLUDED_PLUGINS_KEY => (new ExcludedPlugins())->getFilteredPluginsToExclude(),
            ]);
        }

        if (!$this->keepPermalinks()) {
            $updateOrInsert['rewrite_rules'] = null;
            $updateOrInsert['permalink_structure'] = ' ';
        }

        $freemiusHelper = new FreemiusScript();
        // Only show freemius notice if freemius options exists on the productions site
        // These freemius options will be deleted from option table, see below.
        if ($freemiusHelper->hasFreemiusOptions()) {
            $updateOrInsert[FreemiusScript::NOTICE_OPTION] = true;
        }

        $this->updateOrInsertOptions($updateOrInsert);

        $update = [
            'upload_path' => '',
            'wpstg_connection' => json_encode(['prodHostname' => get_site_url()]),
        ];
        if ($this->dto->getMainJob() !== 'updating') {
            $update[Sites::STAGING_SITES_OPTION] = serialize([]);
        }

        $this->updateOptions($update);

        // Options to delete on the staging site
        $toDelete = [];

        if ($freemiusHelper->hasFreemiusOptions()) {
            $toDelete = array_merge($toDelete, $freemiusHelper->getFreemiusOptions());
        }

        $this->deleteOptions($toDelete);

        return true;
    }

    protected function updateOrInsertOptions($options)
    {
        foreach ($options as $name => $value) {
            $this->debugLog("Updating/inserting $name to $value");
            if (!$this->insertDbOption($name, $value)) {
                $this->log("Failed to update/insert $name {$this->dto->getStagingDb()->last_error}", Logger::TYPE_WARNING);
            }
        }
    }

    protected function updateOptions($options)
    {
        foreach ($options as $name => $value) {
            $this->debugLog("Updating $name to $value");
            if ($this->updateDbOption($name, $value) === false) {
                $this->log("Failed to update $name {$this->dto->getStagingDb()->last_error}", Logger::TYPE_WARNING);
            }
        }
    }

    /**
     * Delete given options
     *
     * @param array $options
     */
    protected function deleteOptions($options)
    {
        foreach ($options as $option) {
            $this->debugLog("Deleting $option");
            if ($this->deleteDbOption($option) === false) {
                $this->log("Failed to delete $option {$this->dto->getStagingDb()->last_error}", Logger::TYPE_WARNING);
            }
        }
    }
}
