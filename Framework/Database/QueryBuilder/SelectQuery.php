<?php

namespace WPStaging\Framework\Database\QueryBuilder;

class SelectQuery
{





    private $preparedValues = [];










    public function getQuery($tableName, $whereClause = '', $limit = 0, $offset = 0)
    {
        $limitations = '';
        if ($limit > 0) {
            $limitations = " LIMIT $limit OFFSET $offset";
        }

        $where = trim($whereClause);
        if ((!empty($where) || $where != '') && !stripos($where, 'where') !== 0) {
            $where = " WHERE $where";
        }

        return "SELECT `$tableName`.* FROM `$tableName`$where$limitations;";
    }













    public function prepareQueryWithFilter($tableName, $limit = 0, $offset = 0, $hook = 'cloning')
    {
        $this->preparedValues = [];
 
        if (!in_array($hook, ['cloning', 'pushing', 'backups'])) {
            throw new \Exception("Hook '$hook' not supported for filter row. Please use between 'cloning', 'pushing' or 'backup'");
        }

        $selectQueryFilter = apply_filters("wpstg.$hook.database.queryRows", []);
        if (!array_key_exists($tableName, $selectQueryFilter)) {
            return $this->getQuery($tableName, '', $limit, $offset);
        }

        if (array_key_exists('join', $selectQueryFilter[$tableName])) {
            return $this->prepareJoinQuery($tableName, $selectQueryFilter[$tableName]['join'], $limit, $offset, $hook);
        }

        $selectQueryFilter = $selectQueryFilter[$tableName];
        $whereClause = $this->prepareWhereClause($selectQueryFilter);

        $where = implode(" AND ", $whereClause);

        return $this->getQuery($tableName, $where, $limit, $offset);
    }






    public function getPreparedValues()
    {
        return $this->preparedValues;
    }












    private function prepareJoinQuery($tableName, $joinInfo, $limit = 0, $offset = 0, $hook = 'cloning')
    {
        $joinTable = $joinInfo['table'];

        $selectQueryFilter = apply_filters("wpstg.$hook.database.queryRows", []);
        if (!array_key_exists($joinTable, $selectQueryFilter)) {
            return $this->getQuery($tableName, '', $limit, $offset);
        }

        $limitations = '';
        if ($limit > 0) {
            $limitations = " LIMIT $limit OFFSET $offset";
        }

        $primaryKey = $joinInfo['primaryKey'];
        $foreignKey = $joinInfo['foreignKey'];

        $selectQueryFilter = $selectQueryFilter[$joinTable];
        $whereClause = $this->prepareWhereClause($selectQueryFilter, "`$joinTable`.");
        $where = implode(" AND ", $whereClause);

        $where = trim($where);
        if ((!empty($where) || $where != '') && !stripos($where, 'where') !== 0) {
            $where = " WHERE $where";
        }

        return "SELECT `$tableName`.* FROM `$tableName`
            INNER JOIN `$joinTable` ON `$joinTable`.$primaryKey = `$tableName`.$foreignKey
            $where$limitations;";
    }








    private function prepareWhereClause($filters, $prefix = '')
    {
        $whereClause = [];
        foreach ($filters as $field => $value) {
            if (is_array($value) && is_array(current($value))) {
                foreach ($value as $subValue) {
                    $whereClause[] = $this->writeWhereClauseStatement($subValue, $field, $prefix);
                }

                continue;
            }

            $whereClause[] = $this->writeWhereClauseStatement($value, $field, $prefix);
        }

        return $whereClause;
    }







    private function writeWhereClauseStatement($value, $field, $prefix)
    {
        if (!is_array($value)) {
            $this->preparedValues[] = $value;
            return "$prefix$field = %s";
        }

        $operator = strtoupper($value['operator']);
        if (!in_array($operator, ['=', '>', '>=', '<', '<=', '<>', '!=', 'LIKE', 'NOT LIKE'])) {
            throw new \Exception('Invalid SQL comparison operator used!');
        }

        $this->preparedValues[] = $value['value'];

        return "$field $operator %s";
    }
}
