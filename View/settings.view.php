<?php
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

class Settings_View
{
	public static function render_page_config()
	{
		?>
		<div class="wrap">
			<h2>Followize</h2>
			<?php settings_errors() ?>
			<form action="options.php" method="POST">

				<?php
				settings_fields( 'followize-settings-config-group' );
				do_settings_sections( 'followize-config' );
				submit_button();
				?>

			</form>
		</div>
		<?php
	}

	public static function render_section_config()
	{
		?>
		<p class="description">
			As chaves necessárias para a integração podem ser geradas e encontradas através desta URL:
			<a href="http://www.followize.com.br/app/integracao/chaves" target="_blank">http://www.followize.com.br/app/integracao/chaves</a>
		</p>
		<?php
	}

	public static function render_section_advanced()
	{

	}

	public static function render_field_client_key( $args )
	{
		$model = $args['model'];
		?>
		<input type="text"
			   id="<?php echo $args['label_for']; ?>"
			   class="large-text"
			   name="<?php echo Setting::OPTION_CLIENT_KEY; ?>"
			   value="<?php echo esc_attr( $model->client_key ); ?>">
		<?php
	}

	public static function render_field_team_keys( $args )
	{
		$model = $args['model'];
		?>
		<textarea name="<?php echo Setting::OPTION_TEAM_KEYS; ?>"
				  id="<?php echo $args['label_for']; ?>"
				  class="large-text"
				  rows="8"
				  cols="40"><?php echo esc_html( $model->team_keys ); ?></textarea>

		<p class="description">
			Insira uma chave de equipe por linha. Ex.: Vendas: e2239f9622d2dda659a773d356d77b3a
		</p>
		<?php
	}

	public static function render_field_disable_css( $args )
	{
		$model = $args['model'];
		?>
		<input type="radio"
			   id="<?php echo $args['label_for']; ?>"
			   name="<?php echo Setting::OPTION_DISABLE_CSS; ?>"
			   value="0"
			   <?php checked( 0, intval( $model->disable_css ) ); ?>> Sim

	   <input type="radio"
			  id="<?php echo $args['label_for']; ?>"
			  name="<?php echo Setting::OPTION_DISABLE_CSS; ?>"
			  value="1"
			  <?php checked( 1, intval( $model->disable_css ) ); ?>> Não

		<p class="description">
			Marque se você deseja incluir ou não a estilização padrão do plugin para o formulário.
		</p>
		<?php
	}

	public static function render_field_custom_css( $args )
	{
		$model = $args['model'];
		?>
		<textarea class="large-text" name="<?php echo Setting::OPTION_CUSTOM_CSS; ?>" rows="8" cols="40"><?php echo stripslashes( wp_filter_nohtml_kses( $model->custom_css ) ); ?></textarea>
		<p class="description">
			Customize o estilo do seu formulário de acordo com suas necessidades. Obs.: Não há necessidade de informar a tag style
		</p>
		<p class="description">
			Ex.: <code>.followize-form-body {background:red;}</code>
		</p>
		<?php
	}

	public static function render_field_emails( $args )
	{
		$model = $args['model'];
		?>
		<input type="text"
			   id="<?php echo $args['label_for']; ?>"
			   class="large-text"
			   name="<?php echo Setting::OPTION_EMAILS; ?>"
			   value="<?php echo esc_attr( $model->emails ); ?>">
		<p class="description">
			Destinatário para onde será enviado todos os registros capturados nos formulários. além do envio padrão para a Followize. Para mais de um e-mail, separe com vírgula.
		</p>
		<?php
	}
}
