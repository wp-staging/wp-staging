<?php

namespace WPStaging\Staging\Service\Database;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Database\Exporter\AbstractRowsExporter;
use WPStaging\Framework\Database\SearchReplace;
use WPStaging\Framework\Database\TableService;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;





class RowsExporter extends AbstractRowsExporter
{
    const FILTER_EXCLUDE_TABLES_DATA = 'wpstg.cloning.database.exclude_tables_data';

 
    const FILTER_LEGACY_SEARCH_REPLACE_EXCLUDED_ROWS = 'wpstg_clone_searchreplace_excl_rows';

 
    const FILTER_LEGACY_SEARCH_REPLACE_EXCLUDED = 'wpstg_clone_searchreplace_excl';

 
    const FILTER_LEGACY_SEARCH_REPLACE_PARAMS = 'wpstg_clone_searchreplace_params';





    const TABLES_EXCLUDED_FROM_DATA_COPYING = [
        'wpstg_queue',
        'wpstg_settings',
    ];

 
    protected $stagingPrefix;

    public function setupDatabase(Database $database)
    {
        $this->setDatabase($database);
        $this->tableService = new TableService($database);
        $this->databaseName = $this->database->getWpdba()->getClient()->__get('dbname');
    }

    public function setStagingPrefix(string $stagingPrefix)
    {
        $this->stagingPrefix = $stagingPrefix;
    }




    public function setTableIndex(int $tableIndex)
    {
        if ($this->tableIndex !== $tableIndex) {
            $this->rowsExporterDto->reset();
        }

        $this->tableIndex = $tableIndex;

        if (!array_key_exists($this->tableIndex, $this->tables)) {
            throw new \RuntimeException('Table not found.');
        }

        $this->tableName = $this->tables[$this->tableIndex]['source'];
    }




    protected function setupSearchReplace()
    {
        $searchReplaceParams = $this->getSearchReplaceParams();
        $searchReplaceArgs   = [
            'search_for'       => $searchReplaceParams['search'],
            'replace_with'     => $searchReplaceParams['replace'],
            'replace_guids'    => 'off',
            'dry_run'          => 'off',
            'case_insensitive' => false,
            'skip_transients'  => 'on',
        ];

        $searchReplaceArgs = $this->filterSearchReplaceParams($searchReplaceArgs);

        $search = isset($searchReplaceArgs['search_for']) && is_array($searchReplaceArgs['search_for']) ? $searchReplaceArgs['search_for'] : $searchReplaceParams['search'];
        $replace = isset($searchReplaceArgs['replace_with']) && is_array($searchReplaceArgs['replace_with']) ? $searchReplaceArgs['replace_with'] : $searchReplaceParams['replace'];
        $caseSensitive = !(isset($searchReplaceArgs['case_insensitive']) && $searchReplaceArgs['case_insensitive']);

        $this->searchReplace = new SearchReplace(
            $search,
            $replace,
            $caseSensitive,
            $this->getSearchReplaceExcludedPatterns()
        );
    }





    protected function filterSearchReplaceParams(array $searchReplaceArgs): array
    {
        return (array)apply_filters(self::FILTER_LEGACY_SEARCH_REPLACE_PARAMS, $searchReplaceArgs);
    }




    protected function getSearchReplaceExcludedPatterns(): array
    {
        return (array)apply_filters(self::FILTER_LEGACY_SEARCH_REPLACE_EXCLUDED, []);
    }




    protected function getSearchReplaceExcludedRows(): array
    {
        return (array)apply_filters(self::FILTER_LEGACY_SEARCH_REPLACE_EXCLUDED_ROWS, $this->excludedStrings());
    }

    protected function getFinalPrefix()
    {
        return $this->stagingPrefix;
    }

    protected function getFinalTableName()
    {
        return $this->tables[$this->tableIndex]['destination'];
    }

    protected function getSearchReplaceParams(): array
    {
        if (!$this->jobDataDto instanceof StagingOperationDtoInterface) {
            throw new \RuntimeException('JobDataDto must be an instance of StagingOperationDtoInterface.');
        }

        $search    = $this->generateHostnamePatterns($this->getSourceHostname());
        $replace   = $this->generateHostnamePatterns($this->getHostnameWithoutScheme($this->jobDataDto->getStagingSiteUrl()));

        return [
            'search'  => $search,
            'replace' => $replace,
        ];
    }






    protected function getHostnameWithoutScheme(string $string): string
    {
        return preg_replace('#^https?://#', '', rtrim($string, '/'));
    }











    protected function isRowSearchReplaceExcluded(string $prefixedTableName, array $row): bool
    {
        if ($prefixedTableName !== $this->getFinalPrefix() . 'options') {
            return false;
        }

        if (!isset($row['option_name'])) {
            return false;
        }

        return in_array($row['option_name'], $this->getSearchReplaceExcludedRows(), true);
    }
}
