<?php
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

App::uses( 'class-wp-list-table', 'Controller/base' );

App::uses( 'utils', 'Helpers' );

App::uses( 'table', 'Model/base' );
App::uses( 'field', 'Model' );
App::uses( 'form-field', 'Model' );

App::uses( 'forms', 'Controller' );
App::uses( 'settings', 'Controller' );
App::uses( 'widgets', 'Controller' );

class Core
{
	public function __construct()
	{
		add_action( 'admin_init', array( &$this, 'scripts_admin' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'scripts_front' ) );
		add_action( 'plugins_loaded', array( &$this, 'update_db_check' ) );

		new Forms_Controller();
		new Settings_Controller();
		new Widgets_Controller();
	}

	public function scripts_admin()
	{
		wp_enqueue_script(
			'admin-script-' . App::SLUG,
			App::plugins_url( '/assets/javascripts/admin/built.js' ),
			array( 'jquery' ),
			App::filemtime( 'assets/javascripts/admin/built.js' ),
			true
		);
	}

	public function scripts_front()
	{
		wp_enqueue_script(
			'front-script-' . App::SLUG,
			App::plugins_url( '/assets/javascripts/front/built.js' ),
			array( 'jquery' ),
			App::filemtime( 'assets/javascripts/front/built.js' ),
			true
		);
	}

	public function update_db_check()
	{
		if ( get_site_option( 'followize_db_version' ) != App::VERSION ) {
			$this->update_db_version();
		}
	}

	public function update_db_version()
	{
		Forms_Controller::create_tables();
		update_site_option( 'followize_db_version', App::VERSION );
	}

	public function activate()
	{
		$this->update_db_check();
	}
}