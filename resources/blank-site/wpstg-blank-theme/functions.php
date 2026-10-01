<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('wpstg_blank_setup')) {
    function wpstg_blank_setup()
    {
        add_theme_support('title-tag');
        add_theme_support('automatic-feed-links');
        add_theme_support(
            'html5',
            [ 'search-form', 'gallery', 'caption', 'style', 'script' ]
        );
    }
}

add_action('after_setup_theme', 'wpstg_blank_setup');

if (! function_exists('wpstg_blank_assets')) {
    function wpstg_blank_assets()
    {
        wp_enqueue_style(
            'wpstg-blank-style',
            get_stylesheet_uri(),
            [],
            wp_get_theme()->get('Version')
        );
    }
}

add_action('wp_enqueue_scripts', 'wpstg_blank_assets');
