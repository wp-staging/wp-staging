<?php


/**
 * Inherited Methods
 * @method void wantToTest( $text )
 * @method void wantTo( $text )
 * @method void execute( $callable )
 * @method void expectTo( $prediction )
 * @method void expect( $prediction )
 * @method void amGoingTo( $argumentation )
 * @method void am( $role )
 * @method void lookForwardTo( $achieveValue )
 * @method void comment( $description )
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class WebdriverTester extends \Codeception\Actor {
	use _generated\WebdriverTesterActions;

	/**
	 * Warns developer if tests are running without the required --env flag.
	 *
	 * This could cause some assertions to be skipped, which could cause the tests
	 * to succeed by omission.
	 *
	 * This is only needed if the tests are defining a "@env" docblock.
	 */
	public function checkEnvFlag() {
		$allowed_envs = [ 'single', 'multi' ];
		$current_env  = $this->getScenario()->current( 'env' );

		if ( ! in_array( $current_env, $allowed_envs ) ) {
			$message = 'You need to run this test with a --env flag. Allowed values: --env ' . implode( $allowed_envs, ', --env ' );
			throw new \PHPUnit\Framework\ExpectationFailedException( $message );
		}
	}
}
