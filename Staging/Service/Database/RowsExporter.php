<?php

namespace WPStaging\Staging\Service\Database;

use WPStaging\Framework\Database\Exporter\AbstractRowsExporter;
use WPStaging\Framework\Database\SearchReplace;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;

/**
 * @see src\Framework\CloningProcess\Database\DatabaseCloningService for existing logic
 * @see src\Staging\Service\Database\TableCreateService for logic related to creating tables
 */
class RowsExporter extends AbstractRowsExporter
{
    /** @var string */
    protected $stagingPrefix;

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
        $this->searchReplace = new SearchReplace(
            $searchReplaceParams['search'],
            $searchReplaceParams['replace'],
            true,
            []
        );
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
}
