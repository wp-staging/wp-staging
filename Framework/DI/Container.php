<?php

namespace WPStaging\Framework\DI;

class Container extends \WPStaging\Vendor\tad_DI52_Container {
	/**
	 * @deprecated Currently, all usages of _get in the codebase
	 *              are Service Locators, not Dependency Injection.
	 *              They need to be refactored in the future.
	 *
	 * @param $offset
	 *
	 * @return mixed|null
	 */
	public function _get( $offset ) {
        try {
            return $this->offsetGet($offset);
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log($e->getMessage());
            }
            return null;
        }
	}
}
