<?php

namespace WPStaging\Framework\Utils;

/**
 * WP Staging wrapper for WordPress hooks functions.
 */
class Hooks
{
    /**
     * @param string $hook
     * @param array ...$args
     * @return void
     */
    public function doAction(string $hook, array ...$args)
    {
        // Early bail if it is not a wpstg hook
        if (strpos($hook, 'wpstg.') !== 0) {
            return;
        }

        // Early bail if it is a wpstg test hook but not a test
        if (strpos($hook, 'wpstg.tests.') === 0  && !$this->isTest()) {
            return;
        }

        do_action($hook, $args);
    }

    /**
     * @param string $hook
     * @param mixed $value
     * @param array ...$args
     * @return mixed
     */
    public function applyFilters(string $hook, $value, array ...$args)
    {
        // Early bail if it is not a wpstg hook
        if (strpos($hook, 'wpstg.') !== 0) {
            return $value;
        }

        // Early bail if it is a wpstg test hook but not a test
        if (strpos($hook, 'wpstg.tests.') === 0 && !$this->isTest()) {
            return $value;
        }

        return apply_filters($hook, $value, $args);
    }

    /**
     * @return bool
     */
    protected function isTest(): bool
    {
        return defined('WPSTG_TEST') && constant('WPSTG_TEST') === true;
    }
}
