<?php

namespace WPStaging\Iterators;

class RecursiveFilterExclude extends \RecursiveFilterIterator {

	protected $exclude = array();

	public function __construct( \RecursiveIterator $iterator, $exclude = array() ) {
		parent::__construct( $iterator );

		// Set exclude filter
		$this->exclude = $exclude;
	}

	public function accept() {
		return ! in_array( $this->getInnerIterator()->getSubPathname(), $this->exclude );
	}

	public function getChildren() {
		return new self( $this->getInnerIterator()->getChildren(), $this->exclude );
	}
}
