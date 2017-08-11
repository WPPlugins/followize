<?php
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

App::uses( 'form', 'Widget' );

class Widgets_Controller
{
	public function __construct()
	{
		add_action( 'widgets_init', array( &$this, 'register_widgets' ) );
	}

	public function register_widgets()
	{
		$available_widgets = array(
	 		'Followize\Form_Widget'
		);

		array_map( 'register_widget', $available_widgets );
	}
}