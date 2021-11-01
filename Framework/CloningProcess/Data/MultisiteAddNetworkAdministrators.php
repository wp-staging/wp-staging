<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Core\Utils\Logger;

class MultisiteAddNetworkAdministrators extends DBCloningService
{
    /**
     * @inheritDoc
     */
    protected function internalExecute()
    {
        $productionDb = $this->dto->getProductionDb();
        $stagingDb = $this->dto->getStagingDb();
        $prefix = $this->dto->getPrefix();

        $this->log("Adding network administrators");
        if ($this->skipTable("usermeta")) {
            return true;
        }

        // Get all super admins
        $superAdmins = get_super_admins();

        // Make sure all super admins are available in usermeta with correct table prefix
        $sql = '';
        foreach ($superAdmins as $username) {
            // Get user id
            $userId = $productionDb->get_var("SELECT ID FROM {$productionDb->base_prefix}users WHERE user_login = '{$username}' ");

            // Check if user capability already exists
            $capabilityExists = $stagingDb->get_var("SELECT user_id FROM {$prefix}usermeta WHERE user_id = '{$userId}' AND meta_key = '{$prefix}capabilities' ");

            // Do nothing if already exists
            if (!empty($capabilityExists)) {
                continue;
            }

            // Add new capability
            $sql .= $stagingDb->prepare(
                "INSERT INTO `{$prefix}usermeta` ( `umeta_id`, `user_id`, `meta_key`, `meta_value` ) VALUES ( NULL , %s, %s, %s );\n",
                $userId,
                $prefix . 'capabilities',
                serialize(
                    [
                        'administrator' => true
                    ]
                )
            );
        }
        if (!empty($sql)) {
            $this->executeSql($sql);
        }
        //$this->log("Done");
        return true;
    }

    /**
     * Execute a batch of sql queries
     * @param string $sqlbatch
     */
    protected function executeSql($sqlbatch)
    {
        $stagingDb = $this->dto->getStagingDb();
        $queries = array_filter(explode(";\n", $sqlbatch));

        foreach ($queries as $query) {
            if ($stagingDb->query($query) === false) {
                $this->log("Could not execute query {$query}", Logger::TYPE_WARNING);
            }
        }
        return true;
    }
}
