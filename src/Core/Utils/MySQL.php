<?php

namespace WPStaging\Utils;

/**
 * Description of MySQL
 *
 */
class MySQL {
    
    private $wpdb;
    
    public function __construct($wpdb){
        $this->wpdb = $wpdb;
    }

    /**
     * Run MySQL query
     *
     * @param  string   $input SQL query
     * @return resource
     */
    public function query( $input ) {
        return mysql_query( $input, $this->wpdb->dbh );
    }

    /**
     * Escape string input for mysql query
     *
     * @param  string $input String to escape
     * @return string
     */
    public function escape( $input ) {
        return mysql_real_escape_string( $input, $this->wpdb->dbh );
    }

    /**
     * Return the error code for the most recent function call
     *
     * @return integer
     */
    public function errno() {
        return mysql_errno( $this->wpdb->dbh );
    }

    /**
     * Return a string description of the last error
     *
     * @return string
     */
    public function error() {
        return mysql_error( $this->wpdb->dbh );
    }

    /**
     * Return server version
     *
     * @return string
     */
    public function version() {
        return mysql_get_server_info( $this->wpdb->dbh );
    }

    /**
     * Return the result from MySQL query as associative array
     *
     * @param  resource $result MySQL resource
     * @return array
     */
    public function fetchAssoc( $result ) {
        return mysql_fetch_assoc( $result );
    }

    /**
     * Return the result from MySQL query as row
     *
     * @param  resource $result MySQL resource
     * @return array
     */
    public function fetchRow( $result ) {
        return mysql_fetch_row( $result );
    }

    /**
     * Return the number for rows from MySQL results
     *
     * @param  resource $result MySQL resource
     * @return integer
     */
    public function numRows( $result ) {
        return mysql_num_rows( $result );
    }

    /**
     * Free MySQL result memory
     *
     * @param  resource $result MySQL resource
     * @return boolean
     */
    public function freeResult( $result ) {
        return mysql_free_result( $result );
    }

}
