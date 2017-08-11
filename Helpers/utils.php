<?php
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

class Utils
{
	public static function post( $key, $default = false, $sanitize = false )
	{
		$value = isset( $_POST[ $key ] ) ? $_POST[ $key ] : $default;

		if ( $sanitize && is_callable( $sanitize ) ) {
			$value = call_user_func( $sanitize, $value );
		}

		return $value;
	}

	public static function get( $key, $default = false, $sanitize = false )
	{
		$value = isset( $_GET[ $key ] ) ? $_GET[ $key ] : $default;

		if ( $sanitize && is_callable( $sanitize ) ) {
			$value = call_user_func( $sanitize, $value );
		}

		return $value;
	}

	public static function to_string_value( $fields )
	{
		if ( ! is_array( $fields ) ) {
			return $fields;
		}

		foreach ( $fields as $key => $field ) :
			if ( is_array( $field ) ) {
				$fields[ $key ] = self::implode( $field );
			}
		endforeach;

		return $fields;
	}

	public static function implode( $value, $sep = ', ' )
	{
		if ( is_array( $value ) ) {
			return implode( $sep, array_map( __METHOD__, $value ) );
		}

		return $value;
	}
}