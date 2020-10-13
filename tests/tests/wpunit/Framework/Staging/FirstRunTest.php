<?php

namespace WPStaging\Framework;

use WPStaging\Framework\Staging\FirstRun;

class FirstRunTest extends \Codeception\TestCase\WPTestCase
{
    /**
     * @var \WpunitTester
     */
    protected $tester;

    const FIRST_RUN_KEY = 'wpstg_first_run_test_key';

    const FIRST_RUN_VALUE = 5;

    const IS_STAGING_SITE_KEY = 'wpstg_is_staging_site';

    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        delete_option(FirstRun::FIRST_RUN_KEY);
        delete_option(self::IS_STAGING_SITE_KEY);
        delete_transient(self::FIRST_RUN_KEY);
    }

    /**
     * Test if FirstRun is executed on staging site
     */
    public function testFirstRunOnStagingSite()
    {
        // Set staging site
        update_option(self::IS_STAGING_SITE_KEY, 'true');

        // try triggering first run
        update_option(FirstRun::FIRST_RUN_KEY, 'true');

        // Set transient via add_action. This is executed only if class works as expected
        add_action('wpstg.clone_first_run',
            function () {
                set_transient(self::FIRST_RUN_KEY, self::FIRST_RUN_VALUE);
            });

        (new FirstRun())->init();

        $this->assertEquals(self::FIRST_RUN_VALUE, get_transient(self::FIRST_RUN_KEY));
    }


    /**
     * Test if FirstRun not executed on production site
     */
    public function testDoNotFirstRunIfNotStagingSite()
    {
        // Is production site
        delete_option(self::IS_STAGING_SITE_KEY, 'true');

        // try triggering first run
        update_option(FirstRun::FIRST_RUN_KEY, 'true');

        // Set transient via add_action. This is executed only if class works as expected.
        add_action('wpstg.clone_first_run',
            function () {
                set_transient(self::FIRST_RUN_KEY, self::FIRST_RUN_VALUE);
            });

        (new FirstRun())->init();

        $this->assertFalse(get_transient(self::FIRST_RUN_KEY));
    }

    /**
     * Test if FirstRun is only executed one time
     */
    public function testDoNotFirstRunIfAlreadyRun()
    {
        // Is staging site
        update_option(self::IS_STAGING_SITE_KEY, 'true');

        // Delete first run key to simulate already run
        delete_option(FirstRun::FIRST_RUN_KEY, 'true');

        // Set transient via add_action. This is executed only if class works as expected.
        add_action('wpstg.clone_first_run',
            function () {
                set_transient(self::FIRST_RUN_KEY, self::FIRST_RUN_VALUE);
            });

        (new FirstRun())->init();

        $this->assertFalse(get_transient(self::FIRST_RUN_KEY));
    }


}
