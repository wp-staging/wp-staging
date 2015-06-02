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
 * @since 1.0.0
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
                $misc_settings = is_array( get_option( 'wpstg_settings_misc' ) )   ? get_option( 'wpstg_settings_misc' )   : array();
                //$networks = is_array( get_option( 'wpstg_settings_networks' ) )   ? get_option( 'wpstg_settings_networks' )   : array();
		//$ext_settings     = is_array( get_option( 'wpstg_settings_extensions' ) ) ? get_option( 'wpstg_settings_extensions' )	: array();
		//$license_settings = is_array( get_option( 'wpstg_settings_licenses' ) )   ? get_option( 'wpstg_settings_licenses' )   : array();
                //$addons_settings = is_array( get_option( 'wpstg_settings_addons' ) )   ? get_option( 'wpstg_settings_addons' )   : array();
                
		$settings = array_merge( $general_settings, $misc_settings, $networks, $ext_settings, $license_settings, $addons_settings);

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
                                    'id' => 'wpstg_textfield',
                                    'name' => __( 'Text Field', 'wpstg' ),
                                    'desc' => __( 'This is a text field', 'wpstg' ),
                                    'type' => 'text',
                                    'size' => 'large',
                                    'std' => 'This is a large textfield'
                            ),
                            array(
                                    'id' => 'wpstg_number',
                                    'name' => __( 'Number field', 'wpstg' ),
                                    'desc' => __( 'This is a number field', 'wpstg' ),
                                    'type' => 'number',
                                    'size' => 'normal'
                            ),  
                            array(
                                    'id' => 'wpstg_custom_field',
                                    'name' => __( 'Custom field', 'wpstg' ),
                                    'desc' => __( 'This is a custom field created with the callback function mashsb_custom_field_callback', 'wpstg' ),
                                    'type' => 'custom_field',
                                    'size' => 'small',
                                    'std' => 0.8
                            ), 
                            array(
                                    'id' => 'wpstg_options',
                                    'name' => __( 'Options field', 'wpstg' ),
                                    'desc' => __( 'This is an options field', 'wpstg' ),
                                    'type' => 'select',
                                                    'options' => array(
                                                            'value1' => __( 'Value 1', 'wpstg' ),
                                                            'value2' => __( 'Value 2', 'wpstg' )
                                                    )
                            ),
                            array(
                                    'id' => 'wpstg_checkbox',
                                    'name' => __( 'Checkbox', 'wpstg' ),
                                    'desc' => __( 'You already guessed it: This is a Checkbox', 'wpstg' ),
                                    'type' => 'checkbox'
                            ),
                                array(
					'id' => 'debug_header',
					'name' => '<strong>' . __( 'Debug', 'mashsb' ) . '</strong>',
					'desc' => __( ' ', 'mashsb' ),
					'type' => 'header'
				),
                                'debug_mode' => array(
					'id' => 'debug_mode',
					'name' => __( 'Debug mode', 'mashsb' ),
					'desc' => __( '<strong>Note: </strong> Check this box before you get in contact with our support team. This allows us to check publically hidden debug messages on your website. Do not forget to disable it thereafter! Enable this also to write daily sorted log files of requested share counts to folder <strong>/wp-content/plugins/mashsharer/logs</strong>. Please send us this files when you notice a wrong share count.' . wpstg_log_permissions(), 'wpstg' ),
					'type' => 'checkbox'
				)
			)
		),
                'misc' => apply_filters('wpstg_settings_misc',
			array(
                            array(
                                    'id' => 'wpstg_header',
                                    'name' => '<strong>' . __( 'WP-Staging', 'wpstg' ) . '</strong>',
                                    'desc' => '',
                                    'type' => 'header',
                                    'size' => 'regular'
                            ),
                            array(
                                    'id' => 'wpstg_textfield',
                                    'name' => __( 'Text Field', 'wpstg' ),
                                    'desc' => __( 'This is a text field', 'wpstg' ),
                                    'type' => 'text',
                                    'size' => 'large',
                                    'std' => 'This is a large textfield'
                            ),
                            array(
                                    'id' => 'wpstg_number',
                                    'name' => __( 'Number field', 'wpstg' ),
                                    'desc' => __( 'This is a number field', 'wpstg' ),
                                    'type' => 'number',
                                    'size' => 'normal'
                            ),  
                            array(
                                    'id' => 'wpstg_custom_field',
                                    'name' => __( 'Custom field', 'wpstg' ),
                                    'desc' => __( 'This is a custom field created with the callback function mashsb_custom_field_callback', 'wpstg' ),
                                    'type' => 'custom_field',
                                    'size' => 'small',
                                    'std' => 0.8
                            ), 
                            array(
                                    'id' => 'wpstg_options',
                                    'name' => __( 'Options field', 'wpstg' ),
                                    'desc' => __( 'This is an options field', 'wpstg' ),
                                    'type' => 'select',
                                                    'options' => array(
                                                            'value1' => __( 'Value 1', 'wpstg' ),
                                                            'value2' => __( 'Value 2', 'wpstg' )
                                                    )
                            ),
                            array(
                                    'id' => 'wpstg_checkbox',
                                    'name' => __( 'Checkbox', 'wpstg' ),
                                    'desc' => __( 'You already guessed it: This is a Checkbox', 'wpstg' ),
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
 * @since 1.0.0
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
		//$tabs['extensions'] = __( 'Extensions', 'wpstg' );
	}
	
	if( ! empty( $settings['licenses'] ) ) {
		//$tabs['licenses'] = __( 'Licenses', 'wpstg' );
	}
        //$tabs['addons'] = __( 'Add-Ons', 'wpstg' );

	//$tabs['misc']      = __( 'Misc', 'wpstg' );

	return apply_filters( 'wpstg_settings_tabs', $tabs );
}

       /*
	* Retrieve a list of possible expire cache times
	*
	* @since  2.0.0
	* @change 
	*
	* @param  array  $methods  Array mit verfügbaren Arten
	*/

        function wpstg_get_expiretimes()
	{
		/* Defaults */
        $times = array(
        '300' => 'in 5 minutes',
        '600' => 'in 10 minutes',
        '1800' => 'in 30 minutes',
        '3600' => 'in 1 hour',
        '21600' => 'in 6 hours',
        '43200' => 'in 12 hours',
        '86400' => 'in 24 hours'
        );
            return $times;
	}
   

/**
 * Retrieve array of  social networks Facebook / Twitter / Subscribe
 *
 * @since 2.0.0
 * 
 * @return array Defined social networks
 */
function wpstg_get_networks_list() {

        $networks = get_option('wpstg_networks');
	return apply_filters( 'wpstg_get_networks_list', $networks );
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
 * Gateways Callback
 *
 * Renders gateways fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_gateways_callback( $args ) {
	global $wpstg_options;

	foreach ( $args['options'] as $key => $option ) :
		if ( isset( $wpstg_options['gateways'][ $key ] ) )
			$enabled = '1';
		else
			$enabled = null;

		echo '<input name="wpstg_settings[' . $args['id'] . '][' . $key . ']"" id="wpstg_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="1" ' . checked('1', $enabled, false) . '/>&nbsp;';
		echo '<label for="wpstg_settings[' . $args['id'] . '][' . $key . ']">' . $option['admin_label'] . '</label><br/>';
	endforeach;
}

/**
 * Dropdown Callback (drop down)
 *
 * Renders gateways select menu
 *
 * @since 1.5
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_gateway_select_callback($args) {
	global $wpstg_options;

	echo '<select name="wpstg_settings[' . $args['id'] . ']"" id="wpstg_settings[' . $args['id'] . ']">';

	foreach ( $args['options'] as $key => $option ) :
		$selected = isset( $wpstg_options[ $args['id'] ] ) ? selected( $key, $wpstg_options[$args['id']], false ) : '';
		echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $option['admin_label'] ) . '</option>';
	endforeach;

	echo '</select>';
	echo '<label for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';
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
/*function wpstg_color_select_callback( $args ) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$html = '<select id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']"/>';

	foreach ( $args['options'] as $option => $color ) :
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . $option . '" ' . $selected . '>' . $color['label'] . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}*/

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
 * Color picker Callback
 *
 * Renders color picker fields.
 *
 * @since 1.6
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_color_callback( $args ) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$default = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="wpstg-color-picker" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
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
 * Networks Callback / Facebook, Twitter and Subscribe default
 *
 * Renders network order table. Uses separate option field 'wpstg_networks 
 *
 * @since 2.0.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the wpstg Options
 * @return void
 */

function wpstg_networks_callback( $args ) {
	global $wpstg_options;
       /* Array in $wpstg_option['networks']
        * 
        *                                   array(
                                                0 => array (
                                                    'status' => '1',
                                                    'name' => 'Share on Facebook',
                                                    'name2' => 'Share'
                                                ), 
                                                1 => array (
                                                    'status' => '1',
                                                    'name' => 'Tweet on Twitter',
                                                    'name2' => 'Twitter'
                                                ),
                                                2 => array (
                                                    'status' => '1',
                                                    'name' => 'Subscribe to us',
                                                    'name2' => 'Subscribe'
                                                )
                                            )
        */

       ob_start();
        ?>
        <p class="description"><?php echo $args['desc']; ?></p>
        <table id="wpstg_network_list" class="wp-list-table fixed posts">
		<thead>
			<tr>
				<th scope="col" style="padding: 15px 10px;"><?php _e( 'Social Networks', 'wpstg' ); ?></th>
                                <th scope="col" style="padding: 15px 10px;"><?php _e( 'Enable', 'wpstg' ); ?></th>
                                <th scope="col" style="padding: 15px 10px;"><?php _e( 'Custom name', 'wpstg' ); ?></th>
			</tr>
		</thead>        
        <?php

	if ( ! empty( $args['options'] ) ) {
		foreach( $args['options'] as $key => $option ):
                        echo '<tr id="wpstg_list_' . $key . '" class="wpstg_list_item">';
			if( isset( $wpstg_options[$args['id']][$key]['status'] ) ) { $enabled = 1; } else { $enabled = NULL; }
                        if( isset( $wpstg_options[$args['id']][$key]['name'] ) ) { $name = $wpstg_options[$args['id']][$key]['name']; } else { $name = NULL; }

                        echo '<td class="mashicon-' . strtolower($option) . '"><span class="icon"></span><span class="text">' . $option . '</span></td>
                        <td><input type="hidden" name="wpstg_settings[' . $args['id'] . '][' . $key . '][id]" id="wpstg_settings[' . $args['id'] . '][' . $key . '][id]" value="' . strtolower($option) .'"><input name="wpstg_settings[' . $args['id'] . '][' . $key . '][status]" id="wpstg_settings[' . $args['id'] . '][' . $key . '][status]" type="checkbox" value="1" ' . checked(1, $enabled, false) . '/><td>
                        <input type="text" class="medium-text" id="wpstg_settings[' . $args['id'] . '][' . $key . '][name]" name="wpstg_settings[' . $args['id'] . '][' . $key . '][name]" value="' . $name .'"/>
                        </tr>';
                endforeach;
	}
        echo '</table>';
        echo ob_get_clean();
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
        
        
/* 
 * Post Types Callback
 * 
 * Adds a multiple choice drop box
 * for selecting where WP-Staging should be enabled
 * 
 * @since 2.0.9
 * @param array $args Arguments passed by the setting
 * @return void
 * 
 */

function wpstg_posttypes_callback ($args){
  global $wpstg_options;
  $posttypes = get_post_types();

  //if ( ! empty( $args['options'] ) ) {
  if ( ! empty( $posttypes ) ) {
		//foreach( $args['options'] as $key => $option ):
                foreach( $posttypes as $key => $option ):
			if( isset( $wpstg_options[$args['id']][$key] ) ) { $enabled = $option; } else { $enabled = NULL; }
			echo '<input name="wpstg_settings[' . $args['id'] . '][' . $key . ']" id="wpstg_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="' . $option . '" ' . checked($option, $enabled, false) . '/>&nbsp;';
			echo '<label for="wpstg_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
		endforeach;
		echo '<p class="description wpstg_hidden">' . $args['desc'] . '</p>';
	}
}

/* 
 * Note Callback
 * 
 * Show a note
 * 
 * @since 2.2.8
 * @param array $args Arguments passed by the setting
 * @return void
 * 
 */

function wpstg_note_callback ($args){
  global $wpstg_options;
  //$html = !empty($args['desc']) ? $args['desc'] : '';
  $html = '';
  echo $html;
}

/**
 * Additional content Callback 
 * Adds several content text boxes selectable via jQuery easytabs() 
 *
 * @param array $args
 * @return string $html
 * @scince 2.3.2
 */

function wpstg_add_content_callback($args){
    	global $wpstg_options;

        $html = '<div id="mashtabcontainer" class="tabcontent_container"><ul class="mashtabs" style="width:99%;max-width:500px;">';
            foreach ( $args['options'] as $option => $name ) :
                    $html .= '<li class="mashtab" style="float:left;margin-right:4px;"><a href="#'.$name['id'].'">'.$name['name'].'</a></li>';
            endforeach;
        $html .= '</ul>';
        $html .= '<div class="mashtab-container">';
            foreach ( $args['options'] as $option => $name ) :
                    $value = isset($wpstg_options[$name['id']]) ? $wpstg_options[ $name['id']] : '';
                    $textarea = '<textarea class="large-text wpstg-textarea" cols="50" rows="15" id="wpstg_settings['. $name['id'] .']" name="wpstg_settings['.$name['id'].']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
                    $html .= '<div id="'.$name['id'].'" style="max-width:500px;"><span style="padding-top:60px;display:block;">' . $name['desc'] . ':</span><br>' . $textarea . '</div>';
            endforeach;
        $html .= '</div>';
        $html .= '</div>';
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


/* returns array with amount of available services
 * @since 2.0
 * @return array
 */

function wpstg_numberServices(){
    $number = 1;
    $array = array();
    while ($number <= count(wpstg_get_networks_list())){
        $array[] = $number++; 

    }
    $array['all'] = __('All Services');
    return apply_filters('wpstg_return_services', $array);
}

/* Purge the WP-Staging 
 * database WPSTG_TABLE
 * 
 * @since 2.0.4
 * @return string
 */

function wpstg_delete_cache_objects(){
    global $wpstg_options, $wpdb;
    if (isset($wpstg_options['delete_cache_objects'])){
        //$sql = "TRUNCATE TABLE " . WPSTG_TABLE;
        //require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        //$wpdb->query($sql);
        delete_post_meta_by_key( 'wpstg_timestamp' );
        delete_post_meta_by_key( 'wpstg_shares' ); 
        delete_post_meta_by_key( 'wpstg_jsonshares' );
        return ' <strong style="color:red;">' . __('DB cache deleted! Do not forget to uncheck this box for performance increase after doing the job.', 'wpstg') . '</strong> ';
    }
}

/* returns Cache Status if enabled or disabled
 *
 * @since 2.0.4
 * @return string
 */

function wpstg_cache_status(){
    global $wpstg_options;
    if (isset($wpstg_options['disable_cache'])){
        return ' <strong style="color:red;">' . __('Transient Cache disabled! Enable it for performance increase.' , 'wpstg') . '</strong> ';
    }
}

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
