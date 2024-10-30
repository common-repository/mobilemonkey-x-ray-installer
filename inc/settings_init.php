<?php

/**
 * custom option and settings
 */

function mmxray_xray_script_callback( $args ) {
	?>
  <textarea name="xray_script" id="xray_script" rows="10"
            cols="50"><?php echo esc_textarea( get_option( 'xray_script' ) ); ?></textarea>
	<?php
}
function mmxray_xray_enabled_callback( $args ) {
	?>
  <input name="xray_enabled" id="xray_enabled" type="checkbox" <?php echo ((bool)get_option( 'xray_enabled' ) ? 'checked="checked"' : ''); ?> />
	<?php
}

function mmxray_xray_section_script_callback( $args ) {
	?>
  <p>Paste your X-Ray code from your installation page or automations page here.</p>
  <p>For support please contact us at <a href="mailto:cs@mobilemonkey.com">cs@mobilemonkey.com</a></p>
	<?php
}

function mmxray_sanitize_xray_script( $data ) {
	$has_errors = false;

	if ( empty( $data ) || strlen( $data ) == 0 ) {
		add_settings_error( 'xray_messages', 'xray_message', 'X-Ray Script is required', 'error' );
		$has_errors = true;
	}

	if ( ! $has_errors && ! preg_match( '/https:\/\/(mm-uxrv\.com|[\w-]+\.mobilemonkey\.com)\/js\/[\w\-_]+\.js/',$data) ) {
		add_settings_error( 'xray_messages', 'xray_message', 'X-Ray code is invalid; please paste entire code snippet from MobileMonkey', 'error' );
		$has_errors = true;
	}
	if ( $has_errors ) {
		return get_option( 'xray_script' );
	}

	return $data;
}


function mmxray_xray_settings_init() {
	// Register a new setting for "xray" page.
	register_setting( 'xray', 'xray_script', [
		'type'              => 'string',
		'description'       => 'X-Ray Code',
		'sanitize_callback' => 'mmxray_sanitize_xray_script',
		'show_in_rest'      => true,
	] );
	register_setting( 'xray', 'xray_enabled', [
		'type'              => 'boolean',
		'description'       => 'X-Ray Enabled',
		'show_in_rest'      => true,
	] );

	// Register a new section in the "xray" page.
	add_settings_section( 'xray_section_script', 'Installation', 'mmxray_xray_section_script_callback', 'xray' );

	add_settings_field( 'xray_enabled', 'X-Ray Enabled', 'mmxray_xray_enabled_callback', 'xray', 'xray_section_script', [
		'label_for' => 'xray_enabled',
		'class'     => 'xray_row',
	] );


	add_settings_field( 'xray_script', 'X-Ray Code', 'mmxray_xray_script_callback', 'xray', 'xray_section_script', [
		'label_for' => 'xray_script',
		'class'     => 'xray_row',
	] );
}

/**
 * Register our xray_settings_init to the admin_init action hook.
 */
add_action( 'admin_init', 'mmxray_xray_settings_init' );
