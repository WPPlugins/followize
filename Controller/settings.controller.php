<?php
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

App::uses( 'setting', 'Model' );
App::uses( 'settings', 'View' );

class Settings_Controller
{
	public $model;

	public function __construct()
	{
		$this->model = new Setting();

		add_action( 'admin_menu', array( &$this, 'add_menu' ) );
		add_action( 'admin_init', array( &$this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . App::FILE, array( &$this, 'add_action_links' ) );
		add_action( 'admin_notices', array( &$this, 'notice_fill_configurations' ) );
	}

	public function add_menu()
	{
		add_submenu_page(
			'followize-page',
			'Configurações',
			'Configurações',
			'manage_options',
			'followize-config',
			array( 'Followize\Settings_View', 'render_page_config' )
		);
	}

	public function register_settings()
	{
		$this->_register_settings_config();
	}

	public function add_action_links ( $links )
	{
		$config_page = $this->model->get_url_config_page();

		$my_links = array(
			"<a href=\"{$config_page}\">Configurar</a>",
		);

		return array_merge( $links, $my_links );
	}

	public function notice_fill_configurations()
	{
		$config_page = isset( $_GET['page'] ) ? $_GET['page'] : false;

		if ( $config_page == 'followize-config' ) {
			return;
		}

		if ( ! $this->model->client_key || ! $this->model->team_keys ) {
			$config_page = $this->model->get_url_config_page();

			printf(
				'<div class="updated"><p><strong>Clique %1$s para configurar sua conta do Followize</strong></p></div>',
				"<a href=\"$config_page\">aqui</a>"
			);
		}
	}

	private function _register_settings_config()
	{
		register_setting( 'followize-settings-config-group', Setting::OPTION_CLIENT_KEY, 'esc_attr' );
		register_setting( 'followize-settings-config-group', Setting::OPTION_TEAM_KEYS, 'esc_html' );
		register_setting( 'followize-settings-config-group', Setting::OPTION_DISABLE_CSS, 'intval' );
		register_setting( 'followize-settings-config-group', Setting::OPTION_CUSTOM_CSS, 'wp_filter_nohtml_kses' );
		register_setting( 'followize-settings-config-group', Setting::OPTION_EMAILS, 'esc_attr' );

		add_settings_section(
			'followize-settings-config-section',
			'Chaves de integração',
			array( 'Followize\Settings_View', 'render_section_config' ),
			'followize-config'
		);

		add_settings_section(
			'followize-settings-advanced-section',
			'Configurações avançadas',
			array( 'Followize\Settings_View', 'render_section_advanced' ),
			'followize-config'
		);

		add_settings_field(
			'followize-settings-field-client-key',
			'Cliente',
			array( 'Followize\Settings_View', 'render_field_client_key' ),
			'followize-config',
			'followize-settings-config-section',
			array(
				'label_for' => 'client-key-id',
				'model'     => $this->model
			)
		);

		add_settings_field(
			'followize-settings-field-team-keys',
			'Equipes',
			array( 'Followize\Settings_View', 'render_field_team_keys' ),
			'followize-config',
			'followize-settings-config-section',
			array(
				'label_for' => 'team-keys-id',
				'model'     => $this->model
			)
		);

		add_settings_field(
			'followize-settings-field-disable-css',
			'Incluir CSS',
			array( 'Followize\Settings_View', 'render_field_disable_css' ),
			'followize-config',
			'followize-settings-advanced-section',
			array(
				'label_for' => 'disable-css-id',
				'model'     => $this->model
			)
		);

		add_settings_field(
			'followize-settings-field-custom-css',
			'CSS Customizado',
			array( 'Followize\Settings_View', 'render_field_custom_css' ),
			'followize-config',
			'followize-settings-advanced-section',
			array(
				'label_for' => 'custom-css-id',
				'model'     => $this->model
			)
		);

		add_settings_field(
			'followize-settings-field-emails',
			'E-mail',
			array( 'Followize\Settings_View', 'render_field_emails' ),
			'followize-config',
			'followize-settings-advanced-section',
			array(
				'label_for' => 'emails-id',
				'model'     => $this->model
			)
		);
	}
}