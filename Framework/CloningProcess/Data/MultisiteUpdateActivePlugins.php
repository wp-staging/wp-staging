<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Backend\Modules\Jobs\Job as MainJob;
use WPStaging\Framework\CloningProcess\Data\UpdateStagingOptionsTable;

class MultisiteUpdateActivePlugins extends DBCloningService
{
    protected function internalExecute()
    {
        $productionDb = $this->dto->getProductionDb();
        $this->log("Updating active_plugins");

        if ($this->skipOptionsTable()) {
            return true;
        }

        // Get active_plugins value from sub site options table
        $active_plugins = $productionDb->get_var("SELECT option_value FROM {$productionDb->prefix}options WHERE option_name = 'active_plugins' ");
        if (!$active_plugins) {
            $this->log("Option name 'active_plugins' is empty ");
            $active_plugins = serialize([]);
        }

        // Get active_sitewide_plugins value from main multisite wp_sitemeta table
        $active_sitewide_plugins = $productionDb->get_var("SELECT meta_value FROM {$productionDb->base_prefix}sitemeta WHERE meta_key = 'active_sitewide_plugins' ");
        if (!$active_sitewide_plugins) {
            $this->log("Site meta {$productionDb->base_prefix}active_sitewide_plugins is empty ");
            $active_sitewide_plugins = serialize([]);
        }

        $active_sitewide_plugins = unserialize($active_sitewide_plugins);
        $active_plugins = unserialize($active_plugins);
        $allPlugins = array_merge($active_plugins, array_keys($active_sitewide_plugins));
        sort($allPlugins);

        if ($this->dto->getMainJob() === MainJob::STAGING) {
            $activePlugins = apply_filters(UpdateStagingOptionsTable::FILTER_CLONING_UPDATE_ACTIVE_PLUGINS, $allPlugins);
            if (is_array($activePlugins)) {
                $allPlugins = $activePlugins;
            }
        }

        if ($this->updateDbOption('active_plugins', serialize($allPlugins)) === false) {
            throw new FatalException("Can not update option active_plugins in {$this->dto->getPrefix()}options");
        }

        return true;
    }
}
