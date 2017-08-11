<?php
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

App::uses( 'list-forms', 'Controller' );
App::uses( 'list-fields', 'Controller' );

class Forms_View
{
	public static function render_page()
	{
		$forms = new ListForms_Controller();
		$forms->prepare_items();
		?>
		<div class="wrap">
			<h2>
				Formulários
				<a href="<?php echo Form::get_url_new(); ?>" class="page-title-action">Adicionar Novo</a>
			</h2>
			<form method="get">
				<p class="search-box">
					<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
					<?php $forms->search_box( 'Pesquisar formulários', 'search_id' ); ?>
				</p>
				<?php $forms->display(); ?>
			</form>
		</div>
		<?php
	}

	public static function render_page_new()
	{
		$form_id = Utils::get( 'form', 0, 'intval' );
		$model   = new Form( $form_id );
		$fields  = new ListFields_Controller();
		$fields->prepare_items();
		?>
		<div class="wrap">
			<h1><?php echo ( $form_id ) ? 'Editar' : 'Adicionar novo'; ?> formulário</h1>
			<form method="post" action="admin-post.php">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><label for="title-id">Título</label></th>
							<td><?php self::render_field_title( $model ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="hide-title-id"></label></th>
							<td><?php self::render_field_hide_title( $model ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="conversion-goal-id">Ponto de conversão</label></th>
							<td><?php self::render_field_conversion_goal( $model ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="team-key-id">Chave de equipe</label></th>
							<td><?php self::render_field_team_key( $model ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="custom-css-id">CSS Customizado</label></th>
							<td><?php self::render_field_custom_css( $model ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="button-title-id">Texto do botão</label></th>
							<td><?php self::render_field_button_title( $model ); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="redirect-url-id">URL de redirecionamento</label></th>
							<td><?php self::render_field_redirect_url( $model ); ?></td>
						</tr>
						<?php if ( $form_id ) : ?>
						<tr>
							<th scope="row"><label for="shortcode-id">Shortcode</label></th>
							<td><?php self::render_field_shortcode( $model ); ?></td>
						</tr>
						<?php endif; ?>
						<tr>
							<th scope="row"><label for="label-placeholder">Rótulo</label></th>
							<td><?php self::render_field_label_placeholder( $model ); ?></td>
						</tr>
					</tbody>
				</table>
				<input type="hidden" name="action" value="fwz_save_form">
				<input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
				<?php
					$button_title = ( $form_id ) ? 'Salvar alterações' : 'Salvar';

					printf( '<button id="add-custom-field" class="button" style="margin-right:10px;">Adicionar campo personalizado</button>' );

					submit_button( $button_title, 'primary', 'submit', false );
					$fields->display();
					submit_button( $button_title );
				?>
			</form>
		</div>
		<?php
	}

	public static function render_field_team_key( $model )
	{
		$setting   = new Setting();
		$team_keys = $setting->get_team_keys();

		if ( ! $team_keys ) {
			self::_not_found_team_keys( $setting );
			return;
		}
		?>
		<select name="form[team_key]"
				id="team-key-id"
				class="widefat">
				<?php echo self::_render_options( $team_keys, $model->team_key ); ?>
		</select>
		<p class="description">
			Selecione a chave de equipe a ser usada pelo formulário.
		</p>
		<?php
	}

	public static function render_field_title( $model )
	{
		?>
		<input type="text"
			   id="title-id"
			   class="widefat"
			   name="form[title]"
			   value="<?php echo esc_attr( $model->title ); ?>">
		<?php
	}

	public static function render_field_hide_title( $model )
	{
		?>
		<label>
			<input type="hidden" name="form[hide_title]" value="0">
			<input type="checkbox"
				   id="hide-title-id"
				   name="form[hide_title]"
				   value="1"
				   <?php checked( 1, $model->hide_title ); ?>> Não exibir título do formulário
		</label>
		<?php
	}

	public static function render_field_button_title( $model )
	{
		$title = ( $model->button_text ) ? $model->button_text : 'Enviar';
		?>
		<input type="text"
			   id="button-title-id"
			   class="widefat"
			   name="form[button_text]"
			   value="<?php echo esc_attr( $title ); ?>">
		<?php
	}

	public static function render_field_redirect_url( $model )
	{
		$title = ( $model->button_text ) ? $model->button_text : 'Enviar';
		?>
		<input type="text"
			   id="redirect-url-id"
			   class="widefat"
			   name="form[redirect_url]"
			   value="<?php echo esc_url( $model->redirect_url ); ?>">
		<p class="description">
			URL para qual o usuário será redirecionado em caso de sucesso no envio do formulário.
		</p>
		<?php
	}

	public static function render_field_custom_css( $model )
	{
		?>
		<textarea id="custom-css-id" class="large-text" name="form[custom_css]" rows="8" cols="40"><?php echo stripslashes( wp_filter_nohtml_kses( $model->custom_css ) ); ?></textarea>
		<p class="description">
			Customize o estilo do seu formulário de acordo com suas necessidades. Obs.: Não há necessidade de informar a tag style
		</p>
		<p class="description">
			Ex.: <code>.followize-form-body {background:red;}</code>
		</p>
		<?php
	}

	public static function render_field_conversion_goal( $model )
	{
		?>
		<input type="text"
			   id="conversion-goal-id"
			   class="widefat"
			   name="form[conversion_goal]"
			   value="<?php echo esc_attr( $model->conversion_goal ) ?>">
		<p class="description">
			Identificador do ponto de conversão. Ex.: Página de produtos
		</p>
		<?php
	}

	public static function render_field_shortcode( $model )
	{
		$shortcode = '';

		if ( $model->id ) {
			$shortcode = '[followize id="'.intval( $model->id ).'"]';
		}
		?>
		<input type="text"
			   id="shortcode-id"
			   class="widefat"
			   value='<?php echo $shortcode; ?>'
			   onclick="this.select();"
			   readonly>
		<p class="description">
			Copie e cole o <strong>shortcode</strong> no local desejado para exibição do formulário.
		</p>
		<?php
	}

	public static function render_field_label_placeholder( $model )
	{
		?>
		<label>
			<input type="hidden" name="form[label_placeholder]" value="0">
			<input type="checkbox"
				   id="hide-title-id"
				   name="form[label_placeholder]"
				   value="1"
				   <?php checked( 1, $model->label_placeholder ); ?>> Exibir como placeholder
		</label>
		<?php
	}

	private static function _not_found_team_keys( $setting )
	{
		$config_url = $setting->get_url_config_page();
		?>
		<p class="description">
			Você ainda não configurou nenhuma chave de equipe,
			<a href="<?php echo $config_url; ?>">clique aqui</a>
			para configurar!
		</p>
		<?php
	}

	private static function _render_options( $list, $current )
	{
		foreach ( $list as $item ) {
			$item = trim( $item );

			printf(
				'<option value="%1$s" %2$s>%1$s</option>',
				$item,
				selected( $item, $current, false )
			);
		}
	}
}
