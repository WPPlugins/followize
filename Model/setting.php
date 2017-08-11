<?php
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

class Setting
{
	const OPTION_CLIENT_KEY  = 'followize_client_key';
	const OPTION_TEAM_KEYS   = 'followize_team_keys';
	const OPTION_DISABLE_CSS = 'followize_disable_css';
	const OPTION_CUSTOM_CSS  = 'followize_custom_css';
	const OPTION_EMAILS      = 'followize_emails';

	private $client_key;
	private $team_keys;
	private $disable_css;
	private $custom_css;
	private $emails;

	public function __get( $prop_name )
	{
		return $this->_get_property( $prop_name );
	}

	public function __set( $prop_name, $value )
	{
		return $this->_set_property( $prop_name, $value );
	}

	public function get_url_config_page()
	{
		return admin_url( 'admin.php?page=followize-config' );
	}

	public function get_team_keys()
	{
		return preg_split( '/\n/', $this->__get( 'team_keys' ), false, PREG_SPLIT_NO_EMPTY );
	}

	private function _get_property( $prop_name )
	{
		switch ( $prop_name ) {

			case 'client_key' :
				if ( ! isset( $this->client_key ) ) {
					$this->client_key = get_option( self::OPTION_CLIENT_KEY );
				}
				break;

			case 'team_keys' :
				if ( ! isset( $this->team_keys ) ) {
					$this->team_keys = get_option( self::OPTION_TEAM_KEYS );
				}
				break;

			case 'disable_css' :
				if ( ! isset( $this->disable_css ) ) {
					$this->disable_css = get_option( self::OPTION_DISABLE_CSS );
				}
				break;

			case 'custom_css' :
				if ( ! isset( $this->custom_css ) ) {
					$this->custom_css = get_option( self::OPTION_CUSTOM_CSS );
				}
				break;

			case 'emails' :
				if ( ! isset( $this->emails ) ) {
					$this->emails = get_option( self::OPTION_EMAILS );
				}
				break;

		}

		return $this->$prop_name;
	}

	private function _set_property( $prop_name, $value )
	{
		switch ( $prop_name ) {

			case 'client_key':
				update_option( self::OPTION_CLIENT_KEY, $value );
				break;

			case 'team_keys':
				update_option( self::OPTION_TEAM_KEYS, $value );
				break;

			case 'disable_css':
				update_option( self::OPTION_DISABLE_CSS, $value );
				break;

			case 'custom_css':
				update_option( self::OPTION_CUSTOM_CSS, $value );
				break;

			case 'emails':
				update_option( self::OPTION_EMAILS, $value );
				break;

		}
	}
}
