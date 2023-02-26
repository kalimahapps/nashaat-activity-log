<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait NashaatUtil {
	/**
	 * Get keys from $keep_keys and create a new array based on that
	 *
	 * @param mixed $input Object or array to pluck data from
	 * @param array $keep_keys Keys to match against $input
	 * @return array New array with required keys
	 */
	public function pluck_object( $input, array $keep_keys ) {
		$output_array = array();

		foreach ( $input as $key => $value ) {
			// Loop through $keep_keys to find needed key
			// if $key_rename is supplied change the key to it
			foreach ( $keep_keys as $keep_key => $key_rename ) {

				// Check if $keep_key is an integer (to check for numeric array vs associative)
				if ( is_int( $keep_key ) && $key === $key_rename ) {
					$output_array[ $key ] = $value;
				} else if ( ! is_int( $keep_key ) && $key === $keep_key ) {
					// if not an integer then use $key_rename as key
					$output_array[ $key_rename ] = $value;
				}
			}
		}
		return $output_array;
	}

	/**
	 * Clean string by replacing anything not alphanumeric with _
	 *
	 * @param string $string String to clean
	 * @return string Cleaned string
	 */
	private function clean_string( string $string ) {
		return preg_replace( '/[^a-zA-Z0-9_]/', '_', $string );
	}
}