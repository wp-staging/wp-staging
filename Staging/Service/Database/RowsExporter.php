<?php

namespace WPStaging\Staging\Service\Database;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Database\Exporter\AbstractRowsExporter;
use WPStaging\Framework\Database\SearchReplace;
use WPStaging\Framework\Database\TableService;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;

/**
 * @see src\Framework\CloningProcess\Database\DatabaseCloningService for existing logic
 * @see src\Staging\Service\Database\TableCreateService for logic related to creating tables
 */
class RowsExporter extends AbstractRowsExporter
{
    const FILTER_EXCLUDE_TABLES_DATA = 'wpstg.cloning.database.exclude_tables_data';

    /** @var string */
    const FILTER_LEGACY_SEARCH_REPLACE_EXCLUDED_ROWS = 'wpstg_clone_searchreplace_excl_rows';

    /** @var string */
    const FILTER_LEGACY_SEARCH_REPLACE_EXCLUDED = 'wpstg_clone_searchreplace_excl';

    /** @var string */
    const FILTER_LEGACY_SEARCH_REPLACE_PARAMS = 'wpstg_clone_searchreplace_params';

    /**
     * Tables without prefix to exclude from data copying. If not excluded in UI or other filters these tables will be created without data.
     * @var string[]
     */
    const TABLES_EXCLUDED_FROM_DATA_COPYING = [
        'wpstg_queue',
        'wpstg_settings',
    ];

    /** @var string */
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

    /**
     * @param int $tableIndex
     */
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

    /**
     * @return void.
     */
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

    /**
     * @param array $searchReplaceArgs
     * @return array
     */
    protected function filterSearchReplaceParams(array $searchReplaceArgs): array
    {
        return (array)apply_filters(self::FILTER_LEGACY_SEARCH_REPLACE_PARAMS, $searchReplaceArgs);
    }

    /**
     * @return array
     */
    protected function getSearchReplaceExcludedPatterns(): array
    {
        return (array)apply_filters(self::FILTER_LEGACY_SEARCH_REPLACE_EXCLUDED, []);
    }

    /**
     * @return array
     */
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
        $search[]  = $this->getPrefix();
        $replace[] = $this->getFinalPrefix();

        return [
            'search'  => $search,
            'replace' => $replace,
        ];
    }

    /**
     * Return Hostname without scheme
     * @param string $string
     * @return string
     */
    protected function getHostnameWithoutScheme(string $string): string
    {
        return preg_replace('#^https?://#', '', rtrim($string, '/'));
    }

    /**
     * Match legacy Backend\Modules\Jobs\SearchReplace semantic for `wpstg_clone_searchreplace_excl_rows`:
     * a row whose `option_name` is on the filter list is written to the destination with its values
     * left untouched. WordPress-core options like siteurl/home/upload_path must exist in the clone
     * (UpdateSiteUrlAndHomeTask UPDATEs them afterwards); plugin-specific entries stay intact.
     *
     * @param string $prefixedTableName
     * @param array  $row
     * @return bool
     */
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
