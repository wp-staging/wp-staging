<?php

namespace WPStaging\Framework\Utils;

/**
 * WP Staging wrapper for WordPress hooks functions.
 */
class Hooks
{
    /**
     * @param string $hookName
     * @param mixed ...$args
     * @return void
     */
    public function doAction(string $hookName, ...$args)
    {
        // Early bail if it is not a wpstg hook
        if (strpos($hookName, 'wpstg.') !== 0) {
            return;
        }

        // Early bail if it is a wpstg test hook but not a test
        if (strpos($hookName, 'wpstg.tests.') === 0  && !$this->isTest()) {
            return;
        }

        do_action($hookName, ...$args);
    }

    /**
     * @param string $hookName
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    public function applyFilters(string $hookName, $value, ...$args)
    {
        // Early bail if it is not a wpstg hook
        if (strpos($hookName, 'wpstg.') !== 0) {
            return $value;
        }

        // Early bail if it is a wpstg test hook but not a test
        if (strpos($hookName, 'wpstg.tests.') === 0 && !$this->isTest()) {
            return $value;
        }

        return apply_filters($hookName, $value, ...$args);
    }

    /**
     * @return bool
     */
    protected function isTest(): bool
    {
        return defined('WPSTG_TEST') && constant('WPSTG_TEST') === true;
    }
}
