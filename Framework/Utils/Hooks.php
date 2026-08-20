<?php

namespace WPStaging\Framework\Utils;




class Hooks
{



    private $internalHooks;






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





    public function unregisterInternalHook(string $hookName)
    {
        if (isset($this->internalHooks[$hookName])) {
            unset($this->internalHooks[$hookName]);
        }
    }







    public function callInternalHook(string $hookName, array $args = [], $defaultValue = null)
    {
        if (isset($this->internalHooks[$hookName]) && is_callable($this->internalHooks[$hookName])) {
            return call_user_func_array($this->internalHooks[$hookName], $args);
        }

        return $defaultValue;
    }






    public function doAction(string $hookName, ...$args)
    {
        if (!function_exists('do_action') || !$this->isHookAllowed($hookName)) {
            return;
        }

        do_action($hookName, ...$args);
    }







    public function applyFilters(string $hookName, $value, ...$args)
    {
        if (!function_exists('apply_filters') || !$this->isHookAllowed($hookName)) {
            return $value;
        }

        return apply_filters($hookName, $value, ...$args);
    }





    private function isHookAllowed(string $hookName): bool
    {
        if (!$this->isWpstgHook($hookName)) {
            return false;
        }

 
        if (strpos($hookName, 'wpstg.tests.') === 0 && !$this->isTest()) {
            return false;
        }

        return true;
    }





    private function isWpstgHook(string $hookName): bool
    {
        return strpos($hookName, 'wpstg.') === 0 || strpos($hookName, 'wpstg_') === 0;
    }




    protected function isTest(): bool
    {
        return defined('WPSTG_TEST') && constant('WPSTG_TEST') === true;
    }
}
