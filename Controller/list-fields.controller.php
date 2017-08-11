<?php
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

class ListFields_Controller extends WP_List_Table
{
	public function __construct()
	{
		parent::__construct(
			array(
				'singular' => 'Campo',
				'plural'   => 'Campos',
				'ajax'     => false
			)
		);

		wp_enqueue_script( 'jquery-ui-sortable' );
	}

	public static function table_data()
	{
		$form = Utils::get( 'form', 0, 'intval' );

		if ( $form ) {
			return self::add_field_data( Form_Field::get_all( $form ) );
		} else {
			return self::add_field_data( Field::get_all() );
		}
	}

	public static function add_field_data( $data )
	{
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$new_data = array();

		foreach ( $data as $item ) {
			$model = new Form_Field( Utils::get( 'form', 0, 'intval' ), $item['field_name'] );
			$item['field_order'] = $model->field_order;
			$new_data[] = $item;
		}

		return $new_data;
	}

	public function no_items() {
		echo 'Nenhum campo encontrado';
	}

	public function column_field_order( $item )
	{
		$model = new Form_Field( Utils::get( 'form', 0, 'intval' ), $item['field_name'] );
		return sprintf( '<input type="text" name="fields[%s][field_order]" size="1" value="%s">', $item['field_name'], '#' );
	}

	public function column_label( $item )
	{
		$model = new Form_Field( Utils::get( 'form', 0, 'intval' ), $item['field_name'] );
		return sprintf( '<input type="text" class="large-text" name="fields[%s][label]" value="%s">', $item['field_name'], $model->label );
	}

	public function column_value( $item )
	{
		$model = new Form_Field( Utils::get( 'form', 0, 'intval' ), $item['field_name'] );
		return sprintf( '<input type="text" class="large-text" name="fields[%s][value]" value="%s">', $item['field_name'], $model->value );
	}

	public function column_enabled( $item )
	{
		$model    = new Form_Field( Utils::get( 'form', 0, 'intval' ), $item['field_name'] );
		$field    = new Field( $item['field_name'] );
		$disabled = $field->required ? 'disabled' : '';

		return sprintf(
			'<input type="checkbox" name="fields[%s][enabled]" value="1" %s %s>',
			$item['field_name'],
			checked( 1, $model->enabled, false ),
			$disabled
		);
	}

	public function column_required( $item )
	{
		$model    = new Form_Field( Utils::get( 'form', 0, 'intval' ), $item['field_name'] );
		$field    = new Field( $item['field_name'] );
		$disabled = $field->required ? 'disabled' : '';

		return sprintf(
			'<input type="checkbox" name="fields[%s][required]" value="1" %s %s>',
			$item['field_name'],
			checked( 1, $model->required, false ),
			$disabled
		);
	}

	public function column_hidden_field( $item )
	{
		$model = new Form_Field( Utils::get( 'form', 0, 'intval' ), $item['field_name'] );
		return sprintf( '<input type="checkbox" name="fields[%s][hidden]" value="1" %s>', $item['field_name'], checked( 1, $model->hidden, false ) );
	}

	public function column_default( $item, $column_name )
	{
		switch ( $column_name ) {
			case 'field_name':
				return sprintf(
					'<strong>%1$s</strong><input type="hidden" name="fields[%1$s][field_name]" value="%1$s"><input type="hidden" name="fields[%1$s][field_order]" value="#">',
					$item[ $column_name ],
					$column_name
				);
				break;
			case 'description':
				return $item[ $column_name ];
				break;
			default:
				return print_r( $item, true );
		}
	}

	function get_columns()
	{
		$columns = array(
			// 'field_order'  => 'Ordem',
			'field_name'   => 'Campo',
			'label'        => 'Rótulo',
			'value'        => 'Valor',
			'enabled'      => 'Habilitar',
			'required'     => 'Requerido',
			'hidden_field' => 'Oculto',
			'description'  => 'Descrição',
		);

		return $columns;
	}

	function get_hidden_columns()
	{
		return array();
	}

	public function get_sortable_columns()
	{
		return array(
			// 'field_name'  => array( 'field_name', false ),
			// 'label'       => array( 'label', false ),
			// 'enabled'     => array( 'enabled', false ),
			// 'required'    => array( 'required', false ),
			// 'description' => array( 'description', false ),
		);
	}

	public function prepare_items()
	{
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$data = $this->table_data();

		array_walk( $data, array( &$this, 'decorate' ) );
		usort( $data, array( &$this, 'sort_data' ) );
		array_walk( $data, array( &$this, 'undecorate' ) );

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $data;
	}

	private function sort_data( $a, $b )
	{
		if( $a[ 'field_order' ] == $b[ 'field_order' ] ) {
			return 0;
		}

		return ( $a[ 'field_order' ] < $b[ 'field_order' ] ) ? -1 : 1;
	}

	private function decorate( &$v, $k )
	{
		$v['field_order'] = array($v['field_order'], $k);
	}

	private function undecorate( &$v, $k )
	{
	    $v['field_order'] = $v['field_order'][0];
	}
}