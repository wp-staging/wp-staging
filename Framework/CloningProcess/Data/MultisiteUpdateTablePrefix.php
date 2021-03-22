<?php


namespace WPStaging\Framework\CloningProcess\Data;


class MultisiteUpdateTablePrefix extends UpdateTablePrefix
{
    /**
     * Changes the base table prefix of the main network site
     */
    protected function updateBaseTablePrefix()
    {
        $prefix = $this->dto->getPrefix();
        $db = $this->dto->getStagingDb();
        $productionDb = $this->dto->getProductionDb();

        $this->debugLog("SQL: UPDATE {$prefix}usermeta SET meta_key = replace(meta_key, {$productionDb->base_prefix}, {$prefix}) WHERE meta_key LIKE  {$productionDb->base_prefix}_%");
        $update = $db->query(
            $db->prepare(
                "UPDATE {$prefix}usermeta SET meta_key = replace(meta_key, %s, %s) WHERE meta_key LIKE %s",
                $productionDb->base_prefix,
                $prefix,
                $productionDb->base_prefix . "_%"
            )
        );

        if ($update === false) {
            $this->log("Failed updating {$prefix}usermeta meta_key database base_prefix {$db->last_error}");
        } else {
            //$this->log("Done");
        }
    }
}
