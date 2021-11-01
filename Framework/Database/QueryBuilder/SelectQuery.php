<?php

namespace WPStaging\Framework\Database\QueryBuilder;

class SelectQuery
{
    /**
     * Prepared values to make sure query is safe from sql injection
     *
     * @var array
     */
    private $preparedValues = [];

    /**
     * Build Select Query
     *
     * @param string $tableName
     * @param string $whereClause
     * @param integer $limit
     * @param integer $offset
     * @return string
     */
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

    /**
     * Prepare parameterized wp filtered select query for data copying.
     *
     * @param string $tableName
     * @param integer $limit
     * @param integer $offset
     * @param string $hook
     *
     * @return string
     *
     * @throws Exception
     */
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

    /**
     * Get prepared statement values
     *
     * @return array
     */
    public function getPreparedValues()
    {
        return $this->preparedValues;
    }

    /**
     * Build Select Query
     * Support joining with other tables
     *
     * @param string $tableName
     * @param array $joinInfo
     * @param integer $limit
     * @param integer $offset
     * @param string $hook
     * @return string
     */
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

    /**
     * Get prepare clause for Select Query
     *
     * @param array $filters
     * @param string $prefix
     * @return array
     */
    private function prepareWhereClause($filters, $prefix = '')
    {
        $whereClause = [];
        foreach ($filters as $field => $value) {
            if (!is_array($value)) {
                $whereClause[] = "$prefix$field = %s";
                $this->preparedValues[] = $value;
                continue;
            }

            $operator = strtoupper($value['operator']);
            if (!in_array($operator, ['=', '>', '>=', '<', '<=', '<>', '!=', 'LIKE', 'NOT LIKE'])) {
                throw new \Exception('Invalid SQL comparison operator used!');
            }

            $value = $value['value'];

            $whereClause[] = "$field $operator %s";
            $this->preparedValues[] = $value;
        }

        return $whereClause;
    }
}
