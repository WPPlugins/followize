<?php
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

class ListForms_Controller extends WP_List_Table
{
	public function __construct()
	{
		parent::__construct(
			array(
				'singular' => 'Formulário',
				'plural'   => 'Formulários',
				'ajax'     => false
			)
		);
	}

	public static function table_data()
	{
		return Form::get_all();
	}

	public static function count_record()
	{
		return Form::count();
	}

	public static function delete_form( $id )
	{
		Form::delete( $id );
		Form_Field::delete( $id );
	}

	public static function duplicate_form( $id )
	{
		Form::duplicate( $id );
		Form_Field::duplicate( $id );
	}

	public function no_items() {
		echo 'Você ainda não adicionou nenhum formulário. <a href="'.Form::get_url_new().'">Adicione um</a>.';
	}

	public function column_title( $item )
	{
		$delete_nonce    = wp_create_nonce( 'fwz_delete_form' );
		$duplicate_nonce = wp_create_nonce( 'fwz_duplicate_form' );
		$title           = sprintf( '<strong><a href="?page=followize-page-form&form=%s">%s</a></strong>', intval( $item['id'] ), ( $item['title'] ) ? $item['title'] : '(Sem título)' );

		$actions = array(
			'edit'      => sprintf( '<a href="?page=followize-page-form&form=%s">Editar</a>', intval( $item['id'] ) ),
			'duplicate' => sprintf( '<a href="?page=%s&action=%s&form=%s&_wpnonce=%s&message=3">Duplicar</a>', esc_attr( $_REQUEST['page'] ), 'duplicate', intval( $item['id'] ), $duplicate_nonce ),
			'delete'    => sprintf( '<a href="?page=%s&action=%s&form=%s&_wpnonce=%s&message=2" onclick="if(!confirm(\'O formulário será excluído permanentemente!\')){return false;}">Excluir</a>', esc_attr( $_REQUEST['page'] ), 'delete', intval( $item['id'] ), $delete_nonce ),
		);

		return $title . $this->row_actions( $actions );
	}

	public function column_shortcode( $item )
	{
		return sprintf(
			'<input type="text" class="large-text" value=\'[followize id="%d"]\' onclick="this.select();" readonly>',
			$item['id']
		);
	}

	public function column_default( $item, $column_name )
	{
		switch ( $column_name ) {
			case 'team_key':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	function get_columns()
	{
		$columns = array(
			'title'     => 'Título',
			'shortcode' => 'Shortcode',
			'team_key'  => 'Chave de equipe'
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
			'title' => array( 'title', false )
		);
	}

	public function prepare_items()
	{
		$this->_remove_http_referer();
		$this->_process_row_action();

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$per_page = 20;

		$data = $this->table_data( $per_page, 1 );
		usort( $data, array( &$this, 'sort_data' ) );

		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page
			)
		);

		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $data;
	}

	public function _process_row_action()
	{
		if ( 'delete' === $this->current_action() ) {
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'fwz_delete_form' ) ) {
				return;
			}

			self::delete_form( intval( $_GET['form'] ) );
			return;
		}

		if ( 'duplicate' === $this->current_action() ) {
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'fwz_duplicate_form' ) ) {
				return;
			}

			self::duplicate_form( intval( $_GET['form'] ) );
			return;
		}
	}

	private function sort_data( $a, $b )
	{
		$orderby = 'id';
		$order   = 'desc';

		if( ! empty($_GET['orderby'] ) ) {
			$orderby = $_GET['orderby'];
		}

		if( ! empty($_GET['order'] ) ) {
			$order = $_GET['order'];
		}

		$result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );

		if( $order === 'asc' ) {
			return $result;
		}

		return -$result;
	}

	private function _remove_http_referer()
	{
		$_SERVER['REQUEST_URI'] = remove_query_arg( '_wp_http_referer', $_SERVER['REQUEST_URI'] );
	}
}