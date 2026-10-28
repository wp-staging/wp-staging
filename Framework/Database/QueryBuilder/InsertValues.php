<?php

namespace WPStaging\Framework\Database\QueryBuilder;

use WPStaging\Framework\Facades\Escape;




class InsertValues
{





    public function readColumnTypes($db, $tableName)
    {
        $columnTypes = [];
        foreach ($db->get_results("SHOW COLUMNS FROM `{$tableName}`", ARRAY_A) as $column) {
            $columnTypes[strtolower($column['Field'])] = strtolower($column['Type']);
        }

        return $columnTypes;
    }






    public function formatRow($row, $columnTypes)
    {
        $values = [];
        foreach ($row as $column => $value) {
            $values[] = $this->formatValue($value, $columnTypes[strtolower($column)] ?? '');
        }

        return implode(', ', $values);
    }






    private function formatValue($value, $columnType)
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if ($this->columnTypeNeedsHexLiteral($columnType)) {
            return "UNHEX('" . bin2hex($value) . "')";
        }

        return "'" . Escape::mysqlRealEscapeString($value) . "'";
    }






    private function columnTypeNeedsHexLiteral($columnType)
    {
        return preg_match('/binary|blob|^(geometry|geomcollection|geometrycollection|(multi)?(point|linestring|polygon))$/', $columnType) === 1;
    }
}
