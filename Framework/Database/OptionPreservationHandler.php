<?php

namespace WPStaging\Framework\Database;

/**
 * This class creates insert sql queries with data to preserve
 * on a live site after pushing a staging site
 */
class OptionPreservationHandler
{
    private $productionDb;

    /**
     * @param array $optionsNameToPreserve
     * @return string
     */
    public function getLikeStatement(array $optionsNameToPreserve): string
    {
        $optionsToPreserveEscaped = esc_sql($optionsNameToPreserve);

        $likeStatement = '';
        $first         = true;
        foreach ($optionsToPreserveEscaped as $option) {
            if ($first) {
                $likeStatement .= " option_name LIKE '$option'";
                $first = false;
            } else {
                $likeStatement .= " OR option_name LIKE '$option'";
            }
        }

        return $likeStatement;
    }

    /**
     * @param string $whereCondition
     * @param string $optionsTable
     *
     * @return mixed An array containing options data to be preserved
     */
    public function getOptionsDataToPreserve(string $whereCondition, string $optionsTable)
    {
        return $this->productionDb->get_results(
            sprintf(
                "SELECT * FROM `$optionsTable` WHERE %s",
                $whereCondition
            ),
            ARRAY_A
        );
    }

    /**
     * @param array $optionToPreserveData
     * @param string $optionsTableName
     * @return string
     */
    public function createInsertQuery(array $optionToPreserveData, string $optionsTableName): string
    {
        $sql = '';
        foreach ($optionToPreserveData as $option) {
            $sql .= $this->productionDb->prepare(
                "INSERT INTO `$optionsTableName` ( `option_id`, `option_name`, `option_value`, `autoload` ) VALUES ( NULL , %s, %s, %s );\n",
                $option['option_name'],
                $option['option_value'],
                $option['autoload']
            );
        }

        return $sql;
    }

    /**
     * @param string $whereCondition
     * @param string $tableName
     * @return mixed
     */
    public function deleteFromTable(string $whereCondition, string $tableName)
    {
        return $this->productionDb->query(
            sprintf(
                "DELETE FROM `$tableName` WHERE %s",
                $whereCondition
            )
        );
    }

    /**
     * @param  mixed $db
     * @return void
     */
    public function setProductionDb($db)
    {
        $this->productionDb = $db;
    }
}
