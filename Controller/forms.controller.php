<?php
namespace Followize;

if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

App::uses( 'form', 'Model' );
App::uses( 'forms', 'View' );

class Forms_Controller
{
	public function __construct()
	{
		add_action( 'admin_menu', array( &$this, 'add_menu' ) );
		add_action( 'admin_post_fwz_save_form', array( &$this, 'save' ) );
		add_action( 'admin_notices', array( &$this, 'show_message_updated' ) );
		add_action( 'admin_head', array( &$this, 'remove_submenu' ) );
		add_shortcode( 'followize', array( &$this, 'show_form' ) );
		add_filter( 'followize_field_message', array( &$this, 'message_field' ), 10, 4 );
		add_filter( 'followize_field_emailOptIn', array( &$this, 'emailOptIn_field' ), 10, 4 );
		add_filter( 'followize_field_name', array( &$this, 'name_field' ), 10, 4 );
		add_action( 'wp_head', array( &$this, 'default_style' ) );
		add_action( 'wp_head', array( &$this, 'custom_style' ) );
		add_action( 'wp_footer', array( &$this, 'enqueue_track_tag' ) );
		add_action( 'admin_head', array( &$this, 'fields_style' ) );
		add_action( 'init', array( &$this, 'get_lead' ) );
		add_filter( 'followize_field_message_value', array( &$this, 'concat_other_fields' ), 10, 2 );
		add_action( 'followize_success', array( &$this, 'maybe_redirect' ) );
	}

	public function maybe_redirect( $form_id )
	{
		$model = new Form( $form_id );

		if ( ! $model->redirect_url ) {
			return;
		}

		wp_redirect( esc_url( $model->redirect_url ) );
		exit;
	}

	public function get_lead()
	{
		$nonce = Utils::post( 'nonce', '' );

		if ( ! wp_verify_nonce( $nonce, 'followize_form' ) ) {
			return;
		}

		$form_id = Utils::post( 'formId', 0 );

		if ( $form_id <= 0 ) {
			return;
		}

		$data = $this->get_lead_data( $form_id );

		$this->send_mail_lead( $data, $form_id );

		$data = $this->get_form_data( $form_id, $data );

		$this->send_lead( $data, $form_id );
	}

	public function send_lead( $data, $form_id )
	{
		if ( ! is_array( $data ) ) {
			return;
		}

		$data = json_encode( Utils::to_string_value( $data ) );
		$curl = curl_init();

		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_URL, App::API );
		curl_setopt( $curl, CURLOPT_HEADER, false );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );

		$result = json_decode( curl_exec( $curl ), true );

		curl_close( $curl );

		$this->handle_errors( $result, $form_id );
	}

	public function send_mail_lead( $data, $form_id )
	{
		$setting = new Setting();
		$form    = new Form( $form_id );

		if ( ! $emails = $setting->emails ) {
			return;
		}

		$subject = sprintf( 'Novo lead recebido em: %s', get_bloginfo( 'name' ) );
		$message = $this->get_mail_body( $this->format_data_mail( $data, $form_id ), $form->title );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $emails, $subject, $message, $headers );
	}

	public function format_data_mail( $data, $form_id )
	{
		$new_data = array();

		foreach ( $data as $key => $value ) {
			$model = new Form_Field( $form_id, $key );

			if ( $model->label ) {
				$new_data[ $model->field_order ] = array(
					'name'  => $model->label,
					'value' => Utils::implode( $value ),
				);
			}
		}

		ksort( $new_data );
		return $new_data;
	}

	public function get_mail_body( $data, $form_title )
	{
		ob_start();
		?>
		<h2><?php echo esc_html( $form_title ); ?></h2>

		<ul>
			<?php
				foreach ( $data as $value ) {
					printf(
						"<li><b>%s:</b> %s</li>\r\n",
						esc_html( $value['name'] ),
						esc_html( $value['value'] )
					);
				}
			?>
		</ul>
		<?php
		return ob_get_clean();
	}

	public function handle_errors( $result, $form_id )
	{
		$error   = false;
		$default = array(
			'success' => 0,
			'error'   => 0
		);

		$result = wp_parse_args( $result, $default );

		$_POST['followize_form_id'] = $form_id;

		if ( intval( $result['error'] ) == 0 && intval( $result['success'] == 0 ) ) {
			$error = 'Falha no envio do formulário, tente novamente!';
			return;
		}

		if ( intval( $result['success'] > 0 ) ) {
			$_POST['followize_success'] = true;
			do_action( 'followize_success', $form_id );
			return;
		}

		switch ( intval( $result['error'] ) ) {

			case 4000 :
				$error = 'Um ou mais campos obrigatórios não enviados.';
				break;

			case 4001 :
				$error = 'Chave de cliente inválida.';
				break;

			case 4002 :
				$error = 'Chave de equipe inválida.';
				break;

			case 4003 :
				$error = 'Falha ao cadastrar o contato.';
				break;

			case 4004 :
				$error = 'Nenhum atendente encontrado na equipe enviada.';
				break;

			case 4005 :
				$error = 'Falha ao cadastrar a conversão.';
				break;

			default :
				$error = 'Falha no envio do formulário, tente novamente!';
				break;
		}

		$_POST['followize_error'] = apply_filters( 'followize_form_error', $error, $form_id, $result );
	}

	public function get_lead_data( $form_id )
	{
		$post_data            = Utils::to_string_value( $_POST );
		$fields               = Field::get_fields_name();
		$post_data['name']    = Utils::post( 'clientName', '' );
		$post_data['message'] = ( isset( $post_data['message'] ) ) ? $post_data['message']: '';
		$valid_fields         = array_intersect( $fields, array_keys( $post_data ) );
		$valid_fields[]       = 'hubUtmz';
		$data                 = array();

		if ( ! $valid_fields ) {
			return $data;
		}

		foreach ( $valid_fields as $field_name ) {
			$data[ $field_name ] = Utils::implode( apply_filters( "followize_field_{$field_name}_value", $post_data[ $field_name ], $form_id ) );
			$data[ $field_name ] = Utils::implode( apply_filters( "followize_{$form_id}_field_{$field_name}_value", $data[ $field_name ] ) );
		}

		return apply_filters( 'followize_lead_data', $data );
	}

	public function concat_other_fields( $value, $form_id )
	{
		$post_data      = Utils::to_string_value( $_POST );
		$valid_fields   = Field::get_fields_name();
		$valid_fields[] = 'clientName';
		$valid_fields[] = 'conversionGoal';
		$valid_fields[] = 'hubUtmz';
		$valid_fields[] = 'nonce';
		$valid_fields[] = '_wp_http_referer';
		$valid_fields[] = 'formId';

		$invalid_fields = array_diff_key( $post_data, array_flip( $valid_fields ) );

		foreach ( $invalid_fields as $field_key => $field_value ) {
			$field = new Form_Field( $form_id, $field_key );

			if ( $field->label ) {
				$value .= "\n {$field->label}: {$field_value}";
			} else {
				$value .= "\n {$field_key}: {$field_value}";
			}
		}

		return $value;
	}

	public function get_form_data( $form_id, $data )
	{
		if ( ! is_array( $data ) ) {
			return false;
		}

		$setting  = new Setting();
		$form     = new Form( $form_id );
		$team_key = $form->get_valid_team_key();

		if ( ! $team_key ) {
			return $data;
		}

		$data['clientKey']      = $setting->client_key;
		$data['teamKey']        = $team_key;
		$data['conversionGoal'] = ( ! empty( $form->conversion_goal ) ) ? $form->conversion_goal : get_bloginfo( 'name' );

		return $data;
	}

	public function add_menu()
	{
		add_menu_page(
			'Followize',
			'Followize',
			'manage_options',
			'followize-page',
			array( 'Followize\Forms_View', 'render_page' ),
			'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+PHN2ZyAgIHhtbG5zOm9zYj0iaHR0cDovL3d3dy5vcGVuc3dhdGNoYm9vay5vcmcvdXJpLzIwMDkvb3NiIiAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIgICB4bWxuczpjYz0iaHR0cDovL2NyZWF0aXZlY29tbW9ucy5vcmcvbnMjIiAgIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyIgICB4bWxuczpzdmc9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiAgIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgICB4bWxuczpzb2RpcG9kaT0iaHR0cDovL3NvZGlwb2RpLnNvdXJjZWZvcmdlLm5ldC9EVEQvc29kaXBvZGktMC5kdGQiICAgeG1sbnM6aW5rc2NhcGU9Imh0dHA6Ly93d3cuaW5rc2NhcGUub3JnL25hbWVzcGFjZXMvaW5rc2NhcGUiICAgaWQ9InN2ZzMwNzEiICAgdmVyc2lvbj0iMS4xIiAgIGlua3NjYXBlOnZlcnNpb249IjAuNDguNCByOTkzOSIgICB3aWR0aD0iMzIiICAgaGVpZ2h0PSIzMiIgICBzb2RpcG9kaTpkb2NuYW1lPSJmb2xsb3dpc2UtaWNvbi5zdmciPiAgPG1ldGFkYXRhICAgICBpZD0ibWV0YWRhdGEzMDc3Ij4gICAgPHJkZjpSREY+ICAgICAgPGNjOldvcmsgICAgICAgICByZGY6YWJvdXQ9IiI+ICAgICAgICA8ZGM6Zm9ybWF0PmltYWdlL3N2Zyt4bWw8L2RjOmZvcm1hdD4gICAgICAgIDxkYzp0eXBlICAgICAgICAgICByZGY6cmVzb3VyY2U9Imh0dHA6Ly9wdXJsLm9yZy9kYy9kY21pdHlwZS9TdGlsbEltYWdlIiAvPiAgICAgICAgPGRjOnRpdGxlPjwvZGM6dGl0bGU+ICAgICAgPC9jYzpXb3JrPiAgICA8L3JkZjpSREY+ICA8L21ldGFkYXRhPiAgPGRlZnMgICAgIGlkPSJkZWZzMzA3NSI+ICAgIDxsaW5lYXJHcmFkaWVudCAgICAgICBpZD0ibGluZWFyR3JhZGllbnQ1NDc5IiAgICAgICBvc2I6cGFpbnQ9InNvbGlkIj4gICAgICA8c3RvcCAgICAgICAgIHN0eWxlPSJzdG9wLWNvbG9yOiNhMGE1YWE7c3RvcC1vcGFjaXR5OjE7IiAgICAgICAgIG9mZnNldD0iMCIgICAgICAgICBpZD0ic3RvcDU0ODEiIC8+ICAgIDwvbGluZWFyR3JhZGllbnQ+ICA8L2RlZnM+ICA8c29kaXBvZGk6bmFtZWR2aWV3ICAgICBwYWdlY29sb3I9IiNmZmZmZmYiICAgICBib3JkZXJjb2xvcj0iIzY2NjY2NiIgICAgIGJvcmRlcm9wYWNpdHk9IjEiICAgICBvYmplY3R0b2xlcmFuY2U9IjEwIiAgICAgZ3JpZHRvbGVyYW5jZT0iMTAiICAgICBndWlkZXRvbGVyYW5jZT0iMTAiICAgICBpbmtzY2FwZTpwYWdlb3BhY2l0eT0iMCIgICAgIGlua3NjYXBlOnBhZ2VzaGFkb3c9IjIiICAgICBpbmtzY2FwZTp3aW5kb3ctd2lkdGg9IjEzNjYiICAgICBpbmtzY2FwZTp3aW5kb3ctaGVpZ2h0PSI3MTUiICAgICBpZD0ibmFtZWR2aWV3MzA3MyIgICAgIHNob3dncmlkPSJmYWxzZSIgICAgIGlua3NjYXBlOnpvb209IjYuNTE4NjQwNiIgICAgIGlua3NjYXBlOmN4PSIyOS4zNDMxNTUiICAgICBpbmtzY2FwZTpjeT0iMTYuMTg2ODQ4IiAgICAgaW5rc2NhcGU6d2luZG93LXg9IjAiICAgICBpbmtzY2FwZTp3aW5kb3cteT0iMzAiICAgICBpbmtzY2FwZTp3aW5kb3ctbWF4aW1pemVkPSIxIiAgICAgaW5rc2NhcGU6Y3VycmVudC1sYXllcj0ic3ZnMzA3MSIgLz4gIDxnICAgICBpZD0iZzMxOTAiICAgICB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxLjczNTU5MjQsLTM4Mi4xODk4MykiIC8+ICA8YSAgICAgaWQ9ImE0MDEyIiAgICAgc3R5bGU9ImZpbGw6IzI0YWY2ODtmaWxsLW9wYWNpdHk6MTtzdHJva2U6bm9uZSIgICAgIGlua3NjYXBlOmV4cG9ydC1maWxlbmFtZT0iL2hvbWUvZWx2aXMvRG93bmxvYWRzL2ZvbGxvd2lzZS1pY29uLnBuZyIgICAgIGlua3NjYXBlOmV4cG9ydC14ZHBpPSI3IiAgICAgaW5rc2NhcGU6ZXhwb3J0LXlkcGk9IjciICAgICB0cmFuc2Zvcm09Im1hdHJpeCgwLjEzNjg2NTQ4LDAsMCwwLjEzNjg2NTQ4LC00Mi43NTQwMjQsLTYuMTY2Nzk1MykiPiAgICA8cGF0aCAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIiAgICAgICBpZD0icGF0aDM5ODQiICAgICAgIGQ9Im0gMzc3LjYxNzkzLDIxOS4wNzEyOCBjIC00LjYxNDgzLC0xMC43Mjc0NSAtNS41MjI0OCwtMjMuMDU5NDUgLTIuMjY2MDMsLTMwLjc4Nzc2IDIuMDAwNSwtNC43NDc2NiAxMC4zOTg5OSwtMTIuODQ1MzggMTYuNTI4MjIsLTE1LjkzNjMzIDEwLjkzNDc4LC01LjUxNDM2IDIwLjI5OTY1LC03LjM4MTY1IDUzLjE1NTEzLC0xMC41OTg3MyAxMC4xNzIxNCwtMC45OTYwMiAyMi4wMTQzNiwtMi41MzI4NCAyNi4zMTYwNSwtMy40MTUxNiA3LjIwMDQ3LC0xLjQ3Njg4IDcuODY3NzIsLTEuNDkxNTMgOC40MDY4OSwtMC4xODQ0OSAwLjMyMjExLDAuNzgwODUgMC41Nzg3Niw2LjEwNTgyIDAuNTcwMzMsMTEuODMzMjggLTAuMDE0Niw5Ljg4MzgzIC0wLjE0NTMxLDEwLjY0OTY2IC0yLjU3MDgxLDE1LjA1NDg0IC0yLjk2MjE5LDUuMzc5OTIgLTkuMjAzMSwxMS4zMTA5NSAtMTQuNTI2NzgsMTMuODA1NDkgLTcuMjY0NTQsMy40MDM5NiAtMTIuMDIzMzksNC41MDUxMyAtMzAuNzQ5OTEsNy4xMTUzIC0yNy4zMTI5NiwzLjgwNjk5IC0zOS43Mjk4Miw3LjU4MTcgLTQ5LjAyMTM4LDE0LjkwMjQyIC0xLjczOTE5LDEuMzcwMyAtMy4zNTA3NywyLjQ5MTQ0IC0zLjU4MTI3LDIuNDkxNDQgLTAuMjMwNTEsMCAtMS4yNDc3MSwtMS45MjYxMyAtMi4yNjA0NCwtNC4yODAzIHoiICAgICAgIHN0eWxlPSJmaWxsOiMyNGFmNjg7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlOm5vbmUiIC8+ICA8L2E+ICA8cGF0aCAgICAgc3R5bGU9ImZpbGw6IzI0YWY2ODtmaWxsLW9wYWNpdHk6MTtzdHJva2U6bm9uZSIgICAgIGQ9Im0gMTEuNDc5NjksMzAuOTQwMjkxIGMgLTAuNTUzNjA5LC0xLjIzMzYwMSAtMC43MDI4MjEsLTIuNzM5NDg1IC0wLjM2NjAyNiwtMy42OTQwNTMgMC42MDk0OTYsLTEuNzI3NTAyIDIuNDA0NTgxLC0yLjYxMjI0OSA2LjM2NTIzMSwtMy4xMzcyNTEgbCAwLjU0Nzc2NSwtMC4wNzI2MSAwLjEzNTk4NCwwLjY5MTIxNiBjIDAuMjY5ODU2LDEuMzcxNjQyIC0wLjI3NDMwMiwyLjk1MjgxNiAtMS40MDE3MDksNC4wNzMwMDUgLTAuNTYwNzE2LDAuNTU3MTI1IC0wLjkyMDE5NSwwLjc3MjQ2NCAtMi4zNjUzNDksMS40MTY5MTEgLTAuOTM3Mjc1LDAuNDE3OTY4IC0xLjkwNzA2MiwwLjkwNjk0MSAtMi4xNTUwODYsMS4wODY2MDQgbCAtMC40NTA5NDEsMC4zMjY2NjIgLTAuMzA5ODY5LC0wLjY5MDQ4NCB6IiAgICAgaWQ9InBhdGgzOTg2IiAgICAgaW5rc2NhcGU6Y29ubmVjdG9yLWN1cnZhdHVyZT0iMCIgICAgIGlua3NjYXBlOmV4cG9ydC1maWxlbmFtZT0iL2hvbWUvZWx2aXMvRG93bmxvYWRzL2ZvbGxvd2lzZS1pY29uLnBuZyIgICAgIGlua3NjYXBlOmV4cG9ydC14ZHBpPSI3IiAgICAgaW5rc2NhcGU6ZXhwb3J0LXlkcGk9IjciIC8+ICA8YSAgICAgaWQ9ImE1NjMyIiAgICAgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMC40NjAyMTg2NSwwKSI+ICAgIDxwYXRoICAgICAgIGlua3NjYXBlOmV4cG9ydC15ZHBpPSI3IiAgICAgICBpbmtzY2FwZTpleHBvcnQteGRwaT0iNyIgICAgICAgaW5rc2NhcGU6ZXhwb3J0LWZpbGVuYW1lPSIvaG9tZS9lbHZpcy9Eb3dubG9hZHMvZm9sbG93aXNlLWljb24ucG5nIiAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIiAgICAgICBpZD0icGF0aDQwMjUiICAgICAgIGQ9Ik0gNi4yMTIwMDIsMTcuMjU0ODY3IEMgNS45MTk1NDIsMTYuNzAyNzg5IDUuMzQwNDY5LDE0LjgzMDIyNiA1LjE4MzA0OCwxMy45Mjc1MTQgNC44NzU4MjQsMTIuMTY1Nzc4IDUuMDMxMjA3LDEwLjc2MTQ5OCA1LjY3OTk1Niw5LjQzNjU3MTUgNi4zNzMxNzUsOC4wMjA4NDA5IDguMzU0MDI0LDYuNDE4NTc3IDEwLjI2NjYzNSw1LjcyNjUxNTkgMTAuODEyNjQ1LDUuNTI4OTQzNSAxMi44NTg5ODUsNS4wMDk4NDUgMTQuODE0MDU4LDQuNTcyOTU3MSAxNi43NjkxMyw0LjEzNjA3MyAxOC45MTg4MzMsMy41OTg2MTU3IDE5LjU5MTE3OSwzLjM3ODYxNjYgYyAxLjMzOTE4OSwtMC40MzgyMDMgMy42MTA2MDEsLTEuNDg3MjE5IDUuMjQzMjI5LC0yLjQyMTUwMjIzIDAuNTc0ODE2LC0wLjMyODkzNjE1IDEuMDcyMjc0LC0wLjU5ODA2OTE1IDEuMTA1NDczLC0wLjU5ODA2OTE1IDAuMjA0MzM5LDAgMS4yMDg1NCwyLjg5MjQ2NzI4IDEuNDU0NjgsNC4xOTAwMzc2OCAwLjY4ODYwOCwzLjYzMDA0NTkgLTAuNzg4MTIzLDYuNTM5MDUzMSAtNC4wNTQ3NTksNy45ODc0NTIxIC0xLjEzNDUyNCwwLjUwMzAzNSAtMi43ODk5MTgsMC44NzM3NTkgLTUuMjg0Mjk3LDEuMTgzNDExIC0zLjEyNjkzMSwwLjM4ODE3OSAtMy43MjIwODIsMC40ODMwMjYgLTUuMzI4NDE0LDAuODQ5MTQzIC0yLjE5NTc2NywwLjUwMDQ2MSAtMy45MjcxNTIsMS4yMjMzOCAtNS41NDgwNzIsMi4zMTY1MjIgLTAuNzY4MTI5LDAuNTE4MDI3IC0wLjg2ODAwMywwLjU1NjE2MiAtMC45NjcwMTcsMC4zNjkyNTYgbCAwLDAgeiIgICAgICAgc3R5bGU9ImZpbGw6IzI0YWY2ODtmaWxsLW9wYWNpdHk6MTtzdHJva2U6bm9uZSIgLz4gIDwvYT48L3N2Zz4='
		);

		add_submenu_page(
			'followize-page',
			'Formulários',
			'Formulários',
			'manage_options',
			'followize-page'
		);

		add_submenu_page(
			'followize-page',
			'Adicionando/Editando Formulário',
			'Adicionando/Editando Formulário',
			'manage_options',
			'followize-page-form',
			array( 'Followize\Forms_View', 'render_page_new' )
		);
	}

	public function remove_submenu()
	{
		remove_submenu_page( 'followize-page', 'followize-page-form' );
	}

	public function show_message_updated()
	{
		$message = Utils::get( 'message', false, 'intval' );
		$page    = Utils::get( 'page', false );
		$form_id = Utils::get( 'form', false );

		if ( ! $page == 'followize-page' || ! $page == 'followize-page-form' ) {
			return;
		}

		if ( ! $message ) {
			return;
		}

		switch ( $message ) {

			case 1:
				$text = 'Formulário salvo com sucesso';
				break;

			case 2:
				$text = 'Formulário excluído';
				break;

			case 3:
				$text = 'Formulário duplicado com sucesso';
				break;

			default:
				$text = 'Ação realizada com sucesso';

		}

		?>
		<div id="message" class="updated notice notice-success is-dismissible">
			<p><?php echo $text; ?></p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">Dispensar este aviso.</span>
			</button>
		</div>
		<?php
	}

	public function save()
	{
		$nonce   = Utils::post( '_wpnonce', false );
		$form    = Utils::post( 'form', false );
		$fields  = Utils::post( 'fields', false );
		$form_id = Utils::post( 'form_id', 0, 'intval' );

		if ( ! wp_verify_nonce( $nonce, 'bulk-campos' ) ) {
			return;
		}

		if ( $form_id === 0 ) {
			$form_id = $this->_insert_form( $form );
			$this->_insert_form_fields( $fields, $form_id );
		} else {
			$this->_update_form( $form, $form_id );
			$this->_update_fields( $fields, $form_id );
		}

		wp_redirect( admin_url( "admin.php?page=followize-page-form&form={$form_id}&message=1" ) );
	}

	public static function create_tables()
	{
		Form::maybe_create_table();
		Field::maybe_create_table();
		Form_Field::maybe_create_table();
	}

	public function show_form( $atts )
	{
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts
		);

		$atts['id'] = intval( $atts['id'] );

		if ( $atts['id'] <= 0 ) {
			return;
		}

		$form = new Form( $atts['id'] );

		if ( ! $form->team_key ) {
			return;
		}

		$success = Utils::post( 'followize_success', false );
		$error   = Utils::post( 'followize_error', false );
		$form_id = Utils::post( 'followize_form_id', false, 'intval' );

		$output  = "<form id=\"followize-form-{$atts['id']}\" action=\"#followize-form-{$atts['id']}\" method=\"post\" data-component=\"followize-form\">";
		$output .= $this->get_custom_style_form( $form );
		$output .= "<div class=\"followize-form-body\">";

		if ( ! $form->hide_title ) {
			$output .= "<h2>{$form->title}</h2>";
		}

		if ( $error && $form_id == $atts['id'] ) {
			$output .= "<div class=\"followize-alert-box error\">";
			$output .= "<p>{$error}</p>";
			$output .= "</div>";
		}

		if ( $success && $form_id == $atts['id'] ) {
			$output .= "<div class=\"followize-alert-box success\">";
			$output .= "<p>Formulário enviado com sucesso!</p>";
			$output .= "</div>";
			$output .= "</div>";
			$output .= "</form>";

			return $output;
		}

		do_action( 'followize_before_form_fields', $form_id );
		do_action( "followize_{$form_id}_before_form_fields" );
		$output .= $this->_show_form_fields( $form, $form_id == $atts['id'] );
		do_action( 'followize_after_form_fields', $form_id );
		do_action( "followize_{$form_id}_after_form_fields" );
		$output .= "</div>";
		$output .= "<div class=\"followize-form-footer\">";
		$output .= "<input type=\"hidden\" name=\"formId\" value=\"{$atts['id']}\">";
		$output .= "<input type=\"hidden\" name=\"hubUtmz\" value=\"\">";
		$output .= wp_nonce_field( 'followize_form', 'nonce', true, false );
		$output .= sprintf( '<input type="submit" value="%s">', esc_attr( ( $form->button_text ) ? $form->button_text : 'Enviar' ) );
		$output .= "</div>";
		$output .= "</form>";

		return $output;
	}

	public function get_custom_style_form( $form )
	{
		if ( ! $form->custom_css ) {
			return;
		}

		ob_start();
		?>
		<style>
			<?php echo stripslashes( wp_strip_all_tags( $form->custom_css ) ); ?>
		</style>
		<?php
		return ob_get_clean();
	}

	public function message_field( $output, $field, $show_values, $form )
	{
		//Sanitize types
		$label       = ( ! $form->label_placeholder ) ? esc_html( $field->label ): '';
		$placeholder = ( $form->label_placeholder ) ? esc_attr( $field->label ):   '';
		$required    = $field->required ? 'required':                              '';
		$class       = $field->required ? "followize-field-required":              '';
		$value       = ( $show_values || $field->value ) ? Utils::post( 'message', esc_attr( $field->value ), 'esc_attr' ) : '';

		$output  = '';
		$output .= "<li id=\"wrap-message-id\" class=\"{$class}\">";
		$output .= "<label class=\"followize-field-label\" for=\"message-id\">{$label}</label>";
		$output .= "<div class=\"followize-field-container\">";
		$output .= "<textarea id=\"message-id\" name=\"message\" placeholder=\"{$placeholder}\" {$required}>{$value}</textarea>";
		$output .= "</div>";
		$output .= "</li>";

		return $output;
	}

	public function emailOptIn_field( $output, $field, $show_values, $form )
	{
		//Sanitize types
		$label       = ( ! $form->label_placeholder ) ? esc_html( $field->label ): '';
		$placeholder = ( $form->label_placeholder ) ? esc_attr( $field->label ):   '';
		$value       = Utils::post( 'emailOptIn', '', 'intval' );
		$checked     = $show_values ? checked( 1, $value, false ):                 '';

		$output  = '';
		$output .= "<li id=\"wrap-emailOptIn-id\">";
		$output .= "<input id=\"emailOptIn-id\" type=\"checkbox\" name=\"emailOptIn\" placeholder=\"{$placeholder}\" value=\"1\" {$checked}>";
		$output .= "<label class=\"followize-field-label\" for=\"emailOptIn-id\">{$label}</label>";
		$output .= "</li>";

		return $output;
	}

	public function name_field( $output, $field, $show_values, $form )
	{
		$label       = ( ! $form->label_placeholder ) ? esc_html( $field->label ): '';
		$placeholder = ( $form->label_placeholder ) ? esc_attr( $field->label ):   '';
		$field_limit = intval( $field->field_limit );

		$required    = $field->required ? 'required' : '';
		$field_limit = $field_limit > 0 ? "maxlength=\"$field_limit\"" : '';
		$class       = $field->required ? "followize-field-required" : '';
		$value       = ( $show_values || $field->value ) ? Utils::post( 'clientName', esc_attr( $field->value ), 'esc_attr' ) : '';

		$output  = '';
		$output .= "<li id=\"wrap-clientName-id\" class=\"{$class}\">";
		$output .= "<label class=\"followize-field-label\" for=\"clientName-id\">{$label}</label>";
		$output .= "<div class=\"followize-field-container\">";
		$output .= "<input id=\"clientName-id\" type=\"text\" name=\"clientName\" placeholder=\"{$placeholder}\" value=\"{$value}\" {$field_limit}{$required}>";
		$output .= "</div>";
		$output .= "</li>";

		return $output;
	}

	public function default_style()
	{
		$setting = new Setting();

		if ( $setting->disable_css ) {
			return;
		}
		?>
		<style>
			.followize-form-body ul {
				list-style: none !important;
				margin: 0 !important;
				padding: 0 !important;
			}

			.followize-form-body h2 {
				margin-bottom: 20px;
			}

			.followize-form-body li {
				margin: 0 0 20px 0 !important;
			}

			.followize-field-hidden {
				display: none;
			}

			.followize-field-label:empty {
				display: none;
			}

			.followize-form-footer {
				text-align: center;
			}

			.followize-alert-box {
				color:#555;
				border-radius:10px;
				font-family:Tahoma,Geneva,Arial,sans-serif;font-size:11px;
				padding:10px 10px 10px 36px;
				margin:10px;
			}

			.followize-alert-box.error {
				background:#ffecec;
				border:1px solid #f5aca6;
			}

			.followize-alert-box.success {
				background:#e9ffd9;
				border:1px solid #a6ca8a;
			}

			.followize-alert-box p {
				font-weight: bold;
				margin: 0;
				text-align: center;
			}

			.followize-field-container input, textarea {
				width: 100%;
			}

			.followize-form-body label.error {
				font-style: italic;
				color: #ED143D;
			}

			.followize-field-required .followize-field-label:after {
				content:" *";
				color:red;
			}

			.followize-form-body #emailOptIn-id {
				margin-right: 10px;
			}
		</style>
		<?php
	}

	public function custom_style()
	{
		$setting = new Setting();

		if ( ! $setting->custom_css ) {
			return;
		}
		?>
		<style>
			<?php echo stripslashes( wp_filter_nohtml_kses( $setting->custom_css ) ); ?>
		</style>
		<?php
	}

	public function enqueue_track_tag()
	{
		?>
		<script>
			(function() {
				var hub = document.createElement('script'); hub.type = 'text/javascript'; hub.async = true;
				hub.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'www.followize.com.br/api/utmz.min.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(hub, s);
			})();
			window.onload=function(){function t(t){for(var n=t+"=",u=document.cookie.split(";"),e=0;e<u.length;e++){for(var i=u[e];" "==i.charAt(0);)i=i.substring(1,i.length);if(0==i.indexOf(n))return i.substring(n.length,i.length)}return null}try{for(hubUtmz =document.getElementsByName("hubUtmz"),i=0;i< hubUtmz.length;i++) hubUtmz[i].value=t("hub_utmz")}catch(n){}};
		</script>
		<?php
	}

	public function fields_style()
	{
		echo '<style>.ui-sortable-handle {cursor: move;}</style>';
	}

	private function _show_form_fields( $form, $show_values )
	{
		$output = "<ul id=\"followize-form-{$form->id}\">";

		foreach ( $form->fields as $field ) {
			$output .= $this->_show_form_field( $field, $show_values, $form );
			do_action( "followize_after_{$field->field_name}_form_field" );
			do_action( "followize_{$form->id}_after_{$field->field_name}_form_field" );
		}

		$output .= "</ul>";


		$output = apply_filters( 'followize_fields', $output, $form->fields );
		return apply_filters( "followize_{$form->id}_fields", $output, $form->fields );
	}

	private function _show_form_field( $field, $show_values, $form )
	{
		//Sanitize types
		$label       = ( ! $form->label_placeholder ) ? esc_html( $field->label ) : '';
		$placeholder = ( $form->label_placeholder ) ? esc_attr( $field->label ) : '';
		$field_name  = esc_attr( $field->field_name );
		$field_limit = intval( $field->field_limit );
		$mask        = esc_attr( apply_filters( "followize_field_{$field_name}_mask", $field->mask ) );
		$mask        = esc_attr( apply_filters( "followize_{$form->id}_field_{$field_name}_mask", $field->mask ) );

		$required    = $field->required ? 'required' : '';
		$field_type  = $field->hidden ? 'hidden' : ( $field->field_type ? esc_attr( $field->field_type ) : 'text' );
		$field_limit = $field_limit > 0 ? "maxlength=\"$field_limit\"" : '';
		$mask        = $field->mask ? "data-mask=\"{$mask}\"" : '';
		$class       = $field->required ? "followize-field-required" : '';
		$class       = $field->hidden ? "followize-field-hidden" : $class;
		$style       = $field->hidden ? "display:none;" : '';
		$value       = ( $show_values || $field->value ) ? Utils::post( $field_name, esc_attr( $field->value ), 'esc_attr' ) : '';

		$output     = '';
		$output    .= "<li id=\"wrap-{$field_name}-id\" class=\"{$class}\" style=\"{$style}\">";
		$output    .= "<label class=\"followize-field-label\" for=\"{$field_name}-id\">{$label}</label>";
		$output    .= "<div class=\"followize-field-container\">";
		$output    .= "<input id=\"{$field_name}-id\" type=\"{$field_type}\" name=\"{$field_name}\" placeholder=\"{$placeholder}\" value=\"{$value}\" {$field_limit}{$mask}{$required}>";
		$output    .= "</div>";
		$output    .= "</li>";

		$output = apply_filters( "followize_field_{$field_name}", $output, $field, $show_values, $form );

		return apply_filters( "followize_{$form->id}_field_{$field_name}", $output, $field, $show_values, $form );
	}

	private function _reset_default_field_value( &$fields )
	{
		$fields['name']['required']  = 1;
		$fields['name']['enabled']   = 1;
		$fields['email']['required'] = 1;
		$fields['email']['enabled']  = 1;
	}

	private function _insert_form( $form )
	{
		Form::insert( $form );
		return Form::insert_id();
	}

	private function _insert_form_fields( $fields, $form_id )
	{
		if ( ! $form_id ) {
			return;
		}

		$this->_reset_default_field_value( $fields );
		$this->_filter_custom_fields( $fields, $form_id );

		foreach ( $fields as $field ) {
			$field['form_id'] = $form_id;
			Form_Field::insert( $field );
		}
	}

	private function _update_form( $form, $form_id )
	{
		Form::update( $form, array( 'id' => $form_id ) );
	}

	private function _update_fields( $fields, $form_id )
	{
		$this->_reset_default_field_value( $fields );
		$this->_filter_custom_fields( $fields, $form_id );

		foreach ( $fields as $field ) {
			$field = wp_parse_args(
				$field,
				array(
					'enabled'  => 0,
					'required' => 0,
					'hidden'   => 0,
					'form_id'  => $form_id
				)
			);

			Form_Field::insert_or_update(
				$field,
				array(
					'form_id'    => $form_id,
					'field_name' => $field['field_name']
				)
			);
		}
	}

	private function _filter_custom_fields( &$fields, $form_id )
	{
		$fields = array_filter( $fields, function( $v ) use ( $form_id ) {
			if ( strrpos( $v['field_name'], 'customField' ) === false ) {
				return true;
			} else {
				Form_Field::delete_field( $form_id, $v['field_name'] );
				return (bool) $v['label'];
			};
		} );
	}
}