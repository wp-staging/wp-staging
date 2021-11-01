<?php

namespace WPStaging\Framework\Analytics;

class AnalyticsCleanup
{
    public function cleanupOldAnalytics()
    {
        global $wpdb;

        $results = $wpdb->get_results("SELECT `option_id`, `option_name` FROM $wpdb->options WHERE `option_name` LIKE 'wpstg_analytics_event_%' ORDER BY `option_id` ASC");

        // No site should have more than 100 events in the database.
        if (count($results) < 100) {
            return;
        }

        // If they do, we will delete the first 20 events.
        $idsToDelete = array_map(function ($option) {
            return $option->option_id;
        }, array_slice($results, 0, 20));

        $idsToDelete = implode(',', $idsToDelete);

        $deleteResult = $wpdb->query("DELETE FROM $wpdb->options WHERE `option_id` IN ($idsToDelete)");

        if ($deleteResult != 20) {
            \WPStaging\functions\debug_log(sprintf('WPSTAGING Analytics Cleanup tried to delete 20 events but couldn\'t. wpdb last query: %s wpdb last error: %s', $wpdb->last_query, $wpdb->last_error));
        }
    }
}
