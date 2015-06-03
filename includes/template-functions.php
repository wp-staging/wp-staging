<?php
/**
 * Template Functions
 *
 * @package     WPSTG
 * @subpackage  Functions/Templates
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function wpstg_clone_page() {
	echo 'Click <a id="wpstg-clone-db" href="#">:D</a> to clone DB';
}

function wpsgt_clone_db() {
	global $wpdb;

	$prefix = 'new_'; //get from options
	$tables = $wpdb->get_results('show tables', OBJECT_K);

	foreach ($tables as $table => $whatever) {
		$newTable = $prefix . $table;
		if ($wpdb->query('create table ' . $newTable . ' like ' . $table))
			$wpdb->query('insert ' . $newTable . ' select * from ' . $table);
	}
}
add_action('wp_ajax_wpstg_clonedb', 'wpsgt_clone_db');