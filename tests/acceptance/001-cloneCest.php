<?php

class cloneCest {

    /**
     * @ env single
     * @ env singlesubdir
     * @ env multisite
     */
    public function _before( AcceptanceTester $I ) {
        $I->amOnPage( '/wp-login.php' );
        $I->wait( 1 );
        $I->fillField( [ 'name' => 'log'], 'admin' );
        $I->fillField( [ 'name' => 'pwd'], 'password' );
        $I->click( '#wp-submit' );
        //$I->see( 'Dashboard' );
        $I->amOnPage( '/wp-admin/admin.php?page=wpstg_clone' );
        $I->wait( 2 );
    }

    public function deleteSite( AcceptanceTester $I ) {
        $I->see( 'staging', '#staging' );
        $I->click( '#staging .wpstg-remove-clone' );
        $I->waitForElementVisible( "#wpstg-remove-clone", 7 );
        $I->click( '#wpstg-remove-clone' );
        $I->waitForElementNotVisible( '#wpstg-removing-clone', 20 );
        $I->wait( 5 );
    }

    public function cloneSite( AcceptanceTester $I ) {
        // Delete site if it exists
        // 
//        $I->performOn('#staging .wpstg-remove-clone', \Codeception\Util\ActionSequence::build()
//                ->click('#staging .wpstg-remove-clone')
//                ->waitForElementVisible( "#wpstg-remove-clone", 7 )
//                ->click( '#wpstg-remove-clone' )
//                ->waitForElementNotVisible( '#wpstg-removing-clone', 20 )
//                ->wait( 2 )
//                );
//      
        //if( $I->canSeeElement( '.wpstg-remove-clone' ) ) {
//        if( $I->seePageHasElement( '//*[@id="staging"]/a[4]' ) ) {
//            $I->see( 'staging', '#staging' );
//            $I->click( '#staging .wpstg-remove-clone' );
//            $I->waitForElementVisible( "#wpstg-remove-clone", 7 );
//            $I->click( '#wpstg-remove-clone' );
//            $I->waitForElementNotVisible( '#wpstg-removing-clone', 20 );
//            $I->wait( 2 );
//        }

        // Create new Staging site
        $I->see( 'Create New Staging Site' );
        $I->click( '#wpstg-new-clone' );
        //$I->wait( 2 );
        $I->waitForElement( '#wpstg-new-clone-id', 5 );
        $I->fillField( '#wpstg-new-clone-id', 'staging' );
        $I->waitForElement( '#wpstg-start-cloning', 5 );
        //$I->wait( 2 );
        $I->click( '#wpstg-start-cloning' );
        $I->waitForElementVisible( '#wpstg-clone-url', 200 );
        $I->see( 'Congratulations' );
        $I->click( '#wpstg-clone-url' );
        $I->wait( 2 );
        $I->executeInSelenium( function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
            $handles    = $webdriver->getWindowHandles();
            $lastWindow = end( $handles );
            $webdriver->switchTo()->window( $lastWindow );
        } );
        $I->fillField( [ 'name' => 'wpstg-username'], 'admin' );
        $I->fillField( [ 'name' => 'wpstg-pass'], 'password' );
        $I->click( '#wp-submit' );
        //$I->wait( 2 );
        $I->see( 'Dashboard' );
        $I->amOnPage( '/staging/wp-admin/admin.php?page=wpstg_clone' );
        //$I->wait( 2 );
        $I->see( 'This is your staging site. Go to your live site to use that function.', '.wpstg-notice-alert' );
    }

}
