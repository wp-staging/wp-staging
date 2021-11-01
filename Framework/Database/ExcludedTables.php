<?php

namespace WPStaging\Framework\Database;

use WPStaging\Framework\BackgroundProcessing\Queue;

/**
 * Provide list of table excluded from Database Copy and SearchReplace Job
 */
class ExcludedTables
{
    /**
     * @var string
     */
    const CLONE_DATABASE_TABLES_EXCLUDE_FILTER = 'wpstg_clone_database_tables_exclude';

    /**
     * @var string
     */
    const CLONE_SEARCH_REPLACE_TABLES_EXCLUDE_FILTER = 'wpstg_clone_searchreplace_tables_exclude';

    /**
     * @var string
     */
    const SEARCH_REPLACE_TABLES_EXCLUDE_FILTER = 'wpstg_searchreplace_excl_tables';

    /**
     * @var array
     */
    private $excludedTables;

    /**
     * @var array
     */
    private $networkExcludedTables;

    /**
     * @var array
     */
    private $excludedTablesSearchReplaceOnly;

    public function __construct()
    {
        $this->excludedTables = [
            Queue::getTableName(),
        ];

        $this->networkExcludedTables = [
            'blogs',
            'blog_version',
        ];

        $this->excludedTablesSearchReplaceOnly = [
            '_cerber_files', // Cerber Security Plugin
            '_cerber_sets', // Cerber Security Plugin
        ];
    }

    /**
     * Get List of excluded tables for database copy
     *
     * @return array
     */
    public function getExcludedTables($networkClone = false)
    {
        $excludedCustomTables = apply_filters(self::CLONE_DATABASE_TABLES_EXCLUDE_FILTER, []);

        if ($networkClone) {
            return array_merge($this->excludedTables, $excludedCustomTables);
        }

        return array_merge($this->excludedTables, $this->networkExcludedTables, $excludedCustomTables);
    }

    /**
     * Get List of excluded tables for search replace cloning process job
     * This also includes list of tables excluded through filters for cloning database copy process
     *
     * @return array
     */
    public function getExcludedTablesForSearchReplace($networkClone = false)
    {
        $excludedCustomCloneTables = apply_filters(self::CLONE_SEARCH_REPLACE_TABLES_EXCLUDE_FILTER, []);
        $excludedCustomClonePushTables = apply_filters(self::SEARCH_REPLACE_TABLES_EXCLUDE_FILTER, $this->excludedTablesSearchReplaceOnly);
        $searchReplaceExcludedTables = array_merge($excludedCustomCloneTables, $excludedCustomClonePushTables);
        return array_merge($this->getExcludedTables($networkClone), $searchReplaceExcludedTables);
    }

    /**
     * Get List of excluded tables for search replace push process job
     *
     * @return array
     */
    public function getExcludedTablesForSearchReplacePushOnly()
    {
        return apply_filters(self::SEARCH_REPLACE_TABLES_EXCLUDE_FILTER, $this->excludedTablesSearchReplaceOnly);
    }
}
