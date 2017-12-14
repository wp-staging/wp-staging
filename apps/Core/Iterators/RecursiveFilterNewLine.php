<?php

namespace WPStaging\Iterators;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

/**
 * Filter files which contain new line character in name
 */

class RecursiveFilterNewLine extends \RecursiveFilterIterator {

	public function accept() {
		return strpos( $this->getInnerIterator()->getSubPathname(), "\n" ) === false &&
			strpos( $this->getInnerIterator()->getSubPathname(), "\r" ) === false;
	}
}

