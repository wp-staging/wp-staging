<?php

namespace WPStaging\Core\Iterators;

// No Direct Access
if (!defined("WPINC")) {
    die;
}
// todo remove this class, use \RecursiveDirectoryIterator instead
class RecursiveDirectoryIterator extends \RecursiveDirectoryIterator {

	protected $excludeFolders = [];


	public function __construct( $path ) {
		parent::__construct( $path );

		// Skip current and parent directory
		$this->skipdots();

	}

	public function rewind() {
		parent::rewind();

		// Skip current and parent directory
		$this->skipdots();
	}

	public function next() {
		parent::next();

		// Skip current and parent directory
		$this->skipdots();
	}

	/**
	 * Returns whether current entry is a directory and not '.' or '..'
	 *
	 * Explicitly set allow links flag, because RecursiveDirectoryIterator::FOLLOW_SYMLINKS
	 * is not supported by <= PHP 5.3.0
	 *
	 * @return bool
	 */
	public function hasChildren( $allow_links = true ) {
		return parent::hasChildren( $allow_links );
	}

	protected function skipdots() {
		while ( $this->isDot() ) {
			parent::next();
		}
	}
}
