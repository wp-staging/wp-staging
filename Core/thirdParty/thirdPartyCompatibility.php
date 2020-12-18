<?php

namespace WPStaging\Core\thirdParty;

/**
 * Methods to use for third party plugin compatibility
 *
 * @author IronMan
 */
class thirdPartyCompatibility {

    /**
     * Define a list of tables which should not run through search & replace method
     * @param string table name e.g. wpsptg1_cerber_files or wpstgtmp_4_cerber_files
     * @return boolean
     */
    public function isSearchReplaceExcluded( $table ) {
        $excludedTables = [
            '_cerber_files', // Cerber Security Plugin
        ];

        $excludedTables = apply_filters( 'wpstg_searchreplace_excl_tables', $excludedTables );

        foreach ( $excludedTables as $excludedTable ) {
            if( strpos( $table, $excludedTable ) !== false ) {
                return true;
            }
        }
        return false;
    }

}
