<?php

namespace WPStaging\Vendor;

/**
 * Class ActionScheduler_StoreSchema
 *
 * @codeCoverageIgnore
 *
 * Creates custom tables for storing scheduled actions
 */
class ActionScheduler_StoreSchema extends \WPStaging\Vendor\ActionScheduler_Abstract_Schema
{
    const ACTIONS_TABLE = 'actionscheduler_actions';
    const CLAIMS_TABLE = 'actionscheduler_claims';
    const GROUPS_TABLE = 'actionscheduler_groups';
    /**
     * @var int Increment this value to trigger a schema update.
     */
    protected $schema_version = 3;
    public function __construct()
    {
        $this->tables = [self::ACTIONS_TABLE, self::CLAIMS_TABLE, self::GROUPS_TABLE];
    }
    protected function get_table_definition($table)
    {
        global $wpdb;
        $table_name = $wpdb->{$table};
        $charset_collate = $wpdb->get_charset_collate();
        $max_index_length = 191;
        // @see wp_get_db_schema()
        switch ($table) {
            case self::ACTIONS_TABLE:
                return "CREATE TABLE {$table_name} (\n\t\t\t\t        action_id bigint(20) unsigned NOT NULL auto_increment,\n\t\t\t\t        hook varchar(191) NOT NULL,\n\t\t\t\t        status varchar(20) NOT NULL,\n\t\t\t\t        scheduled_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',\n\t\t\t\t        scheduled_date_local datetime NOT NULL default '0000-00-00 00:00:00',\n\t\t\t\t        args varchar({$max_index_length}),\n\t\t\t\t        schedule longtext,\n\t\t\t\t        group_id bigint(20) unsigned NOT NULL default '0',\n\t\t\t\t        attempts int(11) NOT NULL default '0',\n\t\t\t\t        last_attempt_gmt datetime NOT NULL default '0000-00-00 00:00:00',\n\t\t\t\t        last_attempt_local datetime NOT NULL default '0000-00-00 00:00:00',\n\t\t\t\t        claim_id bigint(20) unsigned NOT NULL default '0',\n\t\t\t\t        extended_args varchar(8000) DEFAULT NULL,\n\t\t\t\t        PRIMARY KEY  (action_id),\n\t\t\t\t        KEY hook (hook({$max_index_length})),\n\t\t\t\t        KEY status (status),\n\t\t\t\t        KEY scheduled_date_gmt (scheduled_date_gmt),\n\t\t\t\t        KEY args (args({$max_index_length})),\n\t\t\t\t        KEY group_id (group_id),\n\t\t\t\t        KEY last_attempt_gmt (last_attempt_gmt),\n\t\t\t\t        KEY claim_id (claim_id)\n\t\t\t\t        ) {$charset_collate}";
            case self::CLAIMS_TABLE:
                return "CREATE TABLE {$table_name} (\n\t\t\t\t        claim_id bigint(20) unsigned NOT NULL auto_increment,\n\t\t\t\t        date_created_gmt datetime NOT NULL default '0000-00-00 00:00:00',\n\t\t\t\t        PRIMARY KEY  (claim_id),\n\t\t\t\t        KEY date_created_gmt (date_created_gmt)\n\t\t\t\t        ) {$charset_collate}";
            case self::GROUPS_TABLE:
                return "CREATE TABLE {$table_name} (\n\t\t\t\t        group_id bigint(20) unsigned NOT NULL auto_increment,\n\t\t\t\t        slug varchar(255) NOT NULL,\n\t\t\t\t        PRIMARY KEY  (group_id),\n\t\t\t\t        KEY slug (slug({$max_index_length}))\n\t\t\t\t        ) {$charset_collate}";
            default:
                return '';
        }
    }
}
