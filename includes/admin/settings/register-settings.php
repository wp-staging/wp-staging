<?php
/**
 * Register Settings
 *
 * @package     WPSTG
 * @subpackage  Admin/Settings
 * @copyright   Copyright (c) 2014, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;



/**
 * Get an option
 *
 * Looks to see if the specified setting exists, returns default if not
 *
 * @since 0.9.0
 * @return mixed
 */
function wpstg_get_option( $key = '', $default = false ) {
	global $wpstg_options;
	$value = ! empty( $wpstg_options[ $key ] ) ? $wpstg_options[ $key ] : $default;
	$value = apply_filters( 'wpstg_get_option', $value, $key, $default );
	return apply_filters( 'wpstg_get_option_' . $key, $value, $key, $default );
}

/**
 * Get Settings
 *
 * Retrieves all plugin settings
 *
 * @since 1.0
 * @return array WPSTG settings
 */
function wpstg_get_settings() {
	$settings = get_option( 'wpstg_settings' );
               
        
	if( empty( $settings ) ) {
		// Update old settings with new single option
		$general_settings = is_array( get_option( 'wpstg_settings_general' ) )    ? get_option( 'wpstg_settings_general' )  	: array();
                
		//$settings = array_merge( $general_settings, $ext_settings, $license_settings);//, $networks, $ext_settings, $license_settings, $addons_settings);
                $settings = $general_settings;

		update_option( 'wpstg_settings', $settings);
	}
	return apply_filters( 'wpstg_get_settings', $settings );
}

/**
 * Add all settings sections and fields
 *
 * @since 1.0
 * @return void
*/
function wpstg_register_settings() {

	if ( false == get_option( 'wpstg_settings' ) ) {
		add_option( 'wpstg_settings' );
	}

	foreach( wpstg_get_registered_settings() as $tab => $settings ) {

		add_settings_section(
			'wpstg_settings_' . $tab,
			__return_null(),
			'__return_false',
			'wpstg_settings_' . $tab
		);

		foreach ( $settings as $option ) {

			$name = isset( $option['name'] ) ? $option['name'] : '';

			add_settings_field(
				'wpstg_settings[' . $option['id'] . ']',
				$name,
				function_exists( 'wpstg_' . $option['type'] . '_callback' ) ? 'wpstg_' . $option['type'] . '_callback' : 'wpstg_missing_callback',
				'wpstg_settings_' . $tab,
				'wpstg_settings_' . $tab,
				array(
					'id'      => isset( $option['id'] ) ? $option['id'] : null,
					'desc'    => ! empty( $option['desc'] ) ? $option['desc'] : '',
					'name'    => isset( $option['name'] ) ? $option['name'] : null,
					'section' => $tab,
					'size'    => isset( $option['size'] ) ? $option['size'] : null,
					'options' => isset( $option['options'] ) ? $option['options'] : '',
					'std'     => isset( $option['std'] ) ? $option['std'] : '',
                                        'textarea_rows' => isset( $option['textarea_rows']) ? $option['textarea_rows'] : ''
				)
			);
		}

	}

	// Creates our settings in the options table
	register_setting( 'wpstg_settings', 'wpstg_settings', 'wpstg_settings_sanitize' );

}
add_action('admin_init', 'wpstg_register_settings');

/**
 * Retrieve the array of plugin settings
 *
 * @since 1.8
 * @return array
*/
function wpstg_get_registered_settings() {

	/**
	 * 'Whitelisted' WPSTG settings, filters are provided for each settings
	 * section to allow extensions and other plugins to add their own settings
	 */
	$wpstg_settings = array(
		/** General Settings */
		'general' => apply_filters( 'wpstg_settings_general',
				array(
						array(
								'id' => 'wpstg_header',
								'name' => '<strong>' . __( 'General', 'wpstg' ) . '</strong>',
								'desc' => '',
								'type' => 'header',
								'size' => 'regular'
						),
							array(
								'id' => 'wpstg_query_limit',
								'name' => __('DB Copy Query Limit', 'wpstg'),
								'desc' => __('Number of DB rows, that will be copied within one ajax request. The higher the value the faster the database copy process. To find out the highest possible values try a high value like 1.000 or more and decrease it until you get no more errors during copy process. <strong> Default: 100 </strong>'),
								'type' => 'number',
								'size' => 'medium',
								'std' => 1000,
							),
							array(
								'id' => 'wpstg_batch_size',
								'name' => __('File Copy Batch Size', 'wpstg'),
								'desc' => __('Buffer size for the file copy process in megabyte. The higher the value the faster large files will be copied. To find out the highest possible values try a high one and lower it until you get no errors during file copy process. Usually this value correlates directly with the memory consumption of php so make sure that it does not exceed any php.ini max_memory limits. <strong>Default:</strong> 2 ', 'wpstg'),
								'type' => 'number',
								'size' => 'medium',
								'std' => '2',
							),
							array(
								'id' => 'wpstg_cpu_load',
								'name' => __('CPU load priority', 'wpstg'),
								'desc' => __('Using high will result in fast as possible processing but the cpu load increases and it\'s also possible that staging process gets interupted because of too many ajax requests (e.g. <strong>authorization error</strong>). Using a lower value results in lower cpu load on your server but also slower staging site creation. <strong>Default: </strong> Medium ', 'wpstg'),
								'type' => 'select',
								'size' => 'medium',
								'options' => array(
                                                                    'default' => 'Default',
                                                                    'high' => 'High (fast)',
                                                                    'medium' => 'Medium (average)',
                                                                    'low' => 'Low (slow)'

                                                              )
							),
                                                        array(
								'id' => 'wpstg_disabled_plugins',
								'name' => __('Optimizer', 'wpstg'),
								'desc' => __('Select the plugins that should be disabled during build process of the staging site. Some plugins slow down the copy process and add overhead to each request, requiring extra CPU and memory consumption. Some of them can interfere with cloning process and cause them to fail, so we recommend to select all plugins here.<p></p><strong>Note:</strong> This does not disable plugins on your staging site. You have to disable them there separately.', 'wpstg'),
								'type' => 'install_muplugin',
								'size' => 'medium',
								'std' => '20',
							),
                                                        'disable_admin_login' => array(
                                                            'id' => 'disable_admin_login',
                                                            'name' => __( 'Disable admin authorization', 'mashsb' ),
                                                            'desc' => __( 'Use this option only if you are using a custom login page and not the default login.php. If you enable this option you are allowing everyone including searchengines to see your staging site, so you have to create a custom authentication like using .htaccess', 'mashsb' ),
                                                            'type' => 'checkbox'
                                                        ),
                                                        'wordpress_subdirectory' => array(
                                                            'id' => 'wordpress_subdirectory',
                                                            'name' => __( 'Wordpress in subdirectory', 'mashsb' ),
                                                            'desc' => __( 'Use this option when you gave wordpress its own subdirectory. if you enable this, WP Staging will reset the index.php of the clone site to the originally one. <br> <a href="https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory" target="_blank">Read more in the WordPress Codex</a>', 'mashsb' ),
                                                            'type' => 'checkbox'
                                                        ),
                                                        /*'link_images' => array(
                                                            'id' => 'link_images',
                                                            'name' => __( 'Link images. Change upload ', 'mashsb' ),
                                                            'desc' => __( 'Enable this if you want WP Staging to link images. if you enable this, WP Staging will reset the index.php of the clone site to the originally one. <br> <a href="https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory" target="_blank">Read more in the WordPress Codex</a>', 'mashsb' ),
                                                            'type' => 'checkbox'
                                                        ),*/
                                                        /*'admin_login_page' => array(
                                                            'id' => 'admin_login_page',
                                                            'name' => __( 'Login page', 'mashsb' ),
                                                            'desc' => __( ' This is necessary if you are using a custom login page and not the default login.php. Fill in the page id of your custom login page, otherwise you will not be able to login to your staging website.', 'mashsb' ),
                                                            'type' => 'text',
                                                            'size' => 'medium'
                                                        ),*/
                                                        'debug_mode' => array(
                                                            'id' => 'debug_mode',
                                                            'name' => __( 'Debug Mode', 'mashsb' ),
                                                            'desc' => __( 'This will enable an extended debug mode which creates additional entries in <strong>wp-content/wp-staging/logs</strong>. Please enable this when we ask you to do so.', 'mashsb' ),
                                                            'type' => 'checkbox'
                                                        ),
                                                        'uninstall_on_delete' => array(
                                                            'id' => 'uninstall_on_delete',
                                                            'name' => __( 'Remove Data on Uninstall?', 'mashsb' ),
                                                            'desc' => __( 'Check this box if you like WP Staging to completely remove all of its data when the plugin is deleted. This will not remove staging sites files or database tables.', 'mashsb' ),
                                                            'type' => 'checkbox'
                                                        ),
                                   
			)
		),
		'licenses' => apply_filters('wpstg_settings_licenses',
			array('licenses_header' => array(
					'id' => 'licenses_header',
					'name' => __( 'Activate your Add-Ons', 'wpstg' ),
					'desc' => '',
					'type' => 'header'
				),)
		),
                'extensions' => apply_filters('wpstg_settings_extension',
			array()
		),
                'addons' => apply_filters('wpstg_settings_addons',
			array(
                                'addons' => array(
					'id' => 'addons',
					'name' => __( '', 'wpstg' ),
					'desc' => __( '', 'wpstg' ),
					'type' => 'addons'
				)
                        )
		)
	);

	return $wpstg_settings;
}

/**
 * Settings Sanitization
 *
 * Adds a settings error (for the updated message)
 * At some point this will validate input
 *
 * @since 0.9.0
 *
 * @param array $input The value input in the field
 *
 * @return string $input Sanitized value
 */
function wpstg_settings_sanitize( $input = array() ) {

	global $wpstg_options;

	if ( empty( $_POST['_wp_http_referer'] ) ) {
		return $input;
	}

	parse_str( $_POST['_wp_http_referer'], $referrer );

	$settings = wpstg_get_registered_settings();
	$tab      = isset( $referrer['tab'] ) ? $referrer['tab'] : 'general';

	$input = $input ? $input : array();
	$input = apply_filters( 'wpstg_settings_' . $tab . '_sanitize', $input );

	// Loop through each setting being saved and pass it through a sanitization filter
	foreach ( $input as $key => $value ) {

		// Get the setting type (checkbox, select, etc)
		$type = isset( $settings[$tab][$key]['type'] ) ? $settings[$tab][$key]['type'] : false;

		if ( $type ) {
			// Field type specific filter
			$input[$key] = apply_filters( 'wpstg_settings_sanitize_' . $type, $value, $key );
		}

		// General filter
		$input[$key] = apply_filters( 'wpstg_settings_sanitize', $value, $key );
	}

	// Loop through the whitelist and unset any that are empty for the tab being saved
	if ( ! empty( $settings[$tab] ) ) {
		foreach ( $settings[$tab] as $key => $value ) {

			// settings used to have numeric keys, now they have keys that match the option ID. This ensures both methods work
			if ( is_numeric( $key ) ) {
				$key = $value['id'];
			}

			if ( empty( $input[$key] ) ) {
				unset( $wpstg_options[$key] );
			}

		}
	}

	// Merge our new settings with the existing
	$output = array_merge( $wpstg_options, $input );

	add_settings_error( 'wpstg-notices', '', __( 'Settings updated.', 'wpstg' ), 'updated' );

	return $output;
}


/**
 * Sanitize text fields
 *
 * @since 1.8
 * @param array $input The field value
 * @return string $input Sanitizied value
 */
function wpstg_sanitize_text_field( $input ) {
	return trim( $input );
}
add_filter( 'wpstg_settings_sanitize_text', 'wpstg_sanitize_text_field' );

/**
 * Retrieve settings tabs
 *
 * @since 1.8
 * @param array $input The field value
 * @return string $input Sanitizied value
 */
function wpstg_get_settings_tabs() {

	$settings = wpstg_get_registered_settings();

	$tabs             = array();
	$tabs['general']  = __( 'General', 'wpstg' );

        if( ! empty( $settings['misc'] ) ) {
		$tabs['misc'] = __( 'Misc', 'wpstg' );
	} 
        
        if( ! empty( $settings['networks'] ) ) {
		//$tabs['networks'] = __( 'Social Networks', 'wpstg' );
	}  
        
	if( ! empty( $settings['extensions'] ) ) {
		$tabs['extensions'] = __( 'Extensions', 'wpstg' );
	}
	
	if( ! empty( $settings['licenses'] ) ) {
		//$tabs['licenses'] = __( 'Licenses', 'wpstg' );
	}
        //$tabs['addons'] = __( 'Add-Ons', 'wpstg' );

	//$tabs['misc']      = __( 'Misc', 'wpstg' );

	return apply_filters( 'wpstg_settings_tabs', $tabs );
}
   


/**
 * Header Callback
 *
 * Renders the header.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @return void
 */
function wpstg_header_callback( $args ) {
	//echo '<hr/>';
        echo '&nbsp';
}

/**
 * Checkbox Callback
 *
 * Renders checkboxes.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_checkbox_callback( $args ) {
	global $wpstg_options;

	$checked = isset( $wpstg_options[ $args[ 'id' ] ] ) ? checked( 1, $wpstg_options[ $args[ 'id' ] ], false ) : '';
	$html = '<input type="checkbox" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="1" ' . $checked . '/>';
	$html .= '<label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}


/**
 * Multicheck Callback
 *
 * Renders multiple checkboxes.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_multicheck_callback( $args ) {
	global $wpstg_options;

	if ( ! empty( $args['options'] ) ) {
		foreach( $args['options'] as $key => $option ):
			if( isset( $wpstg_options[$args['id']][$key] ) ) { $enabled = $option; } else { $enabled = NULL; }
			echo '<input name="wpstg_settings[' . $args['id'] . '][' . $key . ']" id="wpstg_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="' . $option . '" ' . checked($option, $enabled, false) . '/>&nbsp;';
			echo '<label for="wpstg_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
		endforeach;
		echo '<p class="description wpstg_hidden">' . $args['desc'] . '</p>';
	}
}

/**
 * Radio Callback
 *
 * Renders radio boxes.
 *
 * @since 1.3.3
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_radio_callback( $args ) {
	global $wpstg_options;

	foreach ( $args['options'] as $key => $option ) :
		$checked = false;

		if ( isset( $wpstg_options[ $args['id'] ] ) && $wpstg_options[ $args['id'] ] == $key )
			$checked = true;
		elseif( isset( $args['std'] ) && $args['std'] == $key && ! isset( $wpstg_options[ $args['id'] ] ) )
			$checked = true;

		echo '<input name="wpstg_settings[' . $args['id'] . ']"" id="wpstg_settings[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked(true, $checked, false) . '/>&nbsp;';
		echo '<label for="wpstg_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
	endforeach;

	echo '<p class="description wpstg_hidden">' . $args['desc'] . '</p>';
}

/**
 * Text Callback
 *
 * Renders text fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_text_callback( $args ) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . $size . '-text" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<label class="wpstg_hidden" class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Number Callback
 *
 * Renders number fields.
 *
 * @since 1.9
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_number_callback( $args ) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$max  = isset( $args['max'] ) ? $args['max'] : 999999;
	$min  = isset( $args['min'] ) ? $args['min'] : 0;
	$step = isset( $args['step'] ) ? $args['step'] : 1;

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Textarea Callback
 *
 * Renders textarea fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_textarea_callback( $args ) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : '40';
	$html = '<textarea class="large-text wpstg-textarea" cols="50" rows="' . $size . '" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	$html .= '<label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Password Callback
 *
 * Renders password fields.
 *
 * @since 1.3
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_password_callback( $args ) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="password" class="' . $size . '-text" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';
	$html .= '<label for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Missing Callback
 *
 * If a function is missing for settings callbacks alert the user.
 *
 * @since 1.3.1
 * @param array $args Arguments passed by the setting
 * @return void
 */
function wpstg_missing_callback($args) {
	printf( __( 'The callback function used for the <strong>%s</strong> setting is missing.', 'wpstg' ), $args['id'] );
}

/**
 * Select Callback
 *
 * Renders select fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_select_callback($args) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$html = '<select id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']"/>';

	foreach ( $args['options'] as $option => $name ) :
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Color select Callback
 *
 * Renders color select fields.
 *
 * @since 2.1.2
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */


function wpstg_color_select_callback( $args ) {
	global $wpstg_options;
        
        if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$html = '<strong>#:</strong><input type="text" style="max-width:80px;border:1px solid #' . esc_attr( stripslashes( $value ) ) . ';border-right:20px solid #' . esc_attr( stripslashes( $value ) ) . ';" id="wpstg_settings[' . $args['id'] . ']" class="medium-text ' . $args['id'] . '" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';

	$html .= '</select>';
	$html .= '<label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Rich Editor Callback
 *
 * Renders rich editor fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @global $wp_version WordPress Version
 */
function wpstg_rich_editor_callback( $args ) {
	global $wpstg_options, $wp_version;
	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
		ob_start();
		wp_editor( stripslashes( $value ), 'wpstg_settings_' . $args['id'], array( 'textarea_name' => 'wpstg_settings[' . $args['id'] . ']', 'textarea_rows' => $args['textarea_rows'] ) );
		$html = ob_get_clean();
	} else {
		$html = '<textarea class="large-text wpstg-richeditor" rows="10" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	}

	$html .= '<br/><label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Upload Callback
 *
 * Renders upload fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_upload_callback( $args ) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[$args['id']];
	else
		$value = isset($args['std']) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . $size . '-text wpstg_upload_field" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<span>&nbsp;<input type="button" class="wpstg_settings_upload_button button-secondary" value="' . __( 'Upload File', 'wpstg' ) . '"/></span>';
	$html .= '<label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}


/**
 * Registers the license field callback for Software Licensing
 *
 * @since 1.5
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
if ( ! function_exists( 'wpstg_license_key_callback' ) ) {
	function wpstg_license_key_callback( $args ) {
		global $wpstg_options;

		if ( isset( $wpstg_options[ $args['id'] ] ) )
			$value = $wpstg_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . $size . '-text" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';

		if ( 'valid' == get_option( $args['options']['is_valid_license_option'] ) ) {
			$html .= '<input type="submit" class="button-secondary" name="' . $args['id'] . '_deactivate" value="' . __( 'Deactivate License',  'wpstg' ) . '"/>';
                        $html .= '<span style="font-weight:bold;color:green;"> License key activated! </span> <p style="color:green;font-size:13px;"> You´ll get updates for this Add-On automatically!</p>';
                } else {
                    $html .= '<span style="color:red;"> License key not activated!</span style=""><p style="font-size:13px;font-weight:bold;">You´ll get no important security and feature updates for this Add-On!</p>';
                }
		$html .= '<label for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

                wp_nonce_field( $args['id'] . '-nonce', $args['id'] . '-nonce' );
                
		echo $html;
	}
}



/**
 * Registers the Add-Ons field callback for WP-Staging Add-Ons
 *
 * @since 2.0.5
 * @param array $args Arguments passed by the setting
 * @return html
 */
function wpstg_addons_callback( $args ) {
	$html = wpstg_add_ons_page();
	echo $html;
}

/**
 * Registers the image upload field
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */

	function wpstg_upload_image_callback( $args ) {
		global $wpstg_options;

		if ( isset( $wpstg_options[ $args['id'] ] ) )
			$value = $wpstg_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . $size . '-text ' . $args['id'] . '" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';
	
		$html .= '<input type="submit" class="button-secondary wpstg_upload_image" name="' . $args['id'] . '_upload" value="' . __( 'Select Image',  'wpstg' ) . '"/>';
		
		$html .= '<label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}




        
/**
 * Hook Callback
 *
 * Adds a do_action() hook in place of the field
 *
 * @since 1.0.8.2
 * @param array $args Arguments passed by the setting
 * @return void
 */
function wpstg_hook_callback( $args ) {
	do_action( 'wpstg_' . $args['id'] );
}

/**
 * Set manage_options as the cap required to save WPSTG settings pages
 *
 * @since 1.9
 * @return string capability required
 */
function wpstg_set_settings_cap() {
	return 'manage_options';
}
add_filter( 'option_page_capability_wpstg_settings', 'wpstg_set_settings_cap' );




/* Permission check if logfile is writable
 *
 * @since 2.0.6
 * @return string
 */

function wpstg_log_permissions(){
    global $wpstg_options;
    if (!WPSTG()->logger->checkDir() ){
        return '<br><strong style="color:red;">' . __('Log file directory not writable! Set FTP permission to 755 or 777 for /wp-content/plugins/wp-staging/logs/', 'wpstg') . '</strong> <br> Read here more about <a href="http://codex.wordpress.org/Changing_File_Permissions" target="_blank">file permissions</a> ';
    }
}

/**
 * Render template for installing a must-use plugin (MU Plugin) 
 */
function wpstg_install_muplugin_callback(){
    $plugin_compatibility_checked = isset( $GLOBALS['wpstg_optimizer'] ) ? ' checked="checked"' : '';
    ob_start();
    ?>
<div class="option-section plugin-compatibility-section">
	<label for="plugin-compatibility" class="plugin-compatibility bubble" style="float:left;">
		<input id="plugin-compatibility" type="checkbox" name="plugin_compatibility"<?php echo $plugin_compatibility_checked; ?> autocomplete="off"<?php echo $plugin_compatibility_checked; ?> />
		<?php __( 'Improve performance and reliability by not loading the following plugins for migration requests', 'wpstg' ); ?>
	</label>
	<a href="#" class="general-helper plugin-compatibility-helper js-action-link"></a>

	<div class="plugin-compatibility-message helper-message bottom">
		<?php echo __( 'Select the plugins you wish to disable during clone process', 'wpstg' ); ?></br>
	</div>

	<div class="indent-wrap expandable-content plugin-compatibility-wrap select-wrap" style="display:none;">
		<select autocomplete="off" class="multiselect" id="selected-plugins" name="selected_plugins[]" multiple="multiple" style="min-height:400px;">
			<?php
                        global $wpstg_options;
			$blacklist = isset($wpstg_options['blacklist_plugins']) ? array_flip( (array) $wpstg_options['blacklist_plugins'] ) : array();
			foreach ( get_plugins() as $key => $plugin ) {
				if ( 0 === strpos( $key, 'wp-staging' ) ) {
					continue;
				}
				$selected = ( isset( $blacklist[ $key ] ) ) ? ' selected' : '';
				printf( '<option value="%s"%s>%s</option>', $key, $selected, $plugin['Name'] );
			}
			?>
		</select>
		<br>
		<a class="multiselect-select-all js-action-link" href="#"><?php _e( 'Select All', 'wpstg' ); ?></a>
		<span class="select-deselect-divider">/</span>
		<a class="multiselect-deselect-all js-action-link" href="#"><?php _e( 'Deselect All', 'wpstg' ); ?></a>
		<span class="select-deselect-divider">/</span>
		<a class="multiselect-invert-selection js-action-link" href="#"><?php _e( 'Invert Selection', 'wpstg' ); ?></a>
                <p>
			<span class="button plugin-compatibility-save"><?php _e( 'Save Changes', 'wpstg' ); ?></span>
			<span class="plugin-compatibility-success-msg"><?php _ex( 'Saved', 'The settings were saved successfully', 'wpstg' ); ?></span>
		</p>
	</div>
</div>
<?php
$html = ob_get_contents();
ob_end_clean();
echo $html;
}
