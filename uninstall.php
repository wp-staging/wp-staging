<?php

namespace WPStaging\Backend;

/**
 * Uninstall WP-Staging
 *
 * @package     WPSTG
 * @subpackage  Uninstall
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.9.0
 */
// No direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

class uninstall
{
    public function __construct()
    {
        $this->init();
    }

    private function init()
    {
        $options = json_decode(json_encode(get_option("wpstg_settings", [])));

        if (isset($options->unInstallOnDelete) && $options->unInstallOnDelete === '1') {
            // Options
            delete_option("wpstg_version_upgraded_from");
            delete_option("wpstg_version");
            delete_option("wpstgpro_version_upgraded_from");
            delete_option("wpstgpro_version");
            delete_option("wpstg_installDate");
            delete_option("wpstg_firsttime");
            delete_option("wpstg_is_staging_site");
            delete_option("wpstg_settings");
            delete_option("wpstg_rmpermalinks_executed");
            delete_option("wpstg_activation_redirect");
            delete_option("wpstg_disabled_items_notice");
            delete_option("wpstg_clone_settings");
            delete_option("wpstg_different_prefix_backup_notice");
            // Old notice used for display cache on staging site.
            delete_option("wpstg_disabled_cache_notice");
            // Old option, now moved inside wpstg_clone_settings
            delete_option("wpstg_emails_disabled");
            // Option related to staging sites shifting from one db option to another
            delete_option("wpstg_structure_updated");


            /* Do not delete these fields without actually deleting the staging site
             * @create a delete routine which deletes the staging sites first
             */
            //delete_option( "wpstg_existing_clones" );
            //delete_option( "wpstg_existing_clones_beta" );
            //delete_option( "wpstg_staging_sites" );
            //delete_option( "wpstg_connection" );

            // Old wpstg 1.3 options for admin notices
            delete_option("wpstg_start_poll");
            delete_option("wpstg_hide_beta");
            delete_option("wpstg_RatingDiv");

            // New 2.x options for admin notices
            delete_option("wpstg_poll");
            delete_option("wpstg_rating");
            delete_option("wpstg_beta");

            /* @see \WPStaging\Framework\Staging\FirstRun::FIRST_RUN_KEY */
            delete_option('wpstg_execute');

            /* @see \WPStaging\Framework\BackgroundProcessing\Queue::QUEUE_TABLE_VERSION_KEY */
            delete_option('wpstg_queue_table_version');

            // Delete events
            wp_clear_scheduled_hook('wpstg_weekly_event');

            // Transients
            delete_transient("wpstg_issue_report_submitted");

        }
    }
}

new uninstall();
