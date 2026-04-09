<?php

namespace MeuMouse\Joinotify\Otp_Login\Core;

defined('ABSPATH') || exit;

/**
 * Handles frontend asset registration and localization.
 *
 * @since 1.0.0
 * @package MeuMouse\Joinotify\Otp_Login\Core
 * @author MeuMouse.com
 */
class Assets {

	const SCRIPT_HANDLE = 'joinotify-otp-login-frontend';
	const STYLE_HANDLE = 'joinotify-otp-login-frontend';

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
		add_filter( 'script_loader_tag', array( $this, 'filter_script_loader_tag' ), 10, 3 );
	}

	public function register_frontend_assets() {
		if ( ! $this->should_enqueue_frontend_assets() ) {
			return;
		}

		$bundle = $this->get_frontend_asset_bundle();

		if ( ! empty( $bundle['use_vite'] ) ) {
			wp_register_style(
				self::STYLE_HANDLE,
				$bundle['style_src'],
				array(),
				$bundle['version']
			);

			wp_register_script(
				self::SCRIPT_HANDLE,
				$bundle['script_src'],
				array(),
				$bundle['version'],
				true
			);

			wp_script_add_data( self::SCRIPT_HANDLE, 'type', 'module' );
		} else {
			wp_register_style(
				self::STYLE_HANDLE,
				JOINOTIFY_OTP_LOGIN_ASSETS . 'css/frontend.css',
				array(),
				JOINOTIFY_OTP_LOGIN_VERSION
			);

			wp_register_script(
				self::SCRIPT_HANDLE,
				JOINOTIFY_OTP_LOGIN_ASSETS . 'js/frontend.js',
				array(),
				JOINOTIFY_OTP_LOGIN_VERSION,
				true
			);
		}

		wp_localize_script( self::SCRIPT_HANDLE, 'joinotifyOtpLogin', $this->get_script_params() );

		wp_enqueue_style( self::STYLE_HANDLE );
		wp_enqueue_script( self::SCRIPT_HANDLE );
	}

	public function filter_script_loader_tag( $tag, $handle, $src ) {
		if ( self::SCRIPT_HANDLE !== $handle ) {
			return $tag;
		}

		return sprintf( '<script type="module" src="%s" id="%s-js"></script>', esc_url( $src ), esc_attr( $handle ) );
	}

	private function should_enqueue_frontend_assets() {
		$is_account = function_exists( 'is_account_page' ) && is_account_page();
		$is_checkout = function_exists( 'is_checkout' ) && is_checkout();
		$has_shortcode = false;

		if ( is_singular() ) {
			$post = get_post();

			if ( $post instanceof \WP_Post ) {
				$has_shortcode = has_shortcode( $post->post_content, 'joinotify_otp_login' );
			}
		}

		return ! is_user_logged_in() && ( $is_account || $is_checkout || $has_shortcode );
	}

	private function get_script_params() {
		$default_country = 'br';

		if ( function_exists( 'wc_get_base_location' ) ) {
			$base_location = wc_get_base_location();

			if ( ! empty( $base_location['country'] ) ) {
				$default_country = strtolower( $base_location['country'] );
			}
		}

		return array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'restUrl' => esc_url_raw( rest_url( 'joinotify-otp-login/v1' ) ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'legacyNonce' => wp_create_nonce( 'joinotify_otp_login_nonce' ),
			'defaultCountry' => $default_country,
			'lostPasswordUrl' => esc_url_raw( wp_lostpassword_url() ),
			'otpLength' => (int) apply_filters( 'Joinotify/Otp_Login/Otp_Length', 6 ),
			'theme' => $this->get_theme_config(),
			'i18n' => array(
				'panelEyebrow' => __( 'Secure access', 'joinotify-otp-login' ),
				'phoneTitle' => __( 'Log in with WhatsApp', 'joinotify-otp-login' ),
				'phoneDescription' => __( 'Use your phone number to receive a code and sign in faster.', 'joinotify-otp-login' ),
				'phoneLabel' => __( 'Phone number', 'joinotify-otp-login' ),
				'phoneHelper' => __( 'Enter a valid phone number. The country code will be detected automatically.', 'joinotify-otp-login' ),
				'phoneAction' => __( 'Log in with WhatsApp', 'joinotify-otp-login' ),
				'useEmailPassword' => __( 'Use email and password', 'joinotify-otp-login' ),
				'emailSeparator' => __( 'Or sign in with email', 'joinotify-otp-login' ),
				'emailLabel' => __( 'Email', 'joinotify-otp-login' ),
				'emailPlaceholder' => __( 'Enter your email', 'joinotify-otp-login' ),
				'passwordLabel' => __( 'Password', 'joinotify-otp-login' ),
				'passwordPlaceholder' => __( 'Enter your password', 'joinotify-otp-login' ),
				'rememberMe' => __( 'Remember me', 'joinotify-otp-login' ),
				'forgotPassword' => __( 'Forgot your password?', 'joinotify-otp-login' ),
				'signIn' => __( 'Sign in', 'joinotify-otp-login' ),
				'signInLoading' => __( 'Processing...', 'joinotify-otp-login' ),
				'backToWhatsapp' => __( 'Back to WhatsApp', 'joinotify-otp-login' ),
				'requestCode' => __( 'Request code', 'joinotify-otp-login' ),
				'requestCodeLoading' => __( 'Sending...', 'joinotify-otp-login' ),
				'enterCodeTitle' => __( 'Enter the access code', 'joinotify-otp-login' ),
				'enterCodeDescription' => __( 'Enter the %d-digit code sent to your WhatsApp.', 'joinotify-otp-login' ),
				'otpDigitLabel' => __( 'Code digit %d', 'joinotify-otp-login' ),
				'resendOtpLabel' => __( 'Resend code in', 'joinotify-otp-login' ),
				'resendOtpButton' => __( 'Resend code', 'joinotify-otp-login' ),
				'secondsLabel' => __( 'seconds', 'joinotify-otp-login' ),
				'verifyCode' => __( 'Verify code', 'joinotify-otp-login' ),
				'verifyCodeLoading' => __( 'Verifying...', 'joinotify-otp-login' ),
				'changePhone' => __( 'Change number', 'joinotify-otp-login' ),
				'showPassword' => __( 'Show password', 'joinotify-otp-login' ),
				'hidePassword' => __( 'Hide password', 'joinotify-otp-login' ),
				'missingCredentials' => __( 'Fill in the email and password.', 'joinotify-otp-login' ),
				'invalidPhone' => __( 'Enter a valid phone number with country code.', 'joinotify-otp-login' ),
				'invalidOtp' => __( 'Enter the verification code you received.', 'joinotify-otp-login' ),
				'unexpectedError' => __( 'We could not complete the request right now. Please try again.', 'joinotify-otp-login' ),
			),
		);
	}

	private function get_frontend_asset_bundle() {
		$manifest_path = trailingslashit( JOINOTIFY_OTP_LOGIN_DIR ) . 'dist/.vite/manifest.json';

		if ( ! file_exists( $manifest_path ) ) {
			return array(
				'use_vite' => false,
			);
		}

		$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );

		if ( ! is_array( $manifest ) ) {
			return array(
				'use_vite' => false,
			);
		}

		$entry = $manifest['src/main.js'] ?? null;

		if ( empty( $entry['file'] ) ) {
			return array(
				'use_vite' => false,
			);
		}

		$base_url = trailingslashit( JOINOTIFY_OTP_LOGIN_URL ) . 'dist/';
		$css_src = '';

		if ( ! empty( $entry['css'] ) && is_array( $entry['css'] ) ) {
			$css_src = $base_url . ltrim( $entry['css'][0], '/' );
		} else {
			$css_src = trailingslashit( JOINOTIFY_OTP_LOGIN_ASSETS ) . 'css/frontend.css';
		}

		return array(
			'use_vite' => true,
			'script_src' => $base_url . ltrim( $entry['file'], '/' ),
			'style_src' => $css_src,
			'version' => ! empty( $entry['file'] ) ? md5( $entry['file'] . '|' . (string) filemtime( $manifest_path ) ) : JOINOTIFY_OTP_LOGIN_VERSION,
		);
	}

	private function get_theme_config() {
		$primary_color = get_option( 'joinotify_otp_login_primary_color', '#4f46e5' );
		$border_radius = (int) get_option( 'joinotify_otp_login_border_radius', 6 );
		$palette = array();

		if ( class_exists( '\\MeuMouse\\Joinotify\\Otp_Login\\Support\\Color_Scheme' ) ) {
			$palette = \MeuMouse\Joinotify\Otp_Login\Support\Color_Scheme::generate_palette( $primary_color );
		}

		return array(
			'primaryColor' => $primary_color,
			'borderRadius' => max( 0, min( 80, $border_radius ) ),
			'palette' => $this->palette_to_map( $palette ),
		);
	}

	private function palette_to_map( array $palette ) {
		$map = array();

		foreach ( $palette as $token ) {
			if ( ! is_array( $token ) || empty( $token['step'] ) || empty( $token['color'] ) ) {
				continue;
			}

			$map[ (string) $token['step'] ] = (string) $token['color'];
		}

		return $map;
	}
}
