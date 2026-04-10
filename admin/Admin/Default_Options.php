<?php

namespace MeuMouse\Joinotify\Otp_Login\Admin;

use MeuMouse\Joinotify\Otp_Login\Support\Color_Scheme;

defined('ABSPATH') || exit;

/**
 * Register OTP Login defaults inside Joinotify shared settings.
 *
 * @since 1.0.0
 * @package MeuMouse\Joinotify\Otp_Login\Admin
 */
class Default_Options {

	/**
	 * Register the plugin default options through Joinotify's filter.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_filter( 'Joinotify/Admin/Set_Default_Options', array( $this, 'set_default_options' ), 20, 1 );
		add_action( 'admin_init', array( $this, 'migrate_legacy_options' ), 5 );
	}

	/**
	 * Merge the OTP Login defaults into Joinotify's shared settings array.
	 *
	 * @since 1.0.0
	 * @param array<string,mixed> $options Existing default options.
	 * @return array<string,mixed>
	 */
	public function set_default_options( $options ) {
		$palette = array();

		foreach ( Color_Scheme::generate_palette( '#4f46e5' ) as $row ) {
			if ( empty( $row['step'] ) || empty( $row['color'] ) ) {
				continue;
			}

			$palette[ (string) $row['step'] ] = (string) $row['color'];
		}

		return array_merge(
			(array) $options,
			array(
				'enable_otp_login_integration' => 'yes',
				'otp_login_primary_color' => '#4f46e5',
				'otp_login_palette' => $palette,
				'otp_login_border_radius_value' => '0.375',
				'otp_login_border_radius_unit' => 'rem',
				'otp_login_sender_phone' => '',
			)
		);
	}

	/**
	 * Copy legacy standalone options into Joinotify's shared settings array.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function migrate_legacy_options() {
		$shared_options = get_option( 'joinotify_settings', array() );

		if ( ! is_array( $shared_options ) ) {
			$shared_options = array();
		}

		$defaults = $this->set_default_options( array() );
		$legacy_map = array(
			'otp_login_primary_color' => 'joinotify_otp_login_primary_color',
			'otp_login_border_radius_value' => 'joinotify_otp_login_border_radius',
			'otp_login_sender_phone' => 'joinotify_otp_login_sender_phone',
		);
		$updated = false;

		foreach ( $legacy_map as $shared_key => $legacy_key ) {
			$legacy_value = get_option( $legacy_key, null );

			if ( null === $legacy_value || '' === $legacy_value ) {
				continue;
			}

			if ( 'otp_login_border_radius_value' === $shared_key ) {
				$shared_options[ $shared_key ] = $legacy_value;
				$shared_options['otp_login_border_radius_unit'] = 'px';
				$updated = true;
				continue;
			}

			$current_value = $shared_options[ $shared_key ] ?? null;
			$default_value = $defaults[ $shared_key ] ?? null;

			if ( ! array_key_exists( $shared_key, $shared_options ) || '' === $current_value || $current_value === $default_value ) {
				$shared_options[ $shared_key ] = $legacy_value;
				$updated = true;
			}
		}

		if ( $updated ) {
			update_option( 'joinotify_settings', $shared_options );
		}
	}
}
