<?php

namespace MeuMouse\Joinotify\Otp_Login\Admin;

use MeuMouse\Joinotify\Otp_Login\Support\Color_Scheme;

defined('ABSPATH') || exit;

class Settings_Page {

	const OPTION_GROUP = 'joinotify_otp_login_settings_group';
	const PAGE_SLUG = 'joinotify-otp-login-settings';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 99 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'Joinotify/Otp_Validation/Sender', array( $this, 'filter_sender' ), 10, 1 );
	}

	public function register_menu() {
		add_submenu_page(
			'joinotify-workflows',
			esc_html__( 'OTP Login Settings', 'joinotify-otp-login' ),
			esc_html__( 'OTP Login Settings', 'joinotify-otp-login' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			'joinotify_otp_login_primary_color',
			array(
				'type' => 'string',
				'sanitize_callback' => array( $this, 'sanitize_primary_color' ),
				'default' => '#4f46e5',
			)
		);

		register_setting(
			self::OPTION_GROUP,
			'joinotify_otp_login_border_radius',
			array(
				'type' => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_border_radius' ),
				'default' => 6,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			'joinotify_otp_login_sender_phone',
			array(
				'type' => 'string',
				'sanitize_callback' => array( $this, 'sanitize_sender_phone' ),
				'default' => '',
			)
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'joinotify-otp-login' ) );
		}

		$primary_color = $this->get_primary_color();
		$border_radius = $this->get_border_radius();
		$palette = Color_Scheme::generate_palette( $primary_color );
		$senders = $this->get_available_senders();
		$selected_sender = $this->get_selected_sender( $senders );

		include trailingslashit( JOINOTIFY_OTP_LOGIN_DIR ) . 'admin/Views/Settings.php';
	}

	public function enqueue_assets() {
		if ( empty( $_GET['page'] ) || self::PAGE_SLUG !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		wp_enqueue_style(
			'joinotify-otp-login-admin',
			trailingslashit( JOINOTIFY_OTP_LOGIN_ASSETS ) . 'css/admin.css',
			array(),
			JOINOTIFY_OTP_LOGIN_VERSION
		);
	}

	public function sanitize_primary_color( $value ) {
		$value = Color_Scheme::sanitize_hex( $value );
		return ! empty( $value ) ? $value : '#4f46e5';
	}

	public function sanitize_border_radius( $value ) {
		$value = absint( $value );
		return max( 0, min( 80, $value ) );
	}

	public function sanitize_sender_phone( $value ) {
		return sanitize_text_field( $value );
	}

	public function filter_sender( $sender ) {
		$available_senders = $this->get_available_senders();
		$selected_sender = $this->get_selected_sender( $available_senders );

		if ( ! empty( $selected_sender ) ) {
			return $selected_sender;
		}

		return $sender;
	}

	public function get_primary_color() {
		return $this->sanitize_primary_color( get_option( 'joinotify_otp_login_primary_color', '#4f46e5' ) );
	}

	public function get_border_radius() {
		return $this->sanitize_border_radius( get_option( 'joinotify_otp_login_border_radius', 6 ) );
	}

	public function get_available_senders() {
		$senders = get_option( 'joinotify_get_phones_senders', array() );

		if ( ! is_array( $senders ) ) {
			$senders = array( $senders );
		}

		$normalized = array();

		foreach ( $senders as $sender ) {
			if ( is_array( $sender ) ) {
				$candidate = $sender['phone'] ?? $sender['number'] ?? $sender['value'] ?? $sender['sender'] ?? '';
			} else {
				$candidate = $sender;
			}

			$candidate = sanitize_text_field( (string) $candidate );

			if ( ! empty( $candidate ) ) {
				$normalized[] = $candidate;
			}
		}

		return array_values( array_unique( array_filter( $normalized ) ) );
	}

	public function get_selected_sender( $available_senders = null ) {
		$available_senders = null === $available_senders ? $this->get_available_senders() : $available_senders;

		if ( empty( $available_senders ) ) {
			return '';
		}

		$saved_sender = sanitize_text_field( (string) get_option( 'joinotify_otp_login_sender_phone', '' ) );

		if ( ! empty( $saved_sender ) && in_array( $saved_sender, $available_senders, true ) ) {
			return $saved_sender;
		}

		return (string) reset( $available_senders );
	}
}
