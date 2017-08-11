<?php
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

abstract class Table
{
	static $primary_key = 'id';
	static $table       = '';

	static function table_name()
	{
		global $wpdb;
		return $wpdb->prefix . static::$table;
	}

	static function get( $value )
	{
		global $wpdb;
		return $wpdb->get_row( self::_fetch_sql( $value ), 'ARRAY_A' );
	}

	static function insert_or_update( $data, $where )
	{
		global $wpdb;

		$table_name = self::table_name();
		$where_sql  = esc_sql( $where );

		array_walk( $where_sql, function( &$value, $key ) {
			$value = sprintf( "'%s'", $value );
			$value = "{$key}={$value}";
		} );

		$where_sql = implode( ' AND ', $where_sql );

		$result = (int)$wpdb->get_var(
			"
			SELECT
				COUNT(*)
			FROM
				{$table_name}
			WHERE
				{$where_sql};
			"
		);

		if ( $result ) {
			self::update( $data, $where );
		} else {
			self::insert( $data );
		}
	}

	static function insert( $data )
	{
		global $wpdb;
		return $wpdb->insert( self::table_name(), $data );
	}

	static function update( $data, $where )
	{
		global $wpdb;
		$wpdb->update( self::table_name(), $data, $where );
	}

	static function delete( $value )
	{
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE %s = %%s', self::table_name(), static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $value ) );
	}

	static function count()
	{
		global $wpdb;
		return $wpdb->get_var( sprintf( 'SELECT COUNT(*) FROM %s', self::table_name() ) );
	}

	static function insert_id()
	{
		global $wpdb;
		return $wpdb->insert_id;
	}

	static function time_to_date( $time )
	{
		return gmdate( 'Y-m-d H:i:s', $time );
	}

	static function now()
	{
		return self::time_to_date( time() );
	}

	static function date_to_time( $date )
	{
		return strtotime( $date . ' GMT' );
	}

	static function get_charset()
	{
		global $wpdb;

		$charset_collate = '';

		if ( ! $wpdb->has_cap( 'collation' ) ) {
			return;
		}

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= "\nCOLLATE $wpdb->collate";
		}

		return $charset_collate;
	}

	static function create_table( $sql )
	{
		include_once ABSPATH . '/wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	private static function _fetch_sql( $value )
	{
		global $wpdb;
		$sql = sprintf( 'SELECT * FROM %s WHERE %s = %%s', self::table_name(), static::$primary_key );
		return $wpdb->prepare( $sql, $value );
	}
}