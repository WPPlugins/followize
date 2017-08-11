<?php
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

class Field extends Table
{
	static $primary_key = 'field_name';
	static $table       = 'followize_fields';

	public $field_name;
	public $field_type;
	public $field_limit;
	public $field_order;
	public $label;
	public $required;
	public $enabled;
	public $hidden;
	public $description;
	public $mask;

	public function __construct( $field_name = '' )
	{
		if ( $field_name ) {
			$this->_fill_fields( $field_name );
		}
	}

	public static function maybe_create_table()
	{
		$tablename = self::table_name();
		$charset   = self::get_charset();

		$sql =
		"
			CREATE TABLE {$tablename} (
				field_name VARCHAR(30) NOT NULL,
				field_type VARCHAR(10) NOT NULL,
				field_limit INT NOT NULL,
				label VARCHAR(255),
				required BOOL not null,
				enabled BOOL not null,
				description VARCHAR(255),
				mask VARCHAR(255),
				PRIMARY KEY  (field_name)
			) {$charset} ENGINE = MYISAM;
		";

		self::create_table( $sql );
		self::_maybe_insert_field();
	}

	public static function get_fields_name()
	{
		global $wpdb;

		$sql = sprintf( 'SELECT field_name FROM %s', self::table_name() );
		return $wpdb->get_col( $sql );
	}

	public static function get_all()
	{
		global $wpdb;

		$sql = sprintf( 'SELECT * FROM %s', self::table_name() );
		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	private static function _maybe_insert_field()
	{
		$fields = self::_get_default_fields();

		foreach ( $fields as $field ) {
			if ( self::get( $field['field_name'] ) ) {
				self::update( $field, array( 'field_name' => $field['field_name'] ) );
			} else {
				self::insert( $field );
			}
		}
	}

	private static function _get_default_fields()
	{
		return array(
			array(
				'field_name'  => 'name',
				'field_type'  => 'text',
				'field_limit' => 100,
				'label'       => 'Nome',
				'required'    => 1,
				'enabled'     => 1,
				'description' => 'Nome do cliente.',
			),
			array(
				'field_name'  => 'email',
				'field_type'  => 'email',
				'field_limit' => 150,
				'label'       => 'E-mail',
				'required'    => 1,
				'enabled'     => 1,
				'description' => 'E-mail do cliente.',
			),
			array(
				'field_name'  => 'message',
				'field_type'  => 'text',
				'field_limit' => -1,
				'label'       => 'Mensagem',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Mensagem.',
			),
			array(
				'field_name'  => 'phone',
				'field_type'  => 'text',
				'field_limit' => 40,
				'label'       => 'Telefone',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Telefone do cliente.',
				'mask'        => '(00) 0000-00009',
			),
			array(
				'field_name'  => 'cellPhone',
				'field_type'  => 'text',
				'field_limit' => 40,
				'label'       => 'Celular',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Celular do cliente.',
				'mask'        => '(00) 0000-00009',
			),
			array(
				'field_name'  => 'addressLine1',
				'field_type'  => 'text',
				'field_limit' => 200,
				'label'       => 'Endereço',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Endereço do cliente.',
			),
			array(
				'field_name'  => 'addressLine2',
				'field_type'  => 'text',
				'field_limit' => 20,
				'label'       => 'Complemento',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Complemento do endereço do cliente.',
			),
			array(
				'field_name'  => 'neighborhood',
				'field_type'  => 'text',
				'field_limit' => 50,
				'label'       => 'Bairro',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Bairro do cliente.',
			),
			array(
				'field_name'  => 'city',
				'field_type'  => 'text',
				'field_limit' => 50,
				'label'       => 'Cidade',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Cidade do cliente.',
			),
			array(
				'field_name'  => 'state',
				'field_type'  => 'text',
				'field_limit' => 50,
				'label'       => 'Estado',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Estado do cliente.',
			),
			array(
				'field_name'  => 'country',
				'field_type'  => 'text',
				'field_limit' => 50,
				'label'       => 'País',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'País do cliente.',
			),
			array(
				'field_name'  => 'zipCode',
				'field_type'  => 'text',
				'field_limit' => 9,
				'label'       => 'CEP',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'CEP do cliente.',
				'mask'        => '00000-000',
			),
			array(
				'field_name'  => 'registrationNumber',
				'field_type'  => 'text',
				'field_limit' => 20,
				'label'       => 'CPF',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'CPF do cliente.',
				'mask'        => '000.000.000-00',
			),
			array(
				'field_name'  => 'jobTitle',
				'field_type'  => 'text',
				'field_limit' => 50,
				'label'       => 'Cargo',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Cargo do cliente.',
			),
			array(
				'field_name'  => 'companyName',
				'field_type'  => 'text',
				'field_limit' => 100,
				'label'       => 'Empresa',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Nome da empresa do cliente.',
			),
			array(
				'field_name'  => 'companyWebsite',
				'field_type'  => 'text',
				'field_limit' => 150,
				'label'       => 'Site',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Site da empresa do cliente.',
			),
			array(
				'field_name'  => 'companyRegistrationNumber',
				'field_type'  => 'text',
				'field_limit' => 20,
				'label'       => 'CNPJ',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'CNPJ da empresa do cliente.',
				'mask'        => '00.000.000/0000-00',
			),
			array(
				'field_name'  => 'socialTwitter',
				'field_type'  => 'text',
				'field_limit' => 50,
				'label'       => 'Twitter',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Usuário no Twitter do cliente.',
			),
			array(
				'field_name'  => 'socialFacebook',
				'field_type'  => 'text',
				'field_limit' => 50,
				'label'       => 'Facebook',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Usuário no Facebook do cliente.',
			),
			array(
				'field_name'  => 'socialLinkedin',
				'field_type'  => 'text',
				'field_limit' => 50,
				'label'       => 'Linkedin',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Usuário no LinkedIn do cliente.',
			),
			array(
				'field_name'  => 'socialGoogleplus',
				'field_type'  => 'text',
				'field_limit' => 50,
				'label'       => 'Google Plus',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Usuário no Google Plus do cliente.',
			),
			array(
				'field_name'  => 'socialSkype',
				'field_type'  => 'text',
				'field_limit' => 50,
				'label'       => 'Skype',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Usuário no Skype do cliente.',
			),
			array(
				'field_name'  => 'socialWhatsapp',
				'field_type'  => 'text',
				'field_limit' => 50,
				'label'       => 'WhatsApp',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Número de telefone cadastrado no WhatsApp do cliente.',
				'mask'        => '(00) 0000-00009',
			),
			array(
				'field_name'  => 'emailOptIn',
				'field_type'  => 'checkbox',
				'field_limit' => 1,
				'label'       => 'Aceito receber e-mail marketing',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Cliente aceita ou não receber email marketing.',
			),
			array(
				'field_name'  => 'productId',
				'field_type'  => 'number',
				'field_limit' => 10,
				'label'       => 'ID do produto',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'ID do produto cadastrado no Followize.',
			),
			array(
				'field_name'  => 'productTitle',
				'field_type'  => 'text',
				'field_limit' => 200,
				'label'       => 'Título do produto',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Título do produto.',
			),
			array(
				'field_name'  => 'productRefer',
				'field_type'  => 'number',
				'field_limit' => 10,
				'label'       => 'ID de referência do produto',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'ID de referência do produto cadastrado no site e/ou ERP próprio.',
			),
			array(
				'field_name'  => 'categoryId',
				'field_type'  => 'number',
				'field_limit' => 10,
				'label'       => 'ID da categoria',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'ID da categoria cadastrada no Followize.',
			),
			array(
				'field_name'  => 'categoryTitle',
				'field_type'  => 'text',
				'field_limit' => 200,
				'label'       => 'Título da categoria',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Título da categoria.',
			),
			array(
				'field_name'  => 'categoryRefer',
				'field_type'  => 'number',
				'field_limit' => 10,
				'label'       => 'ID de referência da categoria',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'ID de referência da categoria cadastrada no site e/ou ERP próprio.',
			),
			array(
				'field_name'  => 'brandId',
				'field_type'  => 'number',
				'field_limit' => 10,
				'label'       => 'ID da marca',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'ID da marca cadastrada no Followize.',
			),
			array(
				'field_name'  => 'brandTitle',
				'field_type'  => 'text',
				'field_limit' => 200,
				'label'       => 'Título da marca',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Título da marca.',
			),
			array(
				'field_name'  => 'brandRefer',
				'field_type'  => 'number',
				'field_limit' => 10,
				'label'       => 'ID de referência da marca',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'ID de referência da marca cadastrada no site e/ou ERP próprio.',
			),
			array(
				'field_name'  => 'locationId',
				'field_type'  => 'number',
				'field_limit' => 10,
				'label'       => 'ID da unidade',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'ID da unidade cadastrada no Followize.',
			),
			array(
				'field_name'  => 'locationTitle',
				'field_type'  => 'text',
				'field_limit' => 200,
				'label'       => 'Título da unidade',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'Título da unidade.',
			),
			array(
				'field_name'  => 'locationRefer',
				'field_type'  => 'number',
				'field_limit' => 10,
				'label'       => 'ID de referência da unidade',
				'required'    => 0,
				'enabled'     => 0,
				'description' => 'ID de referência da unidade cadastrada no site e/ou ERP próprio.',
			),
		);
	}

	private function _fill_fields( $field_name )
	{
		$data = self::get( $field_name );

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