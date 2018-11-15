<?php

class cloningCest {

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
        //$I->amOnPage( '/wp-admin/admin.php?page=wpstg_clone' );
        //$I->wait( 2 );
    }

    /**
     * Update Staging Site
     * @param AcceptanceTester $I
     */
    public function updateContent( AcceptanceTester $I ) {
        
        // Create New Content On Production Site
        $I->amOnPage( '/wp-admin/edit.php' );
        $I->click( '//*[@id="post-1"]/td[1]/strong/a' );
        $I->fillField( '#title', 'Updated Content' );
        $I->click( '#publish' );
        
        // Reset Content Production Site
        $I->amOnPage( '/wp-admin/admin.php?page=wpstg_clone' );
        $I->waitForElement( '//*[@id="staging"]/a[3]', 3 );
        $I->click( '//*[@id="staging"]/a[3]' );
        $I->waitForElement( '//*[@id="wpstg-start-updating"]', 3 );
        $I->click( '//*[@id="wpstg-start-updating"]' );
        $I->acceptPopup();
        $I->waitForText( 'The job finished!', 200, '#wpstg-log-details' );

        // Update Staging Site
        $I->amOnPage( '/wp-admin/admin.php?page=wpstg_clone' );
        $I->waitForElement( '//*[@id="staging"]/a[3]', 3 );
        $I->click( '//*[@id="staging"]/a[3]' );
        $I->waitForElement( '//*[@id="wpstg-start-updating"]', 3 );
        
        // Start Update
        $I->click( '//*[@id="wpstg-start-updating"]' );
        $I->acceptPopup();
        $I->waitForText( 'The job finished!', 200, '#wpstg-log-details' );

        // Verify updated staging site
        $I->amOnPage( '/staging' );
        $I->fillField( [ 'name' => 'wpstg-username'], 'admin' );
        $I->fillField( [ 'name' => 'wpstg-pass'], 'password' );
        $I->click( '#wp-submit' );
        $I->amOnPage( '/staging' );
        $I->see( 'Updated Content' );
        
        // Reset Content On Production Site
        $I->amOnPage( '/wp-admin/edit.php' );
        $I->click( '//*[@id="post-1"]/td[1]/strong/a' );
        $I->fillField( '#title', 'Reset Content' );
        $I->click( '#publish' );
    }

}
