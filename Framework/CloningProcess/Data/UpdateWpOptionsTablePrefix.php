<?php


namespace WPStaging\Framework\CloningProcess\Data;


use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Core\Utils\Logger;

class UpdateWpOptionsTablePrefix extends DBCloningService
{
    protected function internalExecute()
    {
        $prefix = $this->dto->getPrefix();
        $stagingDb = $this->dto->getStagingDb();
        $productionDb = $this->dto->getProductionDb();
        $this->log("Updating db prefix in {$prefix}options.");

        if ($this->skipOptionsTable()) {
            return true;
        }

        // Skip, prefixes are identical. No change needed
        if ($productionDb->prefix === $prefix) {
            $this->log("Prefix already the same - skipping");
            return true;
        }

        // Filter the rows below. Do not update them!
        $filters = [
            'wp_mail_smtp',
            'wp_mail_smtp_version',
            'wp_mail_smtp_debug',
        ];

        $filters = apply_filters('wpstg_data_excl_rows', $filters);

        $where = "";
        foreach ($filters as $filter) {
            $where .= " AND option_name <> '" . $filter . "'";
        }

        $this->debugLog("Skipping the option_names (custom filtered):  {$where}", Logger::TYPE_INFO);

        $updateOptions = $stagingDb->query(
            $stagingDb->prepare(
                "UPDATE IGNORE {$prefix}options SET option_name= replace(option_name, %s, %s) WHERE option_name LIKE %s" . $where,
                $productionDb->prefix,
                $prefix,
                $productionDb->prefix . "%"
            )
        );

        if ($updateOptions === false) {
            $this->log("Error on Query: UPDATE IGNORE {$prefix}options SET option_name= replace(option_name, {$productionDb->prefix}, {$prefix}) WHERE option_name LIKE {$productionDb->prefix} {$where}", Logger::TYPE_ERROR);
            throw new FatalException("Failed to update db option_names in {$prefix}options. Error: {$productionDb->last_error}");
        }

        return true;
    }
}
