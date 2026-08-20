<?php

namespace WPStaging\Framework\Database;

use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\Facades\Hooks;




class ExcludedTables
{



    const CLONE_DATABASE_TABLES_EXCLUDE_FILTER = 'wpstg_clone_database_tables_exclude';




    const CLONE_SEARCH_REPLACE_TABLES_EXCLUDE_FILTER = 'wpstg_clone_searchreplace_tables_exclude';




    const SEARCH_REPLACE_TABLES_EXCLUDE_FILTER = 'wpstg_searchreplace_excl_tables';




    private $excludedTables;




    private $networkExcludedTables;




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
            '_cerber_files', 
            '_cerber_sets', 
        ];
    }






    public function getExcludedTables($networkClone = false)
    {
        $excludedCustomTables = Hooks::applyFilters(self::CLONE_DATABASE_TABLES_EXCLUDE_FILTER, []);

        if ($networkClone) {
            return array_merge($this->excludedTables, $excludedCustomTables);
        }

        return array_merge($this->excludedTables, $this->networkExcludedTables, $excludedCustomTables);
    }







    public function getExcludedTablesForSearchReplace($networkClone = false)
    {
        $excludedCustomCloneTables = Hooks::applyFilters(self::CLONE_SEARCH_REPLACE_TABLES_EXCLUDE_FILTER, []);
        $excludedCustomClonePushTables = Hooks::applyFilters(self::SEARCH_REPLACE_TABLES_EXCLUDE_FILTER, $this->excludedTablesSearchReplaceOnly);
        $searchReplaceExcludedTables = array_merge($excludedCustomCloneTables, $excludedCustomClonePushTables);
        return array_merge($this->getExcludedTables($networkClone), $searchReplaceExcludedTables);
    }






    public function getExcludedTablesForSearchReplacePushOnly()
    {
        return Hooks::applyFilters(self::SEARCH_REPLACE_TABLES_EXCLUDE_FILTER, $this->excludedTablesSearchReplaceOnly);
    }
}
