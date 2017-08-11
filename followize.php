<?php
/*
	Plugin Name: Followize
	Plugin URI:
	Version: 0.7.1
	Author: Followize
	Author URI: http://www.followize.com.br
	License: GPL2
	Description: Plugin oficial para criação de formulários de integração com o Followize. Desenvolvido para grandes, médias e pequenas empresas que recebem leads através da internet, o Followize é capaz de organizar, padronizar o processo de atendimento e analisar o desempenho da equipe comercial e ações de marketing de uma maneira objetiva, possibilitando mais produtividade e, é claro, mais lucros.
*/
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

App::uses( 'core', 'Config' );

class App
{
	const SLUG    = 'followize';
	const FILE    = 'followize/followize.php';
	const VERSION = '0.7.1';
	const API     = 'http://www.followize.com.br/api/v2/Leads/';

	public static function uses( $class_name, $location )
	{
		$locations = array(
			'Controller',
			'View',
			'Helper',
			'Widget',
			'Vendor',
		);

		$extension = 'php';

		if ( in_array( $location, $locations ) ) {
			$extension = strtolower( $location ) . '.php';
		}

		include "{$location}/{$class_name}.{$extension}";
	}

	public static function plugins_url( $path )
	{
		return plugins_url( $path, __FILE__ );
	}

	public static function plugin_dir_path( $path )
	{
		return plugin_dir_path( __FILE__ ) . $path;
	}

	public static function filemtime( $path )
	{
		return filemtime( self::plugin_dir_path( $path ) );
	}
}

register_activation_hook( __FILE__, array( new Core(), 'activate' ) );