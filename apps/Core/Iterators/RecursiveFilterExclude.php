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
        
        //  Path contains new line character on linux
        if(strpos( $this->getInnerIterator()->getSubPathname(), "\n" ) !== false)
                return false;
        
        // Path contains new line character on Windows
        if(strpos( $this->getInnerIterator()->getSubPathname(), "\r" ) !== false)
                return false;
        
        // Part of the path is excluded
        if (in_array( $this->getInnerIterator()->getSubPathname(), $this->exclude ))
                return false;
        
        return true;
        
    }

    public function getChildren() {
        return new self( $this->getInnerIterator()->getChildren(), $this->exclude );
    }

}
