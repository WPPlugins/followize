<?php
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

use WP_Widget;

class Form_Widget extends WP_Widget
{
	private $setting;

	public function __construct()
	{
		parent::__construct(
			'followize-form',
			'Followize | Formulário',
			array(
				'classname'   => 'widget_followize_form',
				'description' => 'Widget que exibe o formulário do Followize.',
			)
		);

		$this->setting = new Setting();
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'];

		echo do_shortcode( "[followize id=\"{$instance['form']}\"]" );

		echo $args['after_widget'];
	}

	public function update( $new_instance, $old_instance )
	{
		$instance['form'] = esc_html( $new_instance['form'] );

		return $instance;
	}

	public function form( $instance )
	{
		$forms = Form::get_all();
		?>
		<p>
			<label>Formulário:
				<select class="widefat"
					    name="<?php echo $this->get_field_name( 'form' ); ?>">
						<?php
							echo self::_render_options(
								$forms,
								$this->_get_property( $instance, 'form' )
							);
						?>
				</select>
			</label>
		</p>
		<?php
	}

	private static function _render_options( $list, $current )
	{
		foreach ( $list as $item ) {
			printf(
				'<option value="%1$s" %3$s>%2$s</option>',
				$item['id'],
				$item['title'],
				selected( $item['id'], $current, false )
			);
		}
	}

	private function _get_property( $instance, $property, $default = '' )
	{
		return ( isset( $instance[ $property ] ) && ! empty( $instance[ $property ] ) ) ? $instance[ $property ] : $default;
	}
}
