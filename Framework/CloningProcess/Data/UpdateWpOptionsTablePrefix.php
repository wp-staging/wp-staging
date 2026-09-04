<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Database\OptionNameExclusions;




class UpdateWpOptionsTablePrefix extends DBCloningService
{
 
    const FILTER_DATA_EXCLUDED_ROWS = OptionNameExclusions::FILTER_DATA_EXCLUDED_ROWS;

    protected function internalExecute()
    {
        $stagingPrefix = $this->dto->getPrefix();
        $stagingDb = $this->dto->getStagingDb();
        $productionDb = $this->dto->getProductionDb();

 
        if ($productionDb->prefix === $stagingPrefix) {
            $this->log("Prefix already the same - skipping");
            return true;
        }

        if ($this->isNetworkClone()) {
            return $this->updateAllOptionsTables($stagingPrefix, $productionDb, $stagingDb);
        }

        return $this->updateOptionsTable('options', $stagingPrefix, $productionDb->prefix, $stagingDb);
    }







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










    private function updateOptionsTable($tableName, $stagingPrefix, $productionPrefix, $stagingDb)
    {
        $this->log("Updating db prefix in {$stagingPrefix}{$tableName}.");

        if ($this->skipTable($tableName)) {
            return true;
        }

        $where      = "";
        $parameters = [
            $productionPrefix,
            $stagingPrefix,
            $productionPrefix . "%",
        ];

 
        $filters = $this->getExcludedOptionNames();
        foreach ($filters as $filter) {
            $where        .= " AND option_name <> %s";
            $parameters[] = $filter;
        }

        $this->debugLog("Skipping the option_names (custom filtered):  {$where}", Logger::TYPE_INFO);

        $updateOptions = $stagingDb->query(
            $stagingDb->prepare(
                "UPDATE IGNORE {$stagingPrefix}{$tableName} SET option_name= replace(option_name, %s, %s) WHERE option_name LIKE %s" . $where,
                $parameters
            )
        );

        if ($updateOptions === false) {
            $this->log("Error on Query: UPDATE IGNORE {$stagingPrefix}{$tableName} SET option_name= replace(option_name, {$productionPrefix}, {$stagingPrefix}) WHERE option_name LIKE {$productionPrefix} {$where}", Logger::TYPE_ERROR);
            throw new FatalException("Failed to update db option_names in {$stagingPrefix}{$tableName}. Error: {$stagingDb->last_error}");
        }

        return true;
    }




    private function getExcludedOptionNames()
    {
        return OptionNameExclusions::getFilteredOptionNames();
    }
}
