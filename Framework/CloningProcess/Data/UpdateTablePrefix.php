<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;

class UpdateTablePrefix extends DBCloningService
{



    protected function internalExecute()
    {
        $prefix = $this->dto->getPrefix();
        $db = $this->dto->getStagingDb();
 
        $productionDb = $this->dto->getProductionDb();
        $productionPrefix = $productionDb->prefix;
        if ($this->isNetworkClone()) {
            $productionPrefix = $productionDb->base_prefix;
        }

        $this->log("Updating db prefix in {$prefix}usermeta.");

        if ($this->skipTable('usermeta')) {
            return true;
        }

 
        if ($productionPrefix === $prefix) {
            $this->log("Prefix already the same - skipping");
            return true;
        }

        $this->debugLog("SQL: UPDATE {$prefix}usermeta SET meta_key = replace(meta_key, {$productionPrefix}, {$prefix}) WHERE meta_key LIKE {$productionPrefix}%");

        $update = $db->query(
            $db->prepare(
                "UPDATE {$prefix}usermeta SET meta_key = replace(meta_key, %s, %s) WHERE meta_key LIKE %s",
                $productionPrefix,
                $prefix,
                $productionPrefix . "%"
            )
        );

        if ($update === false) {
            throw new FatalException("Failed to update {$prefix}usermeta meta_key database table prefixes {$db->last_error}");
        }

        return true;
    }
}
