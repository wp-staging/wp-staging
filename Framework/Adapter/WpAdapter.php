<?php

namespace WPStaging\Framework\Adapter;

/**
 * Class WP
 * Adapter to maintain wordpress core function for WP backward compatibility support and deprecated functions
 *
 * @package WPStaging\Framework\Adapter
 */
class WpAdapter
{
    /**
     * Is the current request doing some ajax
     * Alternative to wp_doing_ajax() as it is not available for WP < 4.7
     * This implementation is without hooks
     *
     * @return boolean
     */
    public function doingAjax()
    {
        return defined('DOING_AJAX') && DOING_AJAX;
    }
}
