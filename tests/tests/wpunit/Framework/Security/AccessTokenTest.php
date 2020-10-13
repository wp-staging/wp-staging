<?php

namespace Framework\Security;

use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\Security\Capabilities;

class AccessTokenTest extends \Codeception\TestCase\WPTestCase
{
    /**
     * @var \WpunitTester
     */
    protected $tester;

    /** @var AccessToken */
    protected $accessToken;

    public function setUp(): void
    {
        // Before...
        parent::setUp();
        // Your set up methods here.

        $this->accessToken = new AccessToken;
    }

    public function tearDown(): void
    {
        // Your tear down methods here.

        // Then...
        parent::tearDown();
    }

    protected function generateTokenForTests()
    {
        /** @var \WP_User $user */
        $user = $this->tester->factory()->user->create_and_get();
        $user->add_cap((new Capabilities)->manageWPSTG());

        set_current_user($user->ID);

        $newToken = $this->accessToken->generateNewToken();

        // Sets the user with capabilities to generate the token,
        // then reset the current user to logged-out for test isolation.
        set_current_user(0);

        return $newToken;
    }

    /** @test */
    public function shouldGetTokenWithCap()
    {
        $newToken = $this->generateTokenForTests();

        /** @var \WP_User $user */
        $user = $this->tester->factory()->user->create_and_get();
        $user->add_cap((new Capabilities)->manageWPSTG());

        set_current_user($user->ID);

        $this->assertEquals($newToken, $this->accessToken->getToken());
    }

    /** @test */
    public function shouldNotGetTokenWithoutCap()
    {
        $newToken = $this->generateTokenForTests();

        /** @var \WP_User $user */
        $user = $this->tester->factory()->user->create_and_get();

        set_current_user($user->ID);

        $this->assertFalse($this->accessToken->getToken());
    }

    /** @test */
    public function shouldGenerateTokenWithCap()
    {
        /** @var \WP_User $user */
        $user = $this->tester->factory()->user->create_and_get();
        $user->add_cap((new Capabilities)->manageWPSTG());

        set_current_user($user->ID);

        $this->assertIsString($this->accessToken->generateNewToken());
        $this->assertTrue(strlen($this->accessToken->generateNewToken()) === 64);
    }

    /** @test */
    public function shouldNotGenerateTokenWithoutCap()
    {
        /** @var \WP_User $user */
        $user = $this->tester->factory()->user->create_and_get();

        set_current_user($user->ID);

        $this->assertFalse($this->accessToken->generateNewToken());
    }

    /** @test */
    public function shouldValidateValidToken()
    {
        $newToken = $this->generateTokenForTests();

        $this->assertTrue($this->accessToken->isValidToken($newToken));
    }

    /** @test */
    public function shouldNotValidateInvalidToken()
    {
        $newToken = $this->generateTokenForTests();

        // Slightly different token
        $this->assertFalse($this->accessToken->isValidToken($newToken . ' '));

        // Empty token
        $this->assertFalse($this->accessToken->isValidToken(''));

        // Different token with same length
        $this->assertFalse($this->accessToken->isValidToken(wp_generate_password(64, false)));
    }
}
