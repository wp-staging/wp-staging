<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module {

    public function seePageHasElement( $element ) {
        try {
            $this->getModule( 'WebDriver' )->_findElements( $element );
        } catch ( \PHPUnit_Framework_AssertionFailedError $f ) {
            return false;
        }
        return true;
    }

}
