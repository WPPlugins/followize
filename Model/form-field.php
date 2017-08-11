<?php
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

class Form_Field extends Table
{
	static $primary_key   = 'form_id';
	static $secundary_key = 'field_name';
	static $table         = 'followize_form_fields';

	public $form_id;
	public $field_name;
	private $field_type;
	private $field_limit;
	private $label;
	private $value;
	private $required;
	private $enabled;
	private $mask;
	private $hidden;
	private $field_order;

	public function __construct( $form_id = false, $field_name = false )
	{
		$this->form_id    = $form_id;
		$this->field_name = $field_name;

		if ( $form_id && $field_name ) {
			$this->_fill_fields( $form_id, $field_name );
		}
	}

	public function __get( $prop_name )
	{
		return $this->_get_prop_value( $prop_name );
	}

	public function find( $form_id )
	{
		if ( ! $form_id ) {
			return false;
		}

		return $this->_parse( self::get_fields_name( $form_id ), $form_id );
	}

	static function delete_field( $form_id, $field_name )
	{
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE %s = %%s AND %s = %%s', self::table_name(), static::$primary_key, static::$secundary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $form_id, $field_name ) );
	}

	static function duplicate( $value )
	{
		global $wpdb;
		$sql = sprintf( 'INSERT INTO %1$s (form_id, field_name, label, required, enabled, hidden, field_order, value) SELECT LAST_INSERT_ID(), field_name, label, required, enabled, hidden, field_order, value FROM %1$s WHERE %2$s = %%s', self::table_name(), static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $value ) );
	}

	public static function get_all( $form )
	{
		global $wpdb;

		$form_fields = self::table_name();
		$fields      = Field::table_name();

		return $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT
					ff.*, COALESCE(f.description, 'Campo customizado.') as description
				FROM
					{$form_fields} ff
				LEFT JOIN
					{$fields} f
				ON
					f.field_name = ff.field_name
				WHERE
					ff.form_id = %d;
				",
				$form
			),
			'ARRAY_A'
		);
	}

	public static function maybe_create_table()
	{
		$tablename = self::table_name();
		$charset   = self::get_charset();

		$sql =
		"
			CREATE TABLE {$tablename} (
				form_id BIGINT(20) NOT NULL,
				field_name VARCHAR(30) NOT NULL,
				label VARCHAR(255),
				value VARCHAR(255),
				required BOOL NOT NULL,
				enabled BOOL NOT NULL,
				hidden BOOL NOT NULL,
				field_order INT DEFAULT 0,
				PRIMARY KEY  (form_id,field_name)
			) {$charset} ENGINE = MYISAM;
		";

		self::create_table( $sql );
	}

	static function get_field( $form_id, $field_name )
	{
		global $wpdb;
		$sql = sprintf( 'SELECT * FROM %s WHERE form_id=%%d AND field_name=%%s', self::table_name() );
		return $wpdb->get_row( $wpdb->prepare( $sql, $form_id, $field_name ), 'ARRAY_A' );
	}

	static function get_fields_name( $form_id )
	{
		global $wpdb;
		$sql = sprintf( 'SELECT field_name FROM %s WHERE form_id=%%d AND enabled=1 ORDER BY field_order', self::table_name() );
		return $wpdb->get_results( $wpdb->prepare( $sql, $form_id ), 'ARRAY_A' );
	}

	private function _fill_fields( $form_id, $field_name )
	{
		$data = self::get_field( $form_id, $field_name );

		if ( ! is_array( $data ) ) {
			return;
		}

		foreach ( $this as $key => $value ) {
			if ( isset( $data[ $key ] ) ) {
				$this->$key = $data[ $key ];
			}
		}
	}

	private function _get_prop_value( $prop_name )
	{
		$field = new Field( $this->field_name );
		return ( ! is_null( @$this->$prop_name ) ) ? @$this->$prop_name : @$field->$prop_name;
	}

	private function _parse( $fields, $form_id )
	{
		if ( ! is_array( $fields ) ) {
			return false;
		}

		$list = array();

		foreach ( $fields as $field ) {
			$list[] = new $this( $form_id, $field['field_name'] );
		}

		return $list;
	}
}