<?php

/* wpstgDebug
 * 
 * Based on WP Log in browser by MZAWeb
 * http://wordpress.org/plugins/wp-log-in-browser
 * 
 * @package     WPSTG
 * @subpackage  Classes/debug
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.9.0
 * @author MZAWeb
 * @author Rene Hermenau
 * 
 */

if ( class_exists( "wpstgDebug" ) )
	return;

class wpstgDebug implements iWPstgDebug {

	private static $instance;
	private $path;

	private $interfaces = array();
	private static $timers = array();
	private static $memory = array();

	public function __construct() {

		$this->path = trailingslashit( dirname( dirname( __FILE__ ) ) );

		$this->_get_interfaces();

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'shutdown', array( $this, 'shutdown' ) );

	}

	/************* API **************/

	/**
	 *
	 * @param mixed  $var
	 * @param string $label
	 *
	 * @return wpstgDebug
	 */
	public function log( $var, $label = null ) {
		$this->_run( 'log', array( $var, $label ) );
		return $this;
	}

	/**
	 *
	 * @param mixed  $var
	 * @param string $label
	 *
	 * @return wpstgDebug
	 */
	public function info( $var, $label = null ) {
		$this->_run( 'info', array( $var, $label ) );
		return $this;
	}

	/**
	 * @param mixed  $var
	 * @param string $label
	 *
	 * @return wpstgDebug
	 */
	public function warn( $var, $label = null ) {
		$this->_run( 'warn', array( $var, $label ) );
		return $this;
	}

	/**
	 * @param mixed  $var
	 * @param string $label
	 *
	 * @return wpstgDebug
	 */
	public function error( $var, $label = null ) {
		$this->_run( 'error', array( $var, $label ) );
		return $this;
	}

	/**
	 * @static
	 *
	 * @param string    $key
	 * @param bool      $log
	 *
	 * @return bool|float
	 *
	 */
	public function timer( $key, $log = false ) {
		if ( !isset( self::$timers[$key] ) ) {
			$time               = microtime();
			$time               = explode( ' ', $time );
			$time               = $time[1] + $time[0];
			self::$timers[$key] = $time;
			return false;

		} else {
			$time       = microtime();
			$time       = explode( ' ', $time );
			$time       = $time[1] + $time[0];
			$finish     = $time;
			$total_time = round( ( $finish - self::$timers[$key] ), 4 );

			if ( $log )
				$this->log( $total_time, $key );

			unset( self::$timers[$key] );

			return $total_time;
		}
	}


	/**
	 * @static
	 *
	 * @param string    $key
	 * @param bool      $log
	 *
	 * @return bool|float
	 *
	 */
	public function memory( $key, $log = false ) {
		if ( !isset( self::$memory[$key] ) ) {
			$memory             = memory_get_usage();
			self::$memory[$key] = $memory;

			return false;
		} else {
			$memory       = memory_get_usage();
			$total_memory = $memory - self::$memory[$key];

			if ( $log )
				$this->log( $total_memory, $key );

			unset( self::$memory[$key] );

			return $total_memory;
		}
	}

	/************* API **************/

	public function init() {
		ob_start();
	}

	public function shutdown() {
		if ( ob_get_level() )
			ob_end_flush();
	}

	/**
	 * @return bool
	 */
	private function _should_run() {
                global $wpstg_options;
		//$enabled        = apply_filters( 'wplinb-enabled', true );
		$match_wp_debug = apply_filters( 'wplinb-match-wp-debug', false );
                $enabled = isset($wpstg_options['debug_mode']) ? $wpstg_options['debug_mode'] : false;

		if ( !$enabled )
			return false;

		if ( !$match_wp_debug )
			return true;

		if ( WP_DEBUG )
			return true;

		return false;

	}

	/**
	 * @param  string $command
	 * @param array   $params
	 */
	private function _run( $command, $params = array() ) {

		if ( !$this->_should_run() )
			return;

		if ( empty( $this->interfaces ) )
			return;

		foreach ( $this->interfaces as $interface ) {
			try {
				call_user_func_array( array( $interface, $command ), $params );
			} catch ( Exception $e ) {

			}
		}
	}

	/**
	 *
	 */
	private function _get_interfaces() {
		// This will come from the admin config
		$selected_interfaces = array( 'FirePHP', 'ChromePHP' );

		$selected_interfaces = apply_filters( 'wplinb-selected-interfaces', $selected_interfaces );

		foreach ( (array)$selected_interfaces as $interface_name ) {
			$interface = $this->_get_interface( $interface_name );
			if ( !empty( $interface ) )
				$this->interfaces[] = $interface;

		}
	}

	/**
	 * @param $interface_name
	 *
	 * @return mixed|null|WPChromePHP|WPFirePHP
	 */
	private function _get_interface( $interface_name ) {

		switch ( $interface_name ) {

			case 'FirePHP':
				include $this->path . 'browsers/WPFirePHP.class.php';
				$interface = new WPFirePHP();
				break;

			case 'ChromePHP':
				include $this->path . 'browsers/WPChromePHP.class.php';
				$interface = new WPChromePHP();
				break;

			default:
				$interface = apply_filters( 'wplinb-get-interface', null, $interface_name );

				if ( $interface && !in_array( 'iWPstgDebug', class_implements( $interface ) ) )
					$interface = null;

				break;
		}

		return $interface;
	}

	/**
	 * @static
	 * @return wpstgDebug
	 *
	 * Return an instance of this class. Singleton.
	 */
	public static function instance() {
		if ( !isset( self::$instance ) ) {
			$className      = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}
}

if ( !function_exists( 'wpstgdebug' ) ) {
	/**
	 * Returns a wpstgDebug instance.
	 *
	 * @return wpstgDebug
	 */
	function wpstgdebug() {
		return wpstgDebug::instance();
	}
}
