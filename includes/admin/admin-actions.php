<?php
/**
 * Admin Actions
 *
 * @package     WPSTG
 * @subpackage  Admin/Actions
 * @copyright   Copyright (c) 2014, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Processes all WPSTG actions sent via POST and GET by looking for the 'wpstg-action'
 * request and running do_action() to call the function
 *
 * @since 1.0
 * @return void
 */
function wpstg_process_actions() {
	if ( isset( $_POST['wpstg-action'] ) ) {
		do_action( 'wpstg_' . $_POST['wpstg-action'], $_POST );
	}

	if ( isset( $_GET['wpstg-action'] ) ) {
		do_action( 'wpstg_' . $_GET['wpstg-action'], $_GET );
	}
}
add_action( 'admin_init', 'wpstg_process_actions' );



function wpstg_save_order(){
        global $wpstg_options;
        // Get all settings
        
        $current_list = get_option('wpstg_networks');
        $new_order = $_POST['wpstg_list'];
        $new_list = array();
   
        /* First write the sort order */
        foreach ($new_order as $n){
            if (isset($current_list[$n])){
                $new_list[$n] = $current_list[$n];
                
            }
        }
        //print_r($_POST);
        /* Update sort order of networks */
        update_option('wpstg_networks', $new_list);
        die();
}
add_action ('wp_ajax_wpstg_update_order', 'wpstg_save_order');