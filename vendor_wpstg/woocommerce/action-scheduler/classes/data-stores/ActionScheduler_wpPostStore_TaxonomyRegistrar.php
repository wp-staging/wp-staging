<?php

namespace WPStaging\Vendor;

/**
 * Class ActionScheduler_wpPostStore_TaxonomyRegistrar
 * @codeCoverageIgnore
 */
class ActionScheduler_wpPostStore_TaxonomyRegistrar
{
    public function register()
    {
        \WPStaging\Vendor\register_taxonomy(\WPStaging\Vendor\ActionScheduler_wpPostStore::GROUP_TAXONOMY, \WPStaging\Vendor\ActionScheduler_wpPostStore::POST_TYPE, $this->taxonomy_args());
    }
    protected function taxonomy_args()
    {
        $args = array('label' => \WPStaging\Vendor\__('Action Group', 'action-scheduler'), 'public' => \false, 'hierarchical' => \false, 'show_admin_column' => \true, 'query_var' => \false, 'rewrite' => \false);
        $args = \WPStaging\Vendor\apply_filters('action_scheduler_taxonomy_args', $args);
        return $args;
    }
}
