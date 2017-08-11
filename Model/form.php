<?php
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

class Form extends Table
{
	static $table = 'followize_form';

	public $id;
	public $title;
	public $team_key;
	public $conversion_goal;
	public $custom_css;
	public $button_text;
	public $redirect_url;
	public $hide_title;
	public $label_placeholder;
	private $fields;

	public function __construct( $id = false )
	{
		if ( $id ) {
			$this->_fill_fields( $id );
		}
	}

	public function __get( $prop_name )
	{
		switch ( $prop_name ) {

			case 'fields' :
				if ( ! isset( $this->fields ) ) {
					$form_field       = new Form_Field( $this->id );
					$this->$prop_name = $form_field->find( $this->id );
				}
				break;
			default :
				return false;

		}

		return $this->$prop_name;
	}

	public static function maybe_create_table()
	{
		$tablename = self::table_name();
		$charset   = self::get_charset();

		$sql =
		"
			CREATE TABLE {$tablename} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				title VARCHAR(255) NOT NULL,
				team_key VARCHAR(255) NOT NULL,
				conversion_goal VARCHAR(255) NOT NULL,
				custom_css TEXT,
				button_text VARCHAR(255) NOT NULL,
				redirect_url VARCHAR(255) NOT NULL,
				hide_title BOOLEAN,
				label_placeholder BOOLEAN,
				PRIMARY KEY  (id)
			) {$charset} ENGINE = MYISAM;
		";

		self::create_table( $sql );
	}

	public static function get_all()
	{
		$search = ( isset( $_GET['s'] ) ) ? $_GET['s'] : false;

		if ( $search ) {
			return self::_get_where( $search );
		}

		return self::_get_all();
	}

	public static function get_url_new()
	{
		return admin_url( 'admin.php?page=followize-page-form' );
	}

	public function get_valid_team_key()
	{
		return preg_replace( '/.+:\s?/', '', $this->team_key );
	}

	static function duplicate( $value )
	{
		global $wpdb;
		$sql = sprintf( 'INSERT INTO %1$s (title, team_key, conversion_goal, custom_css, button_text, redirect_url, hide_title, label_placeholder) SELECT CONCAT(title, " Cópia"), team_key, conversion_goal, custom_css, button_text, redirect_url, hide_title, label_placeholder FROM %1$s WHERE %2$s = %%s', self::table_name(), static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $value ) );
	}

	private static function _get_all()
	{
		global $wpdb;

		$sql = sprintf( 'SELECT * FROM %s', self::table_name() );
		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	private static function _get_where( $search )
	{
		global $wpdb;

		$sql = sprintf( 'SELECT * FROM %s WHERE title like %%s', self::table_name() );
		return $wpdb->get_results( $wpdb->prepare( $sql, '%'.$wpdb->esc_like( $search ).'%' ), 'ARRAY_A' );
	}

	private function _fill_fields( $id )
	{
		$data = self::get( $id );

		if ( ! is_array( $data ) ) {
			return;
		}

		foreach ( $this as $key => $value ) {
			if ( isset( $data[ $key ] ) ) {
				$this->$key = $data[ $key ];
			}
		}
	}
}