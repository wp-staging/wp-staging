<?php


namespace WPStaging\Framework\CloningProcess\Data;


use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;

class UpdateTablePrefix extends DBCloningService
{
    /**
     * @inheritDoc
     */
    protected function internalExecute()
    {
        $prefix = $this->dto->getPrefix();
        $db = $this->dto->getStagingDb();
        //On non-external jobs, $productionDb is same as $db
        $productionDb = $this->dto->getProductionDb();

        $this->log("Updating db prefix in {$prefix}usermeta.");

        if ($this->skipTable('usermeta')) {
            return true;
        }

        $this->updateBaseTablePrefix();

        $this->debugLog("SQL: UPDATE {$prefix}usermeta SET meta_key = replace(meta_key, {$productionDb->prefix}, {$prefix}) WHERE meta_key LIKE {$productionDb->prefix}%");

        $update = $db->query(
            $db->prepare(
                "UPDATE {$prefix}usermeta SET meta_key = replace(meta_key, %s, %s) WHERE meta_key LIKE %s",
                $productionDb->prefix,
                $prefix,
                $productionDb->prefix . "%"
            )
        );

        if ($update === false) {
            throw new FatalException("Failed to update {$prefix}usermeta meta_key database table prefixes {$db->last_error}");
        }

        return true;
    }

    /**
     * This function is overwritten for the multisite service
     */
    protected function updateBaseTablePrefix()
    {
        //Do nothing since this is the single-site class
    }
}
