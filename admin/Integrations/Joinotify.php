<?php

namespace MeuMouse\Joinotify\Otp_Login\Integrations;

defined('ABSPATH') || exit;

/**
 * Joinotify builder integration for OTP login triggers and placeholders.
 *
 * @since 1.0.0
 * @package MeuMouse\Joinotify\Otp_Login\Integrations
 */
class Joinotify {

	/**
	 * Register Joinotify hooks for OTP login.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
	//	add_filter( 'Joinotify/Builder/Get_All_Triggers', array( $this, 'add_triggers' ), 10, 1 );
	//	add_filter( 'Joinotify/Builder/Placeholders_List', array( $this, 'add_placeholders' ), 20, 2 );
	//	add_action( 'Joinotify/Otp_Login/OTP_Sent', array( $this, 'log_otp_sent' ), 10, 1 );
	}

	/**
	 * Register the OTP login trigger in the Joinotify builder.
	 *
	 * @since 1.0.0
	 * @param array $triggers Existing triggers list.
	 * @return array
	 */
	public function add_triggers( $triggers ) {
		$triggers['otp_login'] = array(
			array(
				'data_trigger' => 'otp_login_otp_sent',
				'title' => esc_html__( 'OTP enviado (Login)', 'joinotify-otp-login' ),
				'description' => esc_html__( 'Disparado quando o Joinotify OTP Login envia com sucesso um codigo de verificacao via WhatsApp.', 'joinotify-otp-login' ),
				'require_settings' => false,
			),
		);

		return $triggers;
	}

	/**
	 * Register placeholders for the OTP login trigger.
	 *
	 * @since 1.0.0
	 * @param array $placeholders Existing placeholder groups.
	 * @param array $payload Trigger payload.
	 * @return array
	 */
	public function add_placeholders( $placeholders, $payload ) {
		$otp_payload = is_array( $payload ) ? $payload : array();
		$phone = isset( $otp_payload['phone'] ) ? (string) $otp_payload['phone'] : '';
		$otp = isset( $otp_payload['otp'] ) ? (string) $otp_payload['otp'] : '';
		$message = isset( $otp_payload['message'] ) ? (string) $otp_payload['message'] : '';
		$sender = isset( $otp_payload['sender'] ) ? (string) $otp_payload['sender'] : '';
		$receiver = isset( $otp_payload['receiver'] ) ? (string) $otp_payload['receiver'] : '';
		$expires = isset( $otp_payload['expires'] ) ? (int) $otp_payload['expires'] : 0;
		$duration = isset( $otp_payload['duration'] ) ? (int) $otp_payload['duration'] : 0;

		$placeholders['otp_login'] = array(
			'{{ joinotify_otp_phone }}' => array(
				'triggers' => array( 'otp_login_otp_sent' ),
				'description' => esc_html__( 'Telefone normalizado usado no envio do OTP', 'joinotify-otp-login' ),
				'replacement' => array(
					'production' => $phone,
					'sandbox' => '+5511999999999',
				),
			),
			'{{ joinotify_otp_code }}' => array(
				'triggers' => array( 'otp_login_otp_sent' ),
				'description' => esc_html__( 'Codigo OTP gerado para o login', 'joinotify-otp-login' ),
				'replacement' => array(
					'production' => $otp,
					'sandbox' => '123456',
				),
			),
			'{{ joinotify_otp_message }}' => array(
				'triggers' => array( 'otp_login_otp_sent' ),
				'description' => esc_html__( 'Mensagem completa enviada ao usuario', 'joinotify-otp-login' ),
				'replacement' => array(
					'production' => $message,
					'sandbox' => esc_html__( 'Your access code is: 123456. This code expires in 5 minutes.', 'joinotify-otp-login' ),
				),
			),
			'{{ joinotify_otp_sender }}' => array(
				'triggers' => array( 'otp_login_otp_sent' ),
				'description' => esc_html__( 'Numero do remetente configurado no Joinotify', 'joinotify-otp-login' ),
				'replacement' => array(
					'production' => $sender,
					'sandbox' => '+5500000000000',
				),
			),
			'{{ joinotify_otp_receiver }}' => array(
				'triggers' => array( 'otp_login_otp_sent' ),
				'description' => esc_html__( 'Numero do destinatario que recebeu o OTP', 'joinotify-otp-login' ),
				'replacement' => array(
					'production' => $receiver,
					'sandbox' => '+5511988887777',
				),
			),
			'{{ joinotify_otp_expires_at }}' => array(
				'triggers' => array( 'otp_login_otp_sent' ),
				'description' => esc_html__( 'Tempo restante do OTP em minutos e segundos', 'joinotify-otp-login' ),
				'replacement' => array(
					'production' => $this->format_duration( $duration ?: $expires ),
					'sandbox' => $this->format_duration( 300 ),
				),
			),
		);

		return $placeholders;
	}

	/**
	 * Optional observer for the OTP sent action.
	 *
	 * @since 1.0.0
	 * @param array $payload OTP payload.
	 * @return void
	 */
	public function log_otp_sent( $payload ) {
		do_action( 'Joinotify/Otp_Login/OTP_Sent/After', $payload );
	}

	/**
	 * Format a duration in seconds as minutes and seconds.
	 *
	 * @since 1.0.0
	 * @param int $seconds Duration in seconds.
	 * @return string
	 */
	private function format_duration( $seconds ) {
		$seconds = max( 0, (int) $seconds );
		$minutes = intdiv( $seconds, 60 );
		$remaining_seconds = $seconds % 60;

		if ( 0 === $minutes && 0 === $remaining_seconds ) {
			return '';
		}

		if ( 0 === $minutes ) {
			return sprintf(
				_n( '%s segundo', '%s segundos', $remaining_seconds, 'joinotify-otp-login' ),
				number_format_i18n( $remaining_seconds )
			);
		}

		if ( 0 === $remaining_seconds ) {
			return sprintf(
				_n( '%s minuto', '%s minutos', $minutes, 'joinotify-otp-login' ),
				number_format_i18n( $minutes )
			);
		}

		return sprintf(
			/* translators: 1: minutes, 2: seconds */
			__( '%1$s minuto(s) e %2$s segundo(s)', 'joinotify-otp-login' ),
			number_format_i18n( $minutes ),
			number_format_i18n( $remaining_seconds )
		);
	}
}
