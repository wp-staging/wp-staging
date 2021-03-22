<?php

namespace WPStaging\Vendor;

/**
 * Class ActionScheduler_wpPostStore_PostTypeRegistrar
 * @codeCoverageIgnore
 */
class ActionScheduler_wpPostStore_PostTypeRegistrar
{
    public function register()
    {
        \WPStaging\Vendor\register_post_type(\WPStaging\Vendor\ActionScheduler_wpPostStore::POST_TYPE, $this->post_type_args());
    }
    /**
     * Build the args array for the post type definition
     *
     * @return array
     */
    protected function post_type_args()
    {
        $args = array('label' => \WPStaging\Vendor\__('Scheduled Actions', 'action-scheduler'), 'description' => \WPStaging\Vendor\__('Scheduled actions are hooks triggered on a cetain date and time.', 'action-scheduler'), 'public' => \false, 'map_meta_cap' => \true, 'hierarchical' => \false, 'supports' => array('title', 'editor', 'comments'), 'rewrite' => \false, 'query_var' => \false, 'can_export' => \true, 'ep_mask' => \WPStaging\Vendor\EP_NONE, 'labels' => array('name' => \WPStaging\Vendor\__('Scheduled Actions', 'action-scheduler'), 'singular_name' => \WPStaging\Vendor\__('Scheduled Action', 'action-scheduler'), 'menu_name' => \WPStaging\Vendor\_x('Scheduled Actions', 'Admin menu name', 'action-scheduler'), 'add_new' => \WPStaging\Vendor\__('Add', 'action-scheduler'), 'add_new_item' => \WPStaging\Vendor\__('Add New Scheduled Action', 'action-scheduler'), 'edit' => \WPStaging\Vendor\__('Edit', 'action-scheduler'), 'edit_item' => \WPStaging\Vendor\__('Edit Scheduled Action', 'action-scheduler'), 'new_item' => \WPStaging\Vendor\__('New Scheduled Action', 'action-scheduler'), 'view' => \WPStaging\Vendor\__('View Action', 'action-scheduler'), 'view_item' => \WPStaging\Vendor\__('View Action', 'action-scheduler'), 'search_items' => \WPStaging\Vendor\__('Search Scheduled Actions', 'action-scheduler'), 'not_found' => \WPStaging\Vendor\__('No actions found', 'action-scheduler'), 'not_found_in_trash' => \WPStaging\Vendor\__('No actions found in trash', 'action-scheduler')));
        $args = \WPStaging\Vendor\apply_filters('action_scheduler_post_type_args', $args);
        return $args;
    }
}
