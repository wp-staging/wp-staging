<?php

namespace WPStaging\Framework\Traits;




trait WordPressOptionNameTrait
{
    protected function getPrefixIndependentWordPressOptionNames(): array
    {
        return [
            'wp_attachment_pages_enabled',
            'wp_calendar_block_has_published_posts',
            'wp_force_deactivated_plugins',
            'wp_notes_notify',
            'wp_page_for_privacy_policy',
        ];
    }
}
