<?php
/**
 * Template Functions
 *
 * @package     WPSTG
 * @subpackage  Functions/Templates
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.9.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

//error_reporting(-1);
//ini_set('display_errors', 'On');

/**
 * Main Page
 *
 * Renders the main WP-Staging page contents.
 *
 * @since 1.0
 * @return void
*/

/* Global vars
 *
 */

$state_data = ''; 
$start_time = microtime(true);

function wpstg_clone_page() {
	ob_start();
	?>
	<div id="wpstg-clonepage-wrapper">
            <span class="wp-staginglogo"><img src="<?php echo WPSTG_PLUGIN_URL . 'assets/images/logo_clean_small_212_25.png';?>"></span><span class="wpstg-version"><?php if (WPSTG_SLUG === 'wp-staging-pro') {echo 'Pro';} ?> Version <?php echo WPSTG_VERSION . ''; ?></span>
			<div class="wpstg-header">
				<iframe src="//www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwordpress.org%2Fplugins%2Fwp-staging%2F&amp;width=100&amp;layout=button&amp;action=like&amp;show_faces=false&amp;share=true&amp;height=35&amp;appId=449277011881884" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:96px; height:20px;" allowTransparency="true"></iframe>
				<a class="twitter-follow-button" href="https://twitter.com/wpstg" data-size="small" id="twitter-wjs" style="display: block;">Follow @wpstg</a>
                                &nbsp;<a class="twitter-follow-button" href="https://twitter.com/renehermenau" data-size="small" id="twitter-wjs" style="display: block;">Follow @renehermenau</a>
                                &nbsp;<a href="https://twitter.com/intent/tweet?button_hashtag=wpstaging&text=Check%20out%20this%20plugin%20for%20creating%20a%20one%20click%20WordPress%20testing%20site&via=wpstg" class="twitter-hashtag-button" data-size="small" data-related="ReneHermenau" data-url="https://wordpress.org/plugins/wp-staging/" data-dnt="true">Tweet #wpstaging</a>
			</div>
			<?php do_action('wpstg_notifications');?>
			<?php if (is_multisite()) {
				echo '<span class="wpstg-notice-alert" style="margin-top:20px;">' . __('WordPress Multisite is currently not supported!', 'wpstg') . '</span>'; 
				exit;
			}?>
		<ul id="wpstg-steps">
			<li class="wpstg-current-step"><span class="wpstg-step-num">1</span><?php echo __('Overview', 'wpstg');?></li>
			<li><span class="wpstg-step-num">2</span><?php echo __('Scanning', 'wpstg');?></li>
			<li><span class="wpstg-step-num">3</span><?php echo __('Cloning', 'wpstg');?></li>
			<li><span href="#" id="wpstg-loader" style="display:none;"></span></li>
		</ul> <!-- #wpstg-steps -->
		<div id="wpstg-workflow">
			<?php echo wpstg_overview(false); ?>
		</div> <!-- #wpstg-workflow -->
                <div id="wpstg-error-wrapper">
                    <div id="wpstg-error-details"></div>
                </div>
	</div> <!-- #wpstg-clonepage-wrapper -->
	<?php
                echo wpstg_get_sidebar(); 
		echo ob_get_clean();
}

/**
 * Renders the sidebar
 * 
 * @return string
 */
function wpstg_get_sidebar(){
    $html = '<div class="wpstg-sidebar">side bar here</div>';
    return $html;
}

/**
 * 1st step: Overview
 * Renders the overview page content
 * 
 * @global type $wpstg_clone_details
 */
function wpstg_overview() {
	global $wpstg_clone_details;
	$wpstg_clone_details = wpstg_get_options();
	$existing_clones = get_option('wpstg_existing_clones', array());

	?>
	<?php if (isset($wpstg_clone_details['current_clone'])) : ?>
		Current clone: <?php echo $wpstg_clone_details['current_clone']; ?>
		<a href="#" id="wpstg-reset-clone" class="wpstg-link-btn button-primary" data-clone="<?php echo $wpstg_clone_details['current_clone']; ?>">Reset</a>
		<a href="#" class="wpstg-next-step-link wpstg-link-btn button-primary" data-action="wpstg_scanning">Continue</a>
	<?php else : ?>
		<a href="#" id="wpstg-new-clone" class="wpstg-next-step-link wpstg-link-btn button-primary" data-action="wpstg_scanning"><?php echo __('Create new staging site', 'wpstg'); ?></a>
	<?php endif; ?>
	<br>
	<div id="wpstg-existing-clones">
		<?php if (!empty($existing_clones)) : ?>
			<h3><?php _e('Available Staging Sites:', 'wpstg'); ?></h3>
			<?php foreach ($existing_clones as $clone) : ?>
				<div class="wpstg-clone" id="<?php echo $clone; ?>">
					<a href="<?php echo get_home_url() . "/$clone/wp-login.php"; ?>" class="wpstg-clone-title" target="_blank"><?php echo $clone; ?></a>
                                        <?php echo apply_filters('wpstg_before_stage_buttons', $html = '', $clone); ?>
					<a href="<?php echo get_home_url() . "/$clone/wp-login.php"; ?>" class="wpstg-open-clone wpstg-clone-action" target="_blank"><?php _e('Open', 'wpstg'); ?></a>
					<a href="#" class="wpstg-execute-clone wpstg-clone-action" data-clone="<?php echo $clone; ?>"><?php _e('Edit', 'wpstg'); ?></a>
					<a href="#" class="wpstg-remove-clone wpstg-clone-action" data-clone="<?php echo $clone; ?>"><?php _e('Delete', 'wpstg'); ?></a>
					<!--<a href="#" class="wpstg-edit-clone wpstg-clone-action" data-clone="<?php //echo $clone; ?>"><?php //_e('Edit', 'wpstg'); ?></a>-->
                                        <?php echo apply_filters('wpstg_after_stage_buttons', $html = '', $clone); ?>
				</div> <!-- .wpstg-clone -->
			<?php endforeach; ?>
		<?php endif; ?>
	</div> <!-- #wpstg-existing-clones -->
	<div id="wpstg-removing-clone">

	</div> <!-- #wpstg-removing-clone -->
	<?php
	if (check_ajax_referer('wpstg_ajax_nonce', 'nonce', false))
		wp_die();
}
add_action('wp_ajax_wpstg_overview', 'wpstg_overview');


/**
 * 2nd step: Scanning
 * Collect database and file data for clone
 * 
 * @global object $wpdb
 * @global array $wpstg_clone_details
 * @global array $all_files
 */
function wpstg_scanning() {
	global $wpdb, $wpstg_clone_details, $all_files;
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$wpstg_clone_details = wpstg_get_options();
        
        $unchecked_tables = array();
	$excluded_folders = array();
	$clone_path = 'value="' . get_home_path() . '"';

	$disabled = isset($wpstg_clone_details['current_clone']) ? 'disabled' : false;
	$clone = isset($_POST['clone']) ? $_POST['clone']
		: (isset($wpstg_clone_details['current_clone']) ? $wpstg_clone_details['current_clone'] : false);

        if ($clone) {
		//$wpstg_profile = wpstg_get_profile($clone);
		//$unchecked_tables = $wpstg_profile['unchecked_tables'];
		//$excluded_folders = $wpstg_profile['excluded_folders'];
		//$excluded_folders[] = get_home_path() . $wpstg_profile['name'];
		//$clone_path = 'value = "' . $wpstg_profile['path'] . '"';
	}
        

	//Scan DB
	$tables = $wpdb->get_results("show table status like '" . $wpdb->prefix . "_%'");
	$wpstg_clone_details['all_tables'] = $wpdb->get_col("show tables like '" . $wpdb->prefix . "%'");

	//Scan Files
	$wpstg_clone_details['total_size'] = 0;
	unset($wpstg_clone_details['large_files']);
        $folders = wpstg_scan_files(wpstg_get_clone_root_path());
        
	array_pop($folders);

	$path = wpstg_get_upload_dir() . '/remaining_files.json';
	file_put_contents($path, json_encode($all_files));

	wpstg_save_options();

	$clone_id = '';
	if (isset($wpstg_clone_details['current_clone']))
		$clone_id = 'value="' . $wpstg_clone_details['current_clone'] . '" disabled';

	//$free_space = function_exists('disk_free_space') ? disk_free_space(get_home_path()) : '';
	//$overflow = $free_space < $wpstg_clone_details['total_size'] ? true : false;
	?>
	<label id="wpstg-clone-label" for="wpstg-new-clone">
				<?php echo __('Name your new site, e.g. staging, dev (keep it short):', 'wpstg');?>
		<input type="text" id="wpstg-new-clone-id" value="<?php echo $clone; ?>" <?php echo $disabled; ?>>
	</label>
	<span class="wpstg-error-msg" id="wpstg-clone-id-error">
		<?php 
                        echo wpstg_check_diskspace($wpstg_clone_details['total_size']);
                ?>
	</span>
	<div class="wpstg-tabs-wrapper">
		<a href="#" class="wpstg-tab-header active" data-id="#wpstg-scanning-db">
			<span class="wpstg-tab-triangle">&#9658;</span>
			<?php echo __('DB Tables', 'wpstg'); ?>
		</a>
		<div class="wpstg-tab-section" id="wpstg-scanning-db">
			<?php
				do_action('wpstg_scanning_db');
				echo '<h4 style="margin:0px;">' . __('Uncheck the tables you do not want to copy. (If the copy process was previously interrupted, succesfull copied tables are greyed out and copy process will skip these ones)', 'wpstg') . '<h4>';
				wpstg_show_tables($tables, $unchecked_tables); ?>
		</div> <!-- #wpstg-scanning-db -->

		<a href="#" class="wpstg-tab-header" data-id="#wpstg-scanning-files">
			<span class="wpstg-tab-triangle">&#9658;</span>
			<?php echo __('Files', 'wpstg'); ?>
		</a>
		<div class="wpstg-tab-section" id="wpstg-scanning-files">

			<?php
				echo '<h4 style="margin:0px;">' . __('Uncheck the folders you do not want to copy. Click on them for expanding!', 'wpstg') . '<h4>';
				wpstg_directory_structure($folders, null, false, false, $excluded_folders);
				wpstg_show_large_files();
                                echo '<p><span id=wpstg-file-summary>' . __('Files will be copied into subfolder of: ','wpstg') . wpstg_get_clone_root_path() . '</span>';
			?>
		</div> <!-- #wpstg-scanning-files -->

		<a href="#" class="wpstg-tab-header" data-id="#wpstg-advanced-settings">
			<span class="wpstg-tab-triangle">&#9658;</span>
			<?php echo __('Advanced Options', 'wpstg'); ?>
		</a>
		<div class="wpstg-tab-section" id="wpstg-advanced-settings">
			<?php echo wpstg_advanced_settings(); ?>
		</div> <!-- #wpstg-advanced-settings -->
                
                
	</div>
	<a href="#" class="wpstg-prev-step-link wpstg-link-btn button-primary"><?php _e('Back', 'wpstg'); ?></a>
	<a href="#" id="wpstg-start-cloning" class="wpstg-next-step-link wpstg-link-btn button-primary" data-action="wpstg_cloning"><?php  echo wpstg_return_button_title();?></a>
	<?php
	wp_die();
}
add_action('wp_ajax_wpstg_scanning', 'wpstg_scanning');

/**
 * Display db tables
 * 
 * @param array $tables
 * @param array $unchecked_tables
 * @global array $wpstg_clone_details
 */
function wpstg_show_tables($tables, $unchecked_tables = array()) {
	global $wpstg_clone_details;

	$cloned_tables = isset($wpstg_clone_details['cloned_tables']) ? $wpstg_clone_details['cloned_tables'] : array();
	foreach ($tables as $table) { ?>
		<div class="wpstg-db-table">
			<label>
				<?php
				$attributes = in_array($table->Name, $unchecked_tables) ? '' : 'checked';
				$attributes .= in_array($table->Name, $cloned_tables) ? ' disabled' : '';
				?>
				<input class="wpstg-db-table-checkboxes" type="checkbox" name="<?php echo $table->Name; ?>" <?php echo $attributes; ?>>
				<?php echo $table->Name; ?>
			</label>
			<span class="wpstg-size-info">
				<?php echo wpstg_short_size($table->Data_length + $table->Index_length); ?>
			</span>
		</div>
	<?php } ?>
        <div><a href="#" class="wpstg-button-unselect">Unselect all tables</a></div>
        <?php
}


/**
 * Scan all files and create directory structure
 * 
 * @global array $all_files
 * @global array $wpstg_clone_details
 * @global $wpstg_options $wpstg_options
 * @param string $path
 * @param array $folders
 * @return array
 */
function wpstg_scan_files($path, &$folders = array()) {
	global $all_files, $wpstg_clone_details, $wpstg_options;

	$batch_size = isset($wpstg_options['wpstg_batch_size']) ? $wpstg_options['wpstg_batch_size'] : 20;
	$batch_size *= 1024*1024;
	$wpstg_clone_details['large_files'] = isset($wpstg_clone_details['large_files']) ? $wpstg_clone_details['large_files'] : array();
	$clone = isset($wpstg_clone_details['current_clone']) ? $wpstg_clone_details['current_clone'] : null;

	if (is_dir($path)) {
		$dir = dir($path);
		$dirsize = 0;
                    while ( method_exists($dir,'read') && false !== ($entry = $dir->read()) ) { // works
			if ($entry == '.' || $entry == '..' || $entry == $clone)
				continue;
			//if ( is_file($path . $entry) && !is_null($path) && !empty($path) && !is_null($path) ) {
                        //if (is_file($path . $entry) && is_readable($path . $entry) && !is_null($path) && $path != 'null' && $path != '' && !empty($path)) {
                        if (is_file($path . $entry) && is_readable($path . $entry)) {
				$all_files[] = utf8_encode($path . $entry);
                                //$all_files[] = $path . $entry;
                                //$all_files[] = $path . $entry;
				$dirsize += filesize( $path . $entry);
				if ($batch_size < $size = filesize($path . $entry ))
					$wpstg_clone_details['large_files'][] = $path . $entry;
				$wpstg_clone_details['total_size'] += $size;
				continue;
			}

			$tmp_path = str_replace('//', '/' ,$path . '/' . $entry .'//'); // Make sure that directory contains ending slash / but never double slashes //
			$tmp = wpstg_scan_files($tmp_path, $folders[$entry]);

			$dirsize += $tmp['size'];
		}
		$folders['size'] = $dirsize;
	}
	return $folders;
}

/**
 * Get a list of all files which will be copied
 * 
 * @param string $folder
 * @param array $files
 * @param string $total_size
 * @return array files
 */

function wpstg_get_files($folder, &$files = array(), &$total_size) {
	if (! is_dir($folder))
		return array();
	$dir = dir($folder);

        while (false !== $entry = $dir->read()) {
		if ($entry == '.' || $entry == '..')
			continue;
		if (is_file("$folder/$entry")) {
			$files[] = "$folder/$entry";
			$total_size -= filesize("$folder/$entry");
		} else
			wpstg_get_files("$folder/$entry", $files, $total_size);
                
	}
	return $files;
}

/**
 * Display directory structure with checkboxes
 * 
 * @param array $folders list of folder names
 * @param string $path base path for the directory structure
 * @param bool $not_checked checkbox value
 * @param bool $is_removing
 * @param array $excluded_folders
 */
function wpstg_directory_structure($folders, $path = null, $not_checked = false, $is_removing = false, $excluded_folders = array()) {
	/** @var array $existing_clones */
	$existing_clones = get_option('wpstg_existing_clones', array());
	$path = $path === null ? rtrim(get_home_path(), '/') : $path;

	foreach ($folders as $name => $folder) {
		$size = array_pop($folder);
		if ($is_removing) {
			$not_checked_tmp = false;
		} else {
			if (empty ($excluded_folders))
				$not_checked_tmp = $not_checked ? $not_checked : in_array($name, $existing_clones);
		else
				$not_checked_tmp = in_array("$path/$name", $excluded_folders);
		}
	?>
		<div class="wpstg-dir">
			<input type="checkbox" class="wpstg-check-dir" <?php echo $not_checked_tmp ? '' : 'checked'; ?> name="<?php echo "$path/$name"; ?>">
			<a href="#" class="wpstg-expand-dirs <?php echo $not_checked_tmp ? 'disabled' : ''; ?>"><?php echo $name;?></a>
			<span class="wpstg-size-info"><?php echo wpstg_short_size($size); ?></span>
				<?php if (!empty($folder)) : ?>
					<div class="wpstg-dir wpstg-subdir">
						<?php wpstg_directory_structure($folder, "$path/$name", $not_checked_tmp, $is_removing, $excluded_folders); ?>
					</div>
				<?php endif; ?>
		</div>
	<?php }
}

/**
 * Convert byte into human readable numbers
 * 
 * @param integer $size
 * @return string
 */
function wpstg_short_size($size) {
	if (1 < $out = $size / 1000000000)
		return round($out, 2) . ' Gb';
	else if (1 < $out = $size / 1000000)
		return round($out, 2) . ' Mb';
	else if (1 < $out = $size / 1000)
		return round($out, 2) . ' Kb';
	return $size . ' bytes';
}

/**
 * Display list of large files
 */
function wpstg_show_large_files() {
	global $wpstg_clone_details;

	$large_files = isset($wpstg_clone_details['large_files']) ? $wpstg_clone_details['large_files'] : array();
	if (! empty($large_files)) : ?>
        <br>
        <span style="font-weight:bold;">That might require your attention:</span>
        <br>
        <span id="wpstg-show-large-files"><a href="#">Show large files</a></span>
        
	<div id="wpstg-large-files">
			<h4 style="margin-top: 0px;">
				<?php echo __('We have detected the following large files which could neeed some investigation. Often this files are backup files or other temporary files which must not necessarily copied for creating a staging site:','wpstg');?>
			</h4>
				
		<?php foreach ($large_files as $file) : ?>
			<div class="wpstg-large-file"><li><?php echo $file; ?></li></div>
		<?php endforeach; ?>
	</div> <!-- #wpstg-large-file -->

	<?php endif;
}

/**
 * Display advanced settings
 */
function wpstg_advanced_settings() {
	?>
	Comming soon
	<?php
}

/**
 * Sanitize staging name
 * Lowercase alphanumeric characters, dashes and underscores are allowed. 
 * Uppercase characters will be converted to lowercase.
 * 
 * @param string
 * @return string 
 */

function wpstg_sanitize_key($key){
    return sanitize_key( $key );
}

/**
 * Check the staging name input
 * 
 * - Check if name does not contain more than 17 characters
 * - Check is staging name does not already exists
 * - Sanitize staging name
 * 
 * @return json message | name | percent | running time | status
 */
function wpstg_check_clone() {
	global $wpstg_clone_details;
	$wpstg_clone_details = wpstg_get_options();
	//$cur_clone = preg_replace('/[^A-Za-z0-9]/', '', $_POST['cloneID']);
        $cur_clone = wpstg_sanitize_key($_POST['cloneID']);
	$existing_clones = get_option('wpstg_existing_clones', array());
        strlen($_POST['cloneID']) >= 17 ? $max_length = true : $max_length = false;
	//wp_die(!in_array($cur_clone, $existing_clones));
        if ( $max_length )
            wpstg_return_json('wpstg_check_clone', 'fail', 'Clone name must not be longer than 16 characters', 1, wpstg_get_runtime());
        
        if ( in_array($cur_clone, $existing_clones) )
            wpstg_return_json('wpstg_check_clone', 'fail', 'Clone with same name already exists', 1, wpstg_get_runtime());
       
        if ( !in_array($cur_clone, $existing_clones ) && $max_length !== true )
            wpstg_return_json('wpstg_check_clone', 'success', '', 1, wpstg_get_runtime());  
}
add_action('wp_ajax_wpstg_check_clone', 'wpstg_check_clone');

/**
 * 3rd step: Cloning
 * 
 * @global array $wpstg_clone_details clone related data
 */
function wpstg_cloning() {
	global $wpstg_clone_details;
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$wpstg_clone_details = wpstg_get_options();

	$clone = preg_replace('/[^A-Za-z0-9]/', '', $_POST['cloneID']);
	$wpstg_clone_details['current_clone'] = isset($wpstg_clone_details['current_clone']) ? $wpstg_clone_details['current_clone'] : $clone;

	$wpstg_clone_details['cloned_tables'] = isset($wpstg_clone_details['cloned_tables']) ? $wpstg_clone_details['cloned_tables'] : array();
	if (isset($_POST['uncheckedTables']))
		$wpstg_clone_details['cloned_tables'] = array_merge($wpstg_clone_details['cloned_tables'], $_POST['uncheckedTables']);
	if (isset($_POST['excludedFolders'])) {
		$path = wpstg_get_upload_dir() . '/remaining_files.json';
		$all_files = json_decode(file_get_contents($path), true);

		$excluded_files = array();
                //$excluded_files = array (null, 'null'); //rhe 25.09.2015
		foreach ($_POST['excludedFolders'] as $folder) {
			$tmp_array = array();

			$excluded_files = array_merge($excluded_files, wpstg_get_files($folder, $tmp_array, $wpstg_clone_details['total_size']));    
		}
                $excluded_files = array_map("utf8_encode", $excluded_files ); // convert to utf 8 because $all_files is also utf8
		$remaining_files = array_diff($all_files, $excluded_files);
		file_put_contents($path, json_encode(array_values($remaining_files)));
	}

	$wpstg_clone_details['db_progress'] = isset($wpstg_clone_details['db_progress']) ? $wpstg_clone_details['db_progress'] : 0;
	$wpstg_clone_details['files_progress'] = isset($wpstg_clone_details['files_progress']) ? $wpstg_clone_details['files_progress'] : 0;
	$wpstg_clone_details['links_progress'] = isset($wpstg_clone_details['links_progress']) ? $wpstg_clone_details['links_progress'] : 0;

	wpstg_save_options();

	$unchecked_tables = isset($_POST['uncheckedTables']) ? $_POST['uncheckedTables'] : array();
	$excluded_folders = isset($_POST['excludedFolders']) ? $_POST['excludedFolders'] : array();
	//$clone_path = isset($_POST['path']) ? $_POST['path'] : get_home_path();

	//wpstg_initiate_profile($clone, $unchecked_tables, $excluded_folders, $clone_path);
	do_action('wpstg_start_cloning', $clone);
	?>
	<div class="wpstg-cloning-section"> <?php echo __('Copy database tables', 'wpstg');?>
		<div class="wpstg-progress-bar">
			<div class="wpstg-progress" id="wpstg-db-progress" style="width: <?php echo 100 * $wpstg_clone_details['db_progress']; ?>%;"></div>
		</div>
	</div>
	<div class="wpstg-cloning-section"><?php echo __('Copy files', 'wpstg');?>
		<div class="wpstg-progress-bar">
			<div class="wpstg-progress" id="wpstg-files-progress" style="width: <?php echo 100 * $wpstg_clone_details['files_progress']; ?>%;"></div>
		</div>
	</div>
	<div class="wpstg-cloning-section"><?php echo __('Replace Data', 'wpstg');?>
		<div class="wpstg-progress-bar">
			<div class="wpstg-progress" id="wpstg-links-progress" style="width: <?php echo 100 * $wpstg_clone_details['links_progress']; ?>%"></div>
		</div>
	</div>
	<!--<a href="<?php //echo get_home_url();?>" id="wpstg-clone-url" target="_blank"></a>-->
	<a href="#" id="wpstg-cancel-cloning" class="wpstg-link-btn button-primary"><?php echo __('Cancel', 'wpstg');?></a>
	<!--<a href="#" id="wpstg-home-link" class="wpstg-link-btn button-primary"><?php //echo __('Home', 'wpstg');?></a>
	<a href="#" id="wpstg-try-again" class="wpstg-link-btn button-primary"><?php //echo __('Try Again', 'wpstg');?></a>-->

        <a href="#" id="wpstg-show-log-button" class="button" style="margin-top: 5px;"><?php _e('Display working log','wpstg'); ?></a>
        <div><span id="wpstg-cloning-result"></span></div>
        <div id="wpstg-finished-result"><h3>Congratulations: </h3>
            <?php echo __('WP Staging succesfully created a staging site in a subfolder of your main site in: <strong> ', 'wpstg') . get_home_url(); ?>/<span id="wpstg_staging_name"></span></strong>
            <br><br>
            <?php echo __('Now, you have several options: ', 'wpstg'); ?>
            <br>
            <a href="<?php echo get_home_url();?>" id="wpstg-clone-url" target="_blank" class="wpstg-link-btn button-primary">Open staging site <span style="font-size: 10px;">(login with your admin credentials)</span></a>
            <a href="" class="wpstg-link-btn button-primary" id="wpstg-remove-cloning"><?php echo __('Remove', 'wpstg');?></a>
            <a href="" class="wpstg-link-btn button-primary" id="wpstg-home-link"><?php echo __('Start again', 'wpstg');?></a>
            <div id="wpstg-success-notice">
                <h3 style="margin-top:0px;"><?php _e('Important notes:' ,'wpstg'); ?></h3>
                <ul>
                    <li> <strong>1. Permalinks on your <span style="font-style:italic;">staging site</span> will be disabled for technical reasons! </strong><br>Usually this is no problem for a staging website and you do not have to use permalinks!<br>
                    <p>If you really need permalinks on your staging site you have to do several modifications to your .htaccess (Apache) or *.conf (Nginx). <br>WP Staging can not do this automatically.
                    <p><strong>Read more:</strong>
                    <a href="http://stackoverflow.com/questions/5564881/htaccess-to-rewrite-wordpress-subdirectory-with-permalinks-to-root" target="_blank">Changes .htaccess </a> | <a href="http://robido.com/nginx/nginx-wordpress-subdirectory-configuration-example/" target="_blank">Changes nginx conf</a>
                    </li>
                    <li> <strong>2. Verify that you are REALLY working on your staging site and NOT on your production site if you are uncertain! </strong>
                        <br>Your main and your staging site are both reachable under the same domain so<br> it´s easy to become confused. <p>
                        To assist you we changed the name of the dashboard link to <strong style="font-style:italic;">"Staging - <span class="wpstg-clone-name"><?php echo get_bloginfo('name');?></span>"</strong>. 
                        <br>You will notice this new name in the admin bar:
                        <br><br>
                        <img src="<?php echo WPSTG_PLUGIN_URL . '/assets/images/admin_dashboard.png'; ?>">
                    </li>
                </ul>
            </div>
        </div>
	<div id="wpstg-error-wrapper">
		<div id="wpstg-error-details"></div>
	</div>
        <div id="wpstg-log-details"></div>
	<?php
	wp_die();
}
add_action('wp_ajax_wpstg_cloning', 'wpstg_cloning');

/**
 * Create a clone profile with start settings
 *
 * @param $clone
 * @param $unchecked_tables
 * @param $excluded_folders
 */
function wpstg_initiate_profile($clone, $unchecked_tables, $excluded_folders, $path) {
	$wpstg_profiles = get_option('wpstg_profiles', array());

	$profile = array(
		'name' => $clone,
		'unchecked_tables' => $unchecked_tables,
		'excluded_folders' => $excluded_folders,
		'path' => $path,
	);
	$wpstg_profiles[$clone] = $profile;

	update_option('wpstg_profiles', $wpstg_profiles);
}

function wpstg_get_profile($clone) {
	$wpstg_profiles = get_option('wpstg_profiles', array());

	return isset($wpstg_profiles[$clone]) ? $wpstg_profiles[$clone] : false;
}

/**
 * Helper function to get database object for cloning into external database
 * 
 * @global object $wpdb database object
 * @global array $wpstg_clone_details clone related settings
 */
function wpstg_clone_db() {
	global $wpdb, $wpstg_clone_details;
	$wpstg_clone_details = wpstg_get_options();

	// $clone = $profile = wpstg_get_profile

	$db_helper = apply_filters('wpstg_db_helper', $wpdb, $wpstg_clone_details['current_clone']);
	if ($db_helper->dbname != $wpdb->dbname)
		wpstg_clone_db_external($db_helper);
	else
		wpstg_clone_db_internal();
}
add_action('wp_ajax_wpstg_clone_db', 'wpstg_clone_db');

/**
 * Clone into internal database
 * 
 * @global object $wpdb database object
 * @global array $wpstg_clone_details clone data
 * @global array $wpstg_options global options
 */
function wpstg_clone_db_internal() {
	global $wpdb, $wpstg_clone_details, $wpstg_options;
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$wpstg_clone_details = wpstg_get_options();

        // Start timer
        wpstg_get_runtime();
        
        // Use only for debugging
        //usleep(40000000); 
        
	$progress = isset($wpstg_clone_details['db_progress']) ? $wpstg_clone_details['db_progress'] : 0;
	if ($progress >= 1)
                wpstg_return_json('wpstg_clone_db_internal', 'success', 'DB successfull copied', 1, wpstg_get_runtime());
        

	$limit = isset($wpstg_options['wpstg_query_limit']) ? $wpstg_options['wpstg_query_limit'] : 1000;
	$rows_count = 0;
        $log_data = '';

	while (true) {
		$table = isset($wpstg_clone_details['current_table']) ? $wpstg_clone_details['current_table'] : null;
		$is_new = false;

		if ($table === null) {
			$tables = $wpstg_clone_details['all_tables'];
			$cloned_tables = !empty($wpstg_clone_details['cloned_tables']) ? $wpstg_clone_details['cloned_tables'] : array(); //already cloned tables
			$tables = array_diff($tables, $cloned_tables);
			if (empty($tables)) { //exit condition
				$wpstg_clone_details['db_progress'] = 1;
				wpstg_save_options();
				wpstg_log('DB has been cloned successfully');
                                
                                wpstg_return_json('wpstg_clone_db_internal', 'success', 'DB has been cloned successfully', 1, wpstg_get_runtime());
			}
			$table = reset($tables);
			$is_new = true;
		}

		$new_table = $wpstg_clone_details['current_clone'] . '_' . $table;
		$offset = isset($wpstg_clone_details['offsets'][$table]) ? $wpstg_clone_details['offsets'][$table] : 0;
		$is_cloned = true;

		if ($is_new) {
			$existing_table = $wpdb->get_var(
				$wpdb->prepare(
					'show tables like %s',
					$new_table
				)
			);
			if ($existing_table == $new_table)
				$wpdb->query("drop table $new_table");

			$is_cloned = $wpdb->query("create table `$new_table` like `$table`");
                        wpstg_debug_log("Debug QUERY: create table $new_table like $table");
			$wpstg_clone_details['current_table'] = $table;
		}
		if ($is_cloned) {
			/*$limit -= $rows_count;
			if ($limit < 1)
				break; rhe */
			$inserted_rows = $wpdb->query(
				"insert `$new_table` select * from `$table` limit $offset, $limit"
			);
                        wpstg_debug_log("Debug QUERY: insert $new_table select * from $table limit $offset, $limit");
			if ($inserted_rows !== false) {                          
				$wpstg_clone_details['offsets'][$table] = $offset + $inserted_rows;
				$rows_count += $inserted_rows;
				if ($inserted_rows < $limit) {
					$wpstg_clone_details['cloned_tables'][] = $table;
					$all_tables_count = count($wpstg_clone_details['all_tables']);
					$cloned_tables_count = count($wpstg_clone_details['cloned_tables']);
					$wpstg_clone_details['db_progress'] = $cloned_tables_count / $all_tables_count;
					$log_data .= '[' . date('d-m-Y H:i:s') . '] Copy database table: ' . $wpstg_clone_details['current_table'] . '<br>';
                                        unset($wpstg_clone_details['current_table']);
					wpstg_save_options();
				}

				if ($rows_count > $limit) {
					$all_tables_count = count($wpstg_clone_details['all_tables']);
					$cloned_tables_count = count($wpstg_clone_details['cloned_tables']);
					$wpstg_clone_details['db_progress'] = $cloned_tables_count / $all_tables_count;
					wpstg_save_options();
                                        $log_data .= '[' . date('d-m-Y H:i:s') . '] Copy database table: ' . $table . '<br>';
					wpstg_log( 'Query limit exceeded. Starting new query batch! Table: ' . $table);
                                        wpstg_return_json('wpstg_clone_db_internal', 'success', wpstg_get_log_data($progress) . $log_data, $wpstg_clone_details['db_progress'], wpstg_get_runtime());
				}
			} else {
				wpstg_log('Table ' . $new_table . ' has been created, but inserting rows failed! Rows will be skipped. Offset: ' . $offset . '');
				wpstg_save_options();
                                //wpstg_return_json('wpstg_clone_db_internal', 'fail', 'Table ' . $new_table . ' has been created, BUT inserting rows failed. This happens sometimes when a table had been updated during staging process. Exclude this table from copying and try again. Offset: ' . $offset, $wpstg_clone_details['db_progress'] . ' ', wpstg_get_runtime());
			}
		} else {
			wpstg_log('Creating table ' . $table . ' has been failed.');
			wpstg_save_options();
                        wpstg_return_json('wpstg_clone_db_internal', 'fail', 'Copying table ' . $table . ' has been failed.', $wpstg_clone_details['db_progress'], wpstg_get_runtime());
		}
                $log_data .= '[' . date('d-m-Y H:i:s') . '] Copy database table: ' . $table . ' DB rows: ' . $offset . '<br>';
	} //end while
	wpstg_save_options();               
        wpstg_return_json('wpstg_clone_db_internal', 'success', wpstg_get_log_data($progress) . $log_data, $progress, wpstg_get_runtime());
}
/**
 * 
 * Clone into separate database
 * 
 * @global object $wpdb database object
 * @global array $wpstg_clone_details clone related data
 * @global array $wpstg_options global settings
 * 
 * @param object $db_helper new database object
 */
function wpstg_clone_db_external($db_helper) {
	global $wpdb, $wpstg_clone_details, $wpstg_options;
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$one = 1;
        
	$progress = isset($wpstg_clone_details['db_progress']) ? $wpstg_clone_details['db_progress'] : 0;
	if ($progress >= 1)
		wp_die(1);

	$limit = isset($wpstg_options['wpstg_query_limit']) ? $wpstg_options['wpstg_query_limit'] : 1000;
	$rows_count = 0;

	while (true) {
		$table = isset($wpstg_clone_details['current_table']) ? $wpstg_clone_details['current_table'] : null;
		$is_new = false;

		if ($table === null) {
			$tables = $wpstg_clone_details['all_tables'];
			$cloned_tables = !empty($wpstg_clone_details['cloned_tables']) ? $wpstg_clone_details['cloned_tables'] : array(); //already cloned tables
			$tables = array_diff($tables, $cloned_tables);
			if (empty($tables)) { //exit condition
				$wpstg_clone_details['db_progress'] = 1;
				wpstg_save_options();
				wpstg_log('DB has been cloned successfully');
				wp_die(1);
			}
			$table = reset($tables);
			$is_new = true;
		}

		$new_table = $wpstg_clone_details['current_clone'] . '_' . $table;
		$offset = isset($wpstg_clone_details['offsets'][$table]) ? $wpstg_clone_details['offsets'][$table] : 0;
		$is_cloned = true;

		if ($is_new) {
			$existing_table = $db_helper->get_var(
				$db_helper->prepare(
					'show tables like %s',
					$new_table
				)
			);
			if ($existing_table == $new_table)
				$db_helper->query("drop table $new_table");

			$tmp_result = $wpdb->get_row("show create table $table", ARRAY_N);
			$create_sql = str_replace($tmp_result[0], $new_table, $tmp_result[1], $one);
			$is_cloned = $db_helper->query($create_sql);
			$wpstg_clone_details['current_table'] = $table;
		}
		if ($is_cloned) {
			$limit -= $rows_count;
			if ($limit < 1)
				break;

			$selected_rows = $wpdb->get_results("select * from $table limit $offset, $limit", ARRAY_N);
			$inserted_rows = 0;
			foreach ($selected_rows as $row) {
				$row = esc_sql($row);
				$values = implode("', '", $row);
				$query = "insert into $new_table values('$values')";
				$inserted_rows += $db_helper->query($query);
			}

			if ($inserted_rows !== false) {
				$wpstg_clone_details['offsets'][$table] = $offset + $inserted_rows;
				$rows_count += $inserted_rows;
				if ($inserted_rows < $limit) {
					$wpstg_clone_details['cloned_tables'][] = $table;
					$all_tables_count = count($wpstg_clone_details['all_tables']);
					$cloned_tables_count = count($wpstg_clone_details['cloned_tables']);
					$wpstg_clone_details['db_progress'] = $cloned_tables_count / $all_tables_count;
					unset($wpstg_clone_details['current_table']);
					wpstg_save_options();
				}
				if ($rows_count > $limit) {
					$all_tables_count = count($wpstg_clone_details['all_tables']);
					$cloned_tables_count = count($wpstg_clone_details['cloned_tables']);
					$wpstg_clone_details['db_progress'] = $cloned_tables_count / $all_tables_count;
					wpstg_save_options();
					wpstg_log('Query limit is exceeded. Current Table: ' . $table);
					wp_die($wpstg_clone_details['db_progress']);
				}
			} else {
				wpstg_log('Table ' . $new_table . ' has been created, BUT inserting rows failed. Offset: ' . $offset);
				wpstg_save_options();
				//wp_die(-1);
                                wp_die('Table ' . $new_table . ' has been created, BUT inserting rows failed. Offset: ' . $offset);
			}
		} else {
			wpstg_log('Creating table ' . $table . ' has been failed.');
			wpstg_save_options();
			//wp_die(-1);
                        wp_die('Creating table ' . $table . ' has been failed.');
		}
	} //end while
	wpstg_save_options();
	wp_die($progress);
}

/**
 * Get clone root path. Thats the absolute path to wordpress,
 * no matter if wordpress is installed in a subdirectory or not.
 * This path is everytime the same and is used to determine the clone destination
 * 
 * @global $wpstg_options global settings
 * @return string clone root path
 */
function wpstg_get_clone_root_path() {
    $home_path = ABSPATH;
    return str_replace('\\', '/', $home_path);
}

/**
 * Copy selected files files into staging subfolder
 * 
 * @global $wpstg_clone_details
 * @global $wpstg_options
 * @global $batch
 */
function wpstg_copy_files() {
	global $wpstg_clone_details, $wpstg_options, $batch;
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$wpstg_clone_details = wpstg_get_options();
        
        // Start timer
        wpstg_get_runtime();
        
        // Use only for debugging
        //usleep(40000000); 

	$clone_root_path = get_home_path() . $wpstg_clone_details['current_clone'];
        //$clone_root_path = wpstg_get_clone_root_path() . $wpstg_clone_details['current_clone'];
	$sourcepath = wpstg_get_upload_dir() . '/remaining_files.json';
	$files = json_decode(file_get_contents($sourcepath), true);
	$start_index = isset($wpstg_clone_details['file_index']) ? $wpstg_clone_details['file_index'] : 0;
	$wpstg_clone_details['files_progress'] = isset($wpstg_clone_details['files_progress']) ? $wpstg_clone_details['files_progress'] : 0;
	$batch_size = isset($wpstg_options['wpstg_batch_size']) ? $wpstg_options['wpstg_batch_size'] : 2;
	$batch_size *= 1024*1024;
	$batch = 0;
        $log_data = '';
        $size = 0;
        
	if (!is_dir($clone_root_path))
		mkdir($clone_root_path);

	for ($i = $start_index; $i < count($files); $i++) {
                $new_file = wpstg_create_directories($files[$i], wpstg_get_clone_root_path(), $clone_root_path);
                
                if ( file_exists($files[$i] ) ){
                    $size = filesize($files[$i]);
                }
                
		if ( is_file($files[$i]) && file_exists($files[$i]) && $size > $batch_size )  { // is_file() checks if its a symlink or real file
                //if ( is_file($files[$i] && file_exists($files[$i] && $size > $batch_size) ) ) { // is_file() checks if its a symlink or real file
			if (wpstg_copy_large_file($files[$i], $new_file, $batch_size)) {
				//wpstg_log('Copy LARGE file: ' . $files[$i] . '. Batch size: ' . wpstg_short_size($batch + $size) . ' (' . ($batch + $size) . ' bytes)');
				$wpstg_clone_details['file_index'] = $i + 1;
				//$part = ($batch + $size) / $wpstg_clone_details['total_size'];
                                $part = $batch / $wpstg_clone_details['total_size'];
				$wpstg_clone_details['files_progress'] += $part;
				wpstg_save_options();
                                wpstg_return_json('wpstg_copy_files', 'success', '<br> [' . date('d-m-Y H:i:s') . '] Copy LARGE file: ' . $files[$i] . '. Batch size: ' . wpstg_short_size($batch + $size) . ' (' . ($batch + $size) . ' bytes)', $wpstg_clone_details['files_progress'], wpstg_get_runtime());
			} else {
				wpstg_log('Copying LARGE file has been failed and will be skipped: ' . $files[$i]);
				$wpstg_clone_details['file_index'] = $i + 1; //increment it because we want to skip this file when it can not be copied successfully
				$part = $batch / $wpstg_clone_details['total_size'];
				$wpstg_clone_details['files_progress'] += $part;
				wpstg_save_options();
                                wpstg_return_json('wpstg_copy_files', 'fail', '<br> [' . date('d-m-Y H:i:s') . '] <span style="color:red;">Fail: </span> Copying LARGE file has been failed and will be skipped: ' . $files[$i], $wpstg_clone_details['files_progress'], wpstg_get_runtime());
			}
		}
		if ( $batch_size > $batch + $size ) {
			//if ( is_readable( $files[$i] ) && is_file($files[$i]) && copy($files[$i], $new_file)) {
			if ( is_readable( $files[$i] ) && copy($files[$i], $new_file)) {
				$batch += $size;
                                wpstg_log('Copy file no: ' . $i . ' Total files:' . count($files) .' File: ' . $files[$i] . ' to ' . $new_file); 
                                //wpstg_return_json('wpstg_copy_files', 'success', '[' . date('d-m-Y H:i:s') . '] Copy file no: ' . $i . ' Total files:' . count($files) .' File: ' . $files[$i] . ' to ' . $new_file, $wpstg_clone_details['files_progress'], wpstg_get_runtime());
			} else {
				wpstg_log('Copying file has been failed and will be skipped: ' . $files[$i]);
				$wpstg_clone_details['file_index'] = $i + 1; //increment it because we want to skip this file when it can not be copied successfully
				$part = $batch / $wpstg_clone_details['total_size'];
				$wpstg_clone_details['files_progress'] += $part;
				wpstg_save_options();
				//wp_die(-1);
                                //wp_die('Copying file has been failed: ' . $files[$i]);
                                wpstg_return_json('wpstg_copy_files', 'fail', '[' . date('d-m-Y H:i:s') . '] <span style="color:red;">Fail: </span> Copying file has been failed and will be skipped: ' . $files[$i], $wpstg_clone_details['files_progress'], wpstg_get_runtime());
			}
		} else {
			wpstg_log('Batch size: ' . wpstg_short_size($batch) . ' (' . $batch . ' bytes)' . '. Current File: ' . $files[$i]);
			$wpstg_clone_details['file_index'] = $i;
			$part = $batch / $wpstg_clone_details['total_size'];
			$wpstg_clone_details['files_progress'] += $part;
			wpstg_save_options();

                        wpstg_return_json('wpstg_copy_files', 'success', '[' . date('d-m-Y H:i:s') . '] File copy in progress... ' . round($wpstg_clone_details['files_progress'] * 100, 1) . '%', $wpstg_clone_details['files_progress'], wpstg_get_runtime());
		}
	} // for loop

	$wpstg_clone_details['files_progress'] = 1;
	wpstg_save_options();
	//wp_die(1);
        wpstg_return_json('wpstg_copy_files', 'success', '[' . date('d-m-Y H:i:s') . '] File copy succeeded. Percent 100%', $wpstg_clone_details['files_progress'], wpstg_get_runtime());
}
add_action('wp_ajax_wpstg_copy_files', 'wpstg_copy_files');


/**
 * Create target directory during copy process and returns path to the copied file
 * 
 * @param string $source Source file including full path e.g. var/www/htdocs/mainsite/index.php
 * @param string $home_root_path path of the main wordpress installation e.g. var/www/htdocs/mainsite/
 * @param string $clone_root_path main path of the clone e.g. var/www/htdocs/mainsite/myclone/
 * 
 * @return string full target path e.g. var/www/htdocs/mainsite/myclone/index.php
 * 
 * 
 */
function wpstg_create_directories($source, $home_root_path, $clone_root_path) {
	$path = substr($source, strlen($home_root_path)); // remove the source part from home_root_path
	$folders = explode('/', $path); // convert path into array
	array_pop($folders); // remove the file

        
	$new_folder = $clone_root_path;
        //$new_folder = wpstg_get_clone_root_path();
	foreach ($folders as $folder) {
		$new_folder .= '/' . $folder;
		if (!is_dir($new_folder))
			mkdir($new_folder);
	}

        $destination = $clone_root_path . "/" . $path;
        //$destination = $new_folder . "/" . $path;
	return $destination;
}



/**
 * 
 * Copy large files in chunks
 * Set return value to true, no matter if copying has been successfull or not. 
 * Unsuccessfull copying will be skipped for large files.
 * 
 * @param string $src source path
 * @param string $dst destination path
 * @param integer $buffersize // Not used at the moment
 * @return boolean 
 */
function wpstg_copy_large_file($src, $dst, $buffersize) {
    $src = fopen($src, 'r');
    $dest = fopen($dst, 'w');
    
        // Try first method:
        while (! feof($src)){
                    if (false === fwrite($dest, fread($src, $buffersize))){
                        $error = true;
                    }                 
        }
        // Try second method if first one failed
        if (isset($error) && ($error === true)){
            while(!feof($src)){
                stream_copy_to_stream($src, $dest, 1024 );
            }
            fclose($src);
            fclose($dest);
            return true;
        }
        fclose($src);
        fclose($dest);
        return true;
}



/**
 * Replace all urls in data
 * 
 * @global object $wpdb
 * @global array $wpstg_clone_details
 */

function wpstg_replace_links() {
	global $wpdb, $wpstg_clone_details;
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$wpstg_clone_details = wpstg_get_options();

	$db_helper = apply_filters('wpstg_db_helper', $wpdb, $wpstg_clone_details['current_clone']);
	$new_prefix = $wpstg_clone_details['current_clone'] . '_' . $wpdb->prefix;
	$wpstg_clone_details['links_progress'] = isset($wpstg_clone_details['links_progress']) ? $wpstg_clone_details['links_progress'] : 0;
	//replace site url in options
	if ($wpstg_clone_details['links_progress'] < 0.1) {
		$result = $db_helper->query(
			$db_helper->prepare(
				'update ' . $new_prefix . 'options set option_value = %s where option_name = \'siteurl\' or option_name = \'home\'',
				get_home_url() . '/' . $wpstg_clone_details['current_clone']
			)
		);
		if (!$result) {
                        $wpstg_clone_details['links_progress'] = 0.33;
                        $error = isset($db_helper->dbh->error) ? $db_helper->dbh->error : '';
			wpstg_log_error('Replacing site url has been failed. ' . $error);
			//wp_die($db_helper->dbh->error);
                        wpstg_save_options();
                        wpstg_return_json('wpstg_replace_links', 'fail', '[' . date('d-m-Y H:i:s') . '] <span style="color:red;">Fail: </span>Replacing site url has been failed. DB Error: ' . $error, $wpstg_clone_details['links_progress'], wpstg_get_runtime());
		} else {
			$wpstg_clone_details['links_progress'] = 0.33;
                        wpstg_log('Replacing siteurl has been done succesfully');
			wpstg_save_options();
                        wpstg_return_json('wpstg_replace_links', 'success', '[' . date('d-m-Y H:i:s') . '] Replacing "siteurl" has been done succesfully', $wpstg_clone_details['links_progress'], wpstg_get_runtime());
		}
	}
        //Update wpstg_is_staging_site in clone options table
	if ($wpstg_clone_details['links_progress'] < 0.34) {
		$result = $db_helper->query(
			$db_helper->prepare(
				'update ' . $new_prefix . 'options set option_value = %s where option_name = \'wpstg_is_staging_site\'',
				'true'
			)
		);
		if (!$result) {
                        $wpstg_clone_details['links_progress'] = 0.43;
			wpstg_log('Updating db table ' . $new_prefix . 'options where option_name = wpstg_is_staging_site has been failed');
			//wp_die(-1);
                        //wp_die('Updating option[wpstg_is_staging_site] has been failed');
                        wpstg_save_options();
                        wpstg_return_json('wpstg_replace_links', 'fail', '[' . date('d-m-Y H:i:s') . '] <span style="color:red;">Fail: </span> Updating db ' . $new_prefix . 'options where option_name = wpstg_is_staging_site has been failed', $wpstg_clone_details['links_progress'], wpstg_get_runtime());
		} else {
			$wpstg_clone_details['links_progress'] = 0.43;
                        wpstg_log('Updating option [wpstg_is_staging_site] has been done succesfully');
                        wpstg_save_options();
                        wpstg_return_json('wpstg_replace_links', 'success', '[' . date('d-m-Y H:i:s') . '] Updating option [wpstg_is_staging_site] has been done succesfully', $wpstg_clone_details['links_progress'], wpstg_get_runtime());
		}
	}
        
        //Update rewrite_rules in clone options table
	if ($wpstg_clone_details['links_progress'] < 0.44) {
		$result = $db_helper->query(
			$db_helper->prepare(
				'update ' . $new_prefix . 'options set option_value = %s where option_name = \'rewrite_rules\'',
				''
			)
		);
		if (!$result) {
                        wpstg_log_error('Updating option[rewrite_rules] not successfull, likely the main site is not using permalinks');
                        $wpstg_clone_details['links_progress'] = 0.45;
                        // Do not die here. This db option is not available on sites with permalinks disabled, so we want to continue
			//wp_die(-1);
                        wpstg_save_options();
                        wpstg_return_json('wpstg_replace_links', 'fail', '[' . date('d-m-Y H:i:s') . '] Updating option[rewrite_rules] was not possible. Will be skipped! Usually this is no problem and happens only when main site has no permalinks enabled!', $wpstg_clone_details['links_progress'], wpstg_get_runtime());
		} else {
			$wpstg_clone_details['links_progress'] = 0.45;
                        wpstg_log('Updating option [rewrite_rules] has been done succesfully');
                        wpstg_save_options();
                        wpstg_return_json('wpstg_replace_links', 'success', '[' . date('d-m-Y H:i:s') . '] Updating option [rewrite_rules] has been done succesfully', $wpstg_clone_details['links_progress'], wpstg_get_runtime());

		}
	}

	//replace table prefix in meta_keys
	if ($wpstg_clone_details['links_progress'] < 0.50) {
		$result_options = $db_helper->query(
			$db_helper->prepare(
				'update ' . $new_prefix . 'usermeta set meta_key = replace(meta_key, %s, %s) where meta_key like %s',
				$wpdb->prefix,
				$new_prefix,
				$wpdb->prefix . '_%'
			)
		);
		$result_usermeta = $db_helper->query(
			$db_helper->prepare(
				'update ' . $new_prefix . 'options set option_name = replace(option_name, %s, %s) where option_name like %s',
				$wpdb->prefix,
				$new_prefix,
				$wpdb->prefix . '_%'
			)
		);
		if (!$result_options || !$result_usermeta) {
                        $wpstg_clone_details['links_progress'] = 0.66;
			wpstg_log_error('Updating table ' . $new_prefix . ' has been failed.');
                        wpstg_return_json('wpstg_replace_links', 'fail', '[' . date('d-m-Y H:i:s') . ']<span style="color:red;">Fatal error!</span> Updating table ' . $new_prefix . ' has been failed.', $wpstg_clone_details['links_progress'], wpstg_get_runtime());
			//wp_die(.51);
                        //wp_die('Updating table ' . $new_prefix . ' has been failed.');
		} else {
			$wpstg_clone_details['links_progress'] = 0.66;
                        wpstg_log('Updating db prefix "' . $wpdb->prefix . '" to  "' . $new_prefix . '" has been done succesfully.');
                        wpstg_save_options();
                        wpstg_return_json('wpstg_replace_links', 'success', '[' . date('d-m-Y H:i:s') . '] Updating prefix name ' . $wpdb->prefix . ' to  ' . $new_prefix . ' has been done succesfully.', $wpstg_clone_details['links_progress'], wpstg_get_runtime());
		}
	}

	//replace $table_prefix in wp-config.php
	if ($wpstg_clone_details['links_progress'] < 0.67) {
                $wpstg_clone_details['links_progress'] = 0.67;
                $path = get_home_path() . $wpstg_clone_details['current_clone'] . '/wp-config.php';
		$content = file_get_contents($path);
		if ($content) {
			$content = str_replace('$table_prefix', '$table_prefix = \'' . $new_prefix . '\';//', $content); // replace table prefix
                        $content = str_replace(get_home_url(), wpstg_get_staging_url(), $content); // replace any url 
			if ($db_helper->dbname != $wpdb->dbname) {
				$content = str_replace('define(\'DB_NAME\'', 'define(\'DB_NAME\', \'' . $db_helper->dbname . '\');//', $content);
				$content = str_replace('define(\'DB_USER\'', 'define(\'DB_USER\', \'' . $db_helper->dbuser . '\');//', $content);
				$content = str_replace('define(\'DB_PASSWORD\'', 'define(\'DB_PASSWORD\', \'' . $db_helper->dbpassword . '\');//', $content);
				$content = str_replace('define(\'DB_HOST\'', 'define(\'DB_HOST\', \'' . $db_helper->dbhost . '\');//', $content);
			}
			if (FALSE === file_put_contents($path, $content)) {
                                wpstg_save_options(); //  we have to die hier @to do write function
				wpstg_log($path . ' is not writable');
                                wpstg_return_json('wpstg_replace_links', 'fail', '[' . date('d-m-Y H:i:s') . '] <span style="color:red;">Fatal error: </span>Can not modify ' . $path . ' !', $wpstg_clone_details['links_progress'], wpstg_get_runtime());
                        }else{  
                                wpstg_save_options();
                                wpstg_return_json('wpstg_replace_links', 'success', '[' . date('d-m-Y H:i:s') . '] ' . $path . ' has been successfully modified!', $wpstg_clone_details['links_progress'], wpstg_get_runtime());
                        }
		} else {
			wpstg_log_error($path . ' is not readable.');
                        wpstg_save_options();
                        wpstg_return_json('wpstg_replace_links', 'fail', '[' . date('d-m-Y H:i:s') . '] <span style="color:red;">Fail: </span>' . $path . ' is not writable', $wpstg_clone_details['links_progress'], wpstg_get_runtime());
		}
	}
        
        
        // Replace path in index.php
        wpstg_reset_index_php(0.71);

	$existing_clones = get_option('wpstg_existing_clones', array());
	if (false === array_search($wpstg_clone_details['current_clone'], $existing_clones)) {
            $existing_clones[] = $wpstg_clone_details['current_clone'];
            update_option('wpstg_existing_clones', $existing_clones);
	}

	wpstg_clear_options();

        wpstg_return_json('wpstg_replace_links', 'success', '[' . date('d-m-Y H:i:s') . '] All string replacements have been done successfully', 1, wpstg_get_runtime());
}
add_action('wp_ajax_wpstg_replace_links', 'wpstg_replace_links');

/**
 * Reset index.php to original file
 * Check first if main wordpress is used in subfolder and index.php in parent directory
 *
 * See: https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory
 * 
 * @param $progress decimal value as progress indicatior. 1 = 100% | 0.9 = 90% and so on
 * 
 * Return JSON
 */
function wpstg_reset_index_php($progress){
    global $wpstg_options, $wpstg_clone_details;

    if ( isset( $wpstg_options['wordpress_subdirectory'] ) && $wpstg_options['wordpress_subdirectory'] === "1" && $wpstg_clone_details['links_progress'] < $progress) {
        $wpstg_clone_details['links_progress'] = $progress; 
        $path = get_home_path() . $wpstg_clone_details['current_clone'] . '/index.php';
	$content = file_get_contents($path);
        
        if ($content) {

            $pattern = "/(require(.*)wp-blog-header.php' \);)/";
            if ( !preg_match($pattern, $content, $matches) )
                wpstg_return_json('wpstg_wp_in_subdirectory', 'fail', '[' . date('d-m-Y H:i:s') . '] <span style="color:red;">Fatal error: </span>wp-blog-header.php not included in ' . $path . ' !', $progress, wpstg_get_runtime());
            
            $pattern2 = "/require(.*) dirname(.*) __FILE__ (.*) \. '(.*)wp-blog-header.php'(.*);/";
            $replace = "require( dirname( __FILE__ ) . '/wp-blog-header.php' ); // " . $matches[0] . " // Changed by WP-Staging";
            $content = preg_replace($pattern2, $replace, $content);

            if (FALSE === file_put_contents($path, $content)) {
                wpstg_save_options(); //  we should throw fatal error here to die @todo create function()
                wpstg_log_error($path . ' is not writable');
                wpstg_return_json('wpstg_wp_in_subdirectory', 'fail', '[' . date('d-m-Y H:i:s') . '] <span style="color:red;">Fatal error: </span>Can not modify ' . $path . ' !', $progress, wpstg_get_runtime());
            } else {
                wpstg_save_options();
                wpstg_return_json('wpstg_replace_links', 'success', '[' . date('d-m-Y H:i:s') . '] ' . $path . ' has been successfully modified!', $progress, wpstg_get_runtime());
            }
        } else {
            wpstg_log($path . ' is not readable.');
            wpstg_save_options();
            wpstg_return_json('wpstg_replace_links', 'fail', '[' . date('d-m-Y H:i:s') . '] <span style="color:red;">Fail: </span>' . $path . ' is not writable', $progress, wpstg_get_runtime());
        }
    }
}

/**
 * Clear all task related data in *.json files
 * 
 * @return void
 */
function wpstg_clear_options() {
	$path = wpstg_get_upload_dir() . '/clone_details.json';
		if (wp_is_writable($path)) {
			file_put_contents($path, '');
		}
		wpstg_log(wpstg_get_upload_dir() . '/clone_details.json has been purged successfully');
		
		$path = wpstg_get_upload_dir() . '/remaining_files.json';
		if (wp_is_writable($path)) {
			file_put_contents($path, '');
		}
		wpstg_log(wpstg_get_upload_dir() . '/remaining_files.json has been purged successfully');
}

/** Check the files before removing
 * 
 * @global object $wpdb
 */
function wpstg_preremove_clone() {
	global $wpdb;

	$clone = $_POST['cloneID'];
	$prefix = $clone . '_' . $wpdb->prefix;
	$db_helper = apply_filters('wpstg_db_helper', $wpdb, $clone);
	$tables = $db_helper->get_results("show table status like '" . $prefix . "_%'");

	$path = get_home_path() . $clone;
	$folders[$clone] = wpstg_check_removing_files($path);
	?>
	<h4 class="wpstg-notice-alert">
		<?php _e('Attention: Check carefully if this DB tables and files are safe to delete for the staging site','wpstg'); ?>
		<span style="background-color:#575757;color:#fff;"><?php echo $clone; ?></span>
		<?php _e('Usually the preselected data can be deleted without any risk, but in case something goes wrong you better check it first.','wpstg'); ?> 
		</h4>
	<div class="wpstg-tabs-wrapper">
		<a href="#" class="wpstg-tab-header active" data-id="#wpstg-scanning-db">
			<span class="wpstg-tab-triangle">&#9658;</span>
			<?php echo __('DB tables to remove', 'wpstg');?>
		</a>
		<div class="wpstg-tab-section" id="wpstg-scanning-db">
			<h4 style="margin:0px;">
			<?php
				_e('Select the tables for removal:', 'wpstg');
				wpstg_show_tables($tables);
			?>
			<h4>
		</div> <!-- #wpstg-scanning-db -->

		<a href="#" class="wpstg-tab-header" data-id="#wpstg-scanning-files">
			<span class="wpstg-tab-triangle">&#9658;</span>
			<?php echo __('Files to remove', 'wpstg');?>
		</a>
		<div class="wpstg-tab-section" id="wpstg-scanning-files">
			<h4 style="margin:0px;">
			<?php
				_e('Select the folders for removal. Click on a folder name to expand it:', 'wpstg');
				wpstg_directory_structure($folders, null, false, true); 
			?>
			<h4>
		</div> <!-- #wpstg-scanning-files -->
	</div>
	<a href="#" class="wpstg-link-btn button-primary" id="wpstg-cancel-removing"><?php _e('Cancel', 'wpstg');?></a>
	<a href="#" class="wpstg-link-btn button-primary" id="wpstg-remove-clone" data-clone="<?php echo $clone; ?>"><?php echo __('Remove', 'wpstg');?></a>
	<?php
	wp_die();
}
add_action('wp_ajax_wpstg_preremove', 'wpstg_preremove_clone');

/**
 * Check the folders for removing
 * 
 * @param string $path
 * @param array $folders
 *
 * @return array
 */
function wpstg_check_removing_files($path, &$folders = array()) {
	if (is_dir($path)) {
		$dirsize = 0;
		$dir = dir($path);
		while (false !== $entry = $dir->read()) {
			if ($entry == '.' || $entry == '..')
				continue;
			if (is_file("$path/$entry")) {
				$dirsize += filesize("$path/$entry");
				continue;
			}

			$tmp = wpstg_check_removing_files("$path/$entry", $folders[$entry]);
			$dirsize += $tmp['size'];
		}
		$folders['size'] = $dirsize;
	}
	return $folders;
}

/**
 * Removes current clone
 * 
 * @global object $wpdb
 * @global array $wpstg_clone_details
 * @param bool $isAjax
 */
function wpstg_remove_clone($isAjax = true) {
	global $wpdb, $wpstg_clone_details;
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$wpstg_clone_details = wpstg_get_options();

	$clone = $_POST['cloneID'];

	if (empty ($clone) || $clone === '' ) {
		wpstg_log('cloneID does not exist or is empty');
		//wp_die(-1);
                wp_die('cloneID does not exist or is empty');
	}

	//drop clone tables
	$db_helper = apply_filters('wpstg_db_helper', $wpdb, $clone);
	$tables = $db_helper->get_col( $wpdb->prepare('show tables like %s', $clone . '_%'));
	$unchecked_tables = isset($_POST['uncheckedTables']) ? $_POST['uncheckedTables'] : array();
	$tables = array_diff($tables, $unchecked_tables);
	foreach ($tables as $table) {
		if (! wpstg_is_root_table($table, $wpdb->prefix) && !empty($table) ) {
			$result = $db_helper->query("drop table `$table`");
                    if ( !isset($result) || false === $result | 0 === $result ) {
                            wpstg_log('Droping table ' . $table . ' has been failed.');
                            //wp_die(-1);
                            wp_die('Droping table ' . $table . ' has been failed.');
                    }
                wpstg_log('Droping table ' . $table . ' was successfull');
                }
                        
	}

	//remove clone folder
	$excluded_folders = isset($_POST['excludedFolders']) ? $_POST['excludedFolders'] : array();
	$result = wpstgDeleteDirectory(get_home_path() . $clone, $excluded_folders);
	if (! $result) {
		wpstg_log('Removing clone folder '.get_home_path() . $clone.' has been failed.');
		wp_die(-1);
	}
	wpstg_log('Clone folder '.get_home_path() . $clone.' has been removed successfully.');
	$existing_clones = get_option('wpstg_existing_clones', array());
	$key = array_search($clone, $existing_clones);
		
	if ($key !== false && $key !== NULL) {
		unset($existing_clones[$key]);
		update_option('wpstg_existing_clones', $existing_clones);
	}

	do_action('wpstg_remove_clone', $clone);
	if ($isAjax) {
		//wpstg_log('Clone( ' . $clone . ' ) has been removed successfully.');
		wpstg_log('Clone( ' . $clone . ' ) has been removed successfully.');
		wp_die(0);
	}
}
add_action('wp_ajax_wpstg_remove_clone', 'wpstg_remove_clone');



/**
 * Delete a specific directory
 * 
 * @param array $dir
 * @param array $excluded_dirs
 * @return boolean
 */
function wpstgDeleteDirectory($dir, $excluded_dirs) {
	if (!file_exists($dir))
		return true;

	if (!is_dir($dir))
		return unlink($dir);

	foreach (scandir($dir) as $item) {
		if ($item == '.' || $item == '..' || in_array("$dir/$item", $excluded_dirs))
			continue;

		if (!wpstgDeleteDirectory("$dir/$item", $excluded_dirs))
			return false;
	}

	$need_rm = true;
	foreach ($excluded_dirs as $ex_dir)
		if (strpos($ex_dir, $dir) !== false) {
			$need_rm = false;
			break;
		}

	return $need_rm ? rmdir($dir) : true;
}

/** Cancel cloning process
 * Remove current clone and purge json files containing task related data
 * 
 * @return void
 */

function wpstg_cancel_cloning() {
	wpstg_remove_clone(false);
	wpstg_clear_options();
	wp_die(0);
}
add_action('wp_ajax_wpstg_cancel_cloning', 'wpstg_cancel_cloning');


/**
 * Check if table name starts with prefix which belongs to the wordpress root table
 * 
 * @param string $haystack
 * @param string $needle
 * @return bool true if table name starts with prefix of the root table
 */
function wpstg_is_root_table($haystack, $needle) {
	return strpos($haystack, $needle) === 0;
}

/** Get global clone details options
 * 
 * @return string JSON that includes the cloning relevant data
 */
function wpstg_get_options() {
	$path = wpstg_get_upload_dir() . '/clone_details.json';
	$content = @file_get_contents($path);
        if ($content) {
            return json_decode($content, true);
        }else{
            return json_decode('', true);
        }
}

/** 
 * Save global clone details options
 * and create clone_details.json
 * 
 * @return void
 */
function wpstg_save_options() {
	global $wpstg_clone_details;
        $path = wpstg_get_upload_dir();
        
	if (wp_is_writable($path)) {
                $file = 'clone_details.json';
		file_put_contents($path . '/' . $file, json_encode($wpstg_clone_details));
        }else {
            wpstg_log($path . '/' . $file . ' is not writeable! ');
        }
}

/**
 * Write unexpected errors into the log file
 */
    function wpstg_error_processing() {
            $msg = sanitize_text_field($_POST['wpstg_error_msg']);
            if (! empty($msg))
                    wpstg_log($msg);
            wp_die();
    }
    add_action('wp_ajax_wpstg_error_processing', 'wpstg_error_processing');


/**
 * Install must-use plugin that disables other plugins when wp staging ajax requests are made.
 * 
 * @return void;
 */
function wpstg_ajax_muplugin_install() {
    global $state_data;

    $key_rules = array(
        'action' => 'key',
        'install' => 'numeric',
    );
    wpstg_set_post_data($key_rules);

    $mu_dir = ( defined('WPMU_PLUGIN_DIR') && defined('WPMU_PLUGIN_URL') ) ? WPMU_PLUGIN_DIR : trailingslashit(WP_CONTENT_DIR) . 'mu-plugins';
    $source = trailingslashit(WPSTG_PLUGIN_DIR) . 'optimizer/wp-staging-optimizer.php';
    $dest = trailingslashit($mu_dir) . 'wp-staging-optimizer.php';

    if ('1' === trim($state_data['install'])) { // install MU plugin
        if (!wp_mkdir_p($mu_dir)) {
            printf(esc_html__('The following directory could not be created: %s', 'wpstg'), $mu_dir);
            exit;
        }

        if (!copy($source, $dest)) {
            printf(esc_html__('Could not copy the compatibility plugin from %1$s to %2$s', 'wpstg'), $source, $dest);
            exit;
        }
    } else { // uninstall MU plugin
        // TODO: Use WP_Filesystem API.
        if (file_exists($dest) && !unlink($dest)) {
            printf(esc_html__('Could not remove the compatibility plugin from %s', 'wpstg'), $dest);
            exit;
        }
    }
    exit;
}

add_action('wp_ajax_wpstg_muplugin_install', 'wpstg_ajax_muplugin_install');

/**
 * Handler for saving the options and for updating 
 * the plugins that are not loaded during a request (Optimizing Mode).
 */
function wpstg_ajax_disable_plugins() {
    global $state_data, $wpstg_options;

    $key_rules = array(
        'action' => 'key',
        'blacklist_plugins' => 'array',
        'batch_size' => 'int',
        'query_limit' => 'int',
        'disable_admin_login' => 'int',
        'uninstall_on_delete' => 'int'
        
    );

    wpstg_set_post_data($key_rules);

    $wpstg_options['blacklist_plugins'] = (array) $state_data['blacklist_plugins'];
    $wpstg_options['wpstg_batch_size'] = (int) $state_data['batch_size'];
    $wpstg_options['wpstg_query_limit'] = (int) $state_data['query_limit'];
    $wpstg_options['disable_admin_login'] = (int) $state_data['disable_admin_login'];
    $wpstg_options['uninstall_on_delete'] = (int) $state_data['uninstall_on_delete'];
    
    update_option('wpstg_settings', $wpstg_options);
    exit;
}
add_action('wp_ajax_wpstg_disable_plugins', 'wpstg_ajax_disable_plugins');

/**
 * Sets $state_data from $_POST, potentially un-slashed and sanitized.
 *
 * @param array $key_rules An optional associative array of expected keys and their sanitization rule(s).
 * @param string $context The method that is specifying the sanitization rules. Defaults to calling method.
 */
function wpstg_set_post_data($key_rules = array(), $context = '') {
    global $state_data;

    if (is_null($state_data)) {
        $state_data = wp_unslash($_POST);
    } else {
        return;
    }

    // Sanitize the new state data.
    if (!empty($key_rules)) {
        $context = empty($context) ? wpstg_get_caller_function() : trim($context);
        $state_data = WPSTG_Sanitize::sanitize_data($state_data, $key_rules, $context);

        if (false === $state_data) {
            exit;
        }
    }
}

/**
 * Returns the function name that called the function using this function.
 *
 * @return string function name
 */
function wpstg_get_caller_function() {
    list(,, $caller ) = debug_backtrace(false);

    if (!empty($caller['function'])) {
        $caller = $caller['function'];
    } else {
        $caller = '';
    }

    return $caller;
}

/**
 * 
 * 
 * 
 * @param string $function php function name
 * @param string $status | success | fail
 * @param string $message error message or successfull notice
 * @param integer step
 * 
 * @result json
 */
function wpstg_return_json($name, $status, $message, $percent, $running_time){
    $result = array(
                    'name' => $name, 
                    'status' => $status,
                    'message' => $message,
                    'percent' => $percent,
                    'running_time' => $running_time
                    );
    wp_send_json( $result );
}

/**
 * Get runtime
 * 
 * @global type $start_time
 * @return integer
 */
function wpstg_get_runtime(){
    global $start_time;
        $now_time = microtime(true);
        $now = $now_time - $start_time;
    return $now;
}

/**
 * Get header of working log
 * 
 * @param integer $progress percent of progress
 * @param string $message define optional logging message
 * @return string
 */
function wpstg_get_log_data($progress){
            if ($progress === 0) {
     return $log_data_header =  '###########################################<br>'
                              . '&nbsp;&nbsp;&nbsp; WP Staging working log              <br>'
                              . '&nbsp;&nbsp;&nbsp; You find all log files in:          <br>'
                              . '&nbsp;&nbsp;&nbsp; wp-content/plugins/wp-staging/logs  <br>'
                              . '###########################################<br>';
            } else {
                return '';
            }
}

/*
 * This checks if a job has been started previously
 * 
 * @return bool 
 */
function wpstg_check_job_exists(){
    global $wpstg_clone_details;
    if ( isset($wpstg_clone_details['db_progress']) ) {
        return true;
    }
}

/**
 * Return the button title depending if the 
 * 
 * @return string
 */
function wpstg_return_button_title(){
    If ( !wpstg_check_job_exists() ) {
        return __('Start Cloning', 'wpstg');
    } else {
        return __('Resume Cloning', 'wpstg');
    }
}
/**
 * Check if there is enough disk space left for cloning site
 * 
 * @return string
 */
/*function wpstg_check_diskspace_($clone_size){
        $free_space = function_exists('disk_free_space') ? disk_free_space(get_home_path()) : 'undefined';
		$overflow = $free_space < $clone_size ? true : false;

        if (function_exists('disk_free_space') ) {
              return $overflow ? __('Probably not enough free disk space to create a staging site. You can continue but its likely that the copying process will fail.', 'wpstg') : '';
        }
        //return __('Can not check if there is enough disk space left for the staging website. This is not really a bad thing so you can still continue.', 'wpstg');        
}*/

function wpstg_check_diskspace($clone_size) {
                if ( !function_exists('disk_free_space') )
                    return '';
                
		$overflow = @disk_free_space(get_home_path());
		if ( $overflow == false)                
                    return '';
                
                $overflow = $overflow < $clone_size ? true : false; 
                return $overflow ? '<br>' . __('Probably not enough free disk space to create a staging site. <br> You can continue but its likely that the copying process will fail.', 'wpstg') : '';
}

/**
 * Get URL of staging site
 * 
 * @global type $wpstg_clone_details
 * @return mixed bool | string false when there is not staging url defined
 */
function wpstg_get_staging_url(){
    global $wpstg_clone_details;
    
    if ( !empty($wpstg_clone_details['current_clone']))
        return get_home_url() . '/' . $wpstg_clone_details['current_clone'];
    
    return false;
}