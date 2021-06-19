<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get translation string from NashaatTranslation class
 *
 * @param string $key Translation key
 * @return string Related translation
 */
function get_nashaat_lang( string $key ) {
	$nashaat_translation = NashaatTranslation::get_instance();
	return esc_html( $nashaat_translation->get_translation_string( $key ) );
}

/**
 * Extend wp_kses_post to add input tags to allowed html
 *
 * @param string $data Data to escape
 * @param bool   $echo true to echo (default), false to return
 * @return string The escaped data
 */
function nashaat_kses_post( string $data, $echo = true ) :string {
	// Get default allowed html for post
	$allowed_html = wp_kses_allowed_html( 'post' );
	// Add extra tags and attributes
	// form fields - input
	$allowed_html['input'] = array(
		'class' => array(),
		'id'    => array(),
		'name'  => array(),
		'value' => array(),
		'type'  => array(),
		'size'  => array(),
		'min'  => array(),
		'max'  => array(),
		'checked'  => array()
	);

	// select
	$allowed_html['select'] = array(
		'class'  => array(),
		'id'     => array(),
		'name'   => array(),
		'value'  => array(),
		'type'   => array(),
	);
	// select options
	$allowed_html['option'] = array(
		'selected' => array(),
	);
	if ( $echo ) {
		echo wp_kses( $data, $allowed_html );
		return '';
	}
	return wp_kses( $data, $allowed_html );
}