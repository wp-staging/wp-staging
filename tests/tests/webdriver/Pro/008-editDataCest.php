<?php

/**
 * Test to edit a staging sites data
 * See Reconnect Staging site: https://wp-staging.com/docs/reconnect-staging-site-to-production-website/
 */

class editDataCest
{
    // Original data
    const STAGING_NAME = 'staging';
    const STAGING_PATH = '/var/www/single_tests/staging/';
    const STAGING_URL = 'https://single.tests.wp-staging.local/staging';
    const STAGING_PREFIX = 'wpstg0_';
    const STAGING_DBUSER = '';
    const STAGING_DBPASSWORD = '';
    const STAGING_DBNAME = '';
    const STAGING_DBHOSTNAME = '';

    // Edited site data
    const STAGING_NAME_EDIT = 'newsite';
    const STAGING_PATH_EDIT = '/var/www/single_tests/newsite/';
    const STAGING_URL_EDIT = 'https://single.tests.wp-staging.local/newsite';
    const STAGING_PREFIX_EDIT = 'wpstg100_';
    const STAGING_DBUSER_EDIT = 'dbuser';
    const STAGING_DBPASSWORD_EDIT = '!"§$%&//())';
    const STAGING_DBNAME_EDIT = 'databasename100';
    const STAGING_DBHOSTNAME_EDIT = 'localhost:3306';



    /**
     * Login to WordPress
     */
    public function _before(WebdriverTester $I)
    {
        $I->loginAsAdmin(10, 3);
        $I->amOnPage('/wp-admin/admin.php?page=wpstg_clone');
        $I->waitForJqueryAjax(30);
    }


    /**
     * Revert the staging site data to original one
     * @param WebdriverTester $I
     */
    public function _after(WebdriverTester $I)
    {
        $I->loginAsAdmin(10, 3);
        $I->amOnPage('/wp-admin/admin.php?page=wpstg_clone');
        $I->waitForJqueryAjax(30);

        $I->click('.wpstg-edit-clone-data');

        $I->tryToSeeElement('#wpstg-edit-clone-data-directory-name');

        $I->fillField('#wpstg-edit-clone-data-directory-name', self::STAGING_NAME);
        $I->fillField('#wpstg-edit-clone-data-path', self::STAGING_PATH);
        $I->fillField('#wpstg-edit-clone-data-url', self::STAGING_URL);
        $I->fillField('#wpstg-edit-clone-data-prefix', self::STAGING_PREFIX);
        $I->fillField('#wpstg-edit-clone-data-database-user', self::STAGING_DBUSER);
        $I->fillField('#wpstg-edit-clone-data-database-password', self::STAGING_DBPASSWORD);
        $I->fillField('#wpstg-edit-clone-data-database-database', self::STAGING_DBNAME);
        $I->fillField('#wpstg-edit-clone-data-database-server', self::STAGING_DBHOSTNAME);
        $I->fillField('#wpstg-edit-clone-data-database-prefix', self::STAGING_PREFIX);

        $I->click('#wpstg-save-clone-data');

        $I->wait(1);
    }

    /**
     * Edit Data of existing staging site named "staging"
     *
     * @param WebdriverTester $I
     *
     * @env single
     */
    public function editData(WebdriverTester $I)
    {
        $I->click('.wpstg-edit-clone-data');

        $I->tryToSeeElement('#wpstg-edit-clone-data-directory-name');

        $I->fillField('#wpstg-edit-clone-data-directory-name', self::STAGING_NAME_EDIT );
        $I->fillField('#wpstg-edit-clone-data-path', self::STAGING_PATH_EDIT );
        $I->fillField('#wpstg-edit-clone-data-url', self::STAGING_URL_EDIT );
        $I->fillField('#wpstg-edit-clone-data-prefix', self::STAGING_PREFIX_EDIT );
        $I->fillField('#wpstg-edit-clone-data-database-user', self::STAGING_DBUSER_EDIT );
        $I->fillField('#wpstg-edit-clone-data-database-password', self::STAGING_DBPASSWORD_EDIT );
        $I->fillField('#wpstg-edit-clone-data-database-database', self::STAGING_DBNAME_EDIT );
        $I->fillField('#wpstg-edit-clone-data-database-server', self::STAGING_DBHOSTNAME_EDIT );
        $I->fillField('#wpstg-edit-clone-data-database-prefix', self::STAGING_PREFIX_EDIT );

        $I->click('#wpstg-save-clone-data');

        $I->wait(1);


        // Verify data
        $I->tryToSeeElement('Your Staging Sites:');

        $I->see(self::STAGING_NAME_EDIT);
        $I->see(self::STAGING_PATH_EDIT);
        $I->see(self::STAGING_URL_EDIT);
        $I->see(self::STAGING_PREFIX_EDIT);

        // Click on button UPDATE

        $I->waitForElement('//*[@id="' . self::STAGING_NAME_EDIT . '"]/a[3]', 3);
        $I->click('//*[@id="' . self::STAGING_NAME_EDIT . '"]/a[3]');

        $I->waitForElement('//*[@id="wpstg-workflow"]/div/a[3]/span/input');
        $I->click('//*[@id="wpstg-workflow"]/div/a[3]/span/input');

        $I->waitForElement('#wpstg_db_username');
        $I->seeInField('#wpstg_db_username', self::STAGING_DBUSER_EDIT  );
        $I->seeInField('#wpstg_db_database', self::STAGING_DBNAME_EDIT );
        $I->seeInField('#wpstg_db_server', self::STAGING_DBHOSTNAME_EDIT );

        // Password is not visible for the user. Create Unit test?
        //$I->see('!"§$%&//())');

    }

}
