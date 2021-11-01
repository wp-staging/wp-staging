<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Core\Utils\Logger;

class UpdateWpOptionsTablePrefix extends DBCloningService
{
    protected function internalExecute()
    {
        $stagingPrefix = $this->dto->getPrefix();
        $stagingDb = $this->dto->getStagingDb();
        $productionDb = $this->dto->getProductionDb();

        // Skip, prefixes are identical. No change needed
        if ($productionDb->prefix === $stagingPrefix) {
            $this->log("Prefix already the same - skipping");
            return true;
        }

        if ($this->isNetworkClone()) {
            return $this->updateAllOptionsTables($stagingPrefix, $productionDb, $stagingDb);
        }

        return $this->updateOptionsTable('options', $stagingPrefix, $productionDb->prefix, $stagingDb);
    }

    /**
     * @param string $stagingPrefix
     * @param wpdb   $productionDb
     * @param wpdb   $stagingDb
     * @return boolean
     */
    private function updateAllOptionsTables($stagingPrefix, $productionDb, $stagingDb)
    {
        $basePrefix = $productionDb->base_prefix;
        $sites = get_sites();
        foreach ($sites as $site) {
            $tableName = $this->getOptionTableWithoutBasePrefix($site->blog_id);
            $this->updateOptionsTable($tableName, $stagingPrefix, $basePrefix, $stagingDb);
        }

        return true;
    }

    /**
     * @param string $tableName
     * @param string $stagingPrefix
     * @param string $productionPrefix
     * @param wpdb   $stagingDb
     * @return boolean
     *
     * @throws FatalException
     */
    private function updateOptionsTable($tableName, $stagingPrefix, $productionPrefix, $stagingDb)
    {
        $this->log("Updating db prefix in {$stagingPrefix}{$tableName}.");

        if ($this->skipTable($tableName)) {
            return true;
        }

        // Filter the rows below. Do not update them!
        $filters = [
            'wp_mail_smtp',
            'wp_mail_smtp_version',
            'wp_mail_smtp_debug',
            'db_version',
        ];

        $filters = apply_filters('wpstg_data_excl_rows', $filters);

        $where = "";
        foreach ($filters as $filter) {
            $where .= " AND option_name <> '" . $filter . "'";
        }

        $this->debugLog("Skipping the option_names (custom filtered):  {$where}", Logger::TYPE_INFO);

        $updateOptions = $stagingDb->query(
            $stagingDb->prepare(
                "UPDATE IGNORE {$stagingPrefix}{$tableName} SET option_name= replace(option_name, %s, %s) WHERE option_name LIKE %s" . $where,
                $productionPrefix,
                $stagingPrefix,
                $productionPrefix . "%"
            )
        );

        if ($updateOptions === false) {
            $this->log("Error on Query: UPDATE IGNORE {$stagingPrefix}{$tableName} SET option_name= replace(option_name, {$productionPrefix}, {$stagingPrefix}) WHERE option_name LIKE {$productionPrefix} {$where}", Logger::TYPE_ERROR);
            throw new FatalException("Failed to update db option_names in {$stagingPrefix}{$tableName}. Error: {$stagingDb->last_error}");
        }

        return true;
    }
}
