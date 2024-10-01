<?php

namespace WPStaging\Framework\Utils;

/**
 * WP Staging wrapper for WordPress hooks functions.
 */
class Hooks
{
    /**
     *  @var array<string, callable>
     */
    private $internalHooks;

    /**
     * @param string $hookName
     * @param callable $callback
     * @return void
     */
    public function registerInternalHook(string $hookName, $callback)
    {
        if (!is_callable($callback)) {
            return;
        }

        if (isset($this->internalHooks[$hookName])) {
            $this->unregisterInternalHook($hookName);
        }

        $this->internalHooks[$hookName] = $callback;
    }

    /**
     * @param string $hookName
     * @return void
     */
    public function unregisterInternalHook(string $hookName)
    {
        if (isset($this->internalHooks[$hookName])) {
            unset($this->internalHooks[$hookName]);
        }
    }

    /**
     * @param string $hookName
     * @param array $args []
     * @param mixed $defaultValue null
     * @return mixed
     */
    public function callInternalHook(string $hookName, array $args = [], $defaultValue = null)
    {
        if (isset($this->internalHooks[$hookName]) && is_callable($this->internalHooks[$hookName])) {
            return call_user_func_array($this->internalHooks[$hookName], $args);
        }

        return $defaultValue;
    }

    /**
     * @param string $hookName
     * @param mixed ...$args
     * @return void
     */
    public function doAction(string $hookName, ...$args)
    {
        // Early bail if do_action function is not available
        if (!function_exists('do_action')) {
            return;
        }

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
        // Early bail if apply_filters function is not available
        if (!function_exists('apply_filters')) {
            return $value;
        }

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
