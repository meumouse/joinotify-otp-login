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

	const SCRIPT_HANDLE = 'joinotify-otp-login';
	const STYLE_HANDLE = 'joinotify-otp-login-frontend';

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
		add_filter( 'script_loader_tag', array( $this, 'filter_script_loader_tag' ), 10, 3 );
	}

	/**
	 * Register and enqueue the frontend assets needed by the login widget.
	 *
	 * @since 1.0.0
	 * @return void
	 */
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
				array( 'wp-i18n' ),
				$bundle['version'],
				true
			);

			wp_set_script_translations(
				self::SCRIPT_HANDLE,
				'joinotify-otp-login',
				trailingslashit( JOINOTIFY_OTP_LOGIN_DIR ) . 'languages'
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
				array( 'wp-i18n' ),
				JOINOTIFY_OTP_LOGIN_VERSION,
				true
			);

			wp_set_script_translations(
				self::SCRIPT_HANDLE,
				'joinotify-otp-login',
				trailingslashit( JOINOTIFY_OTP_LOGIN_DIR ) . 'languages'
			);
		}

		wp_localize_script( self::SCRIPT_HANDLE, 'joinotifyOtpLogin', $this->get_script_params() );

		wp_enqueue_style( self::STYLE_HANDLE );
		wp_enqueue_script( self::SCRIPT_HANDLE );
	}

	/**
	 * Ensure the compiled frontend bundle is printed as an ES module.
	 *
	 * @since 1.0.0
	 * @param string $tag Generated script tag.
	 * @param string $handle Registered script handle.
	 * @param string $src Script source URL.
	 * @return string Filtered script tag.
	 */
	public function filter_script_loader_tag( $tag, $handle, $src ) {
		if ( self::SCRIPT_HANDLE !== $handle ) {
			return $tag;
		}

		return sprintf( '<script type="module" src="%s" id="%s-js"></script>', esc_url( $src ), esc_attr( $handle ) );
	}

	/**
	 * Decide whether the frontend bundle should be loaded for the current request.
	 *
	 * @since 1.0.0
	 * @return bool True when the OTP login UI is relevant to the page.
	 */
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

	/**
	 * Build the JavaScript localization payload consumed by the frontend app.
	 *
	 * @since 1.0.0
	 * @return array<string,mixed> Script parameters.
	 */
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
			'i18n' => $this->get_frontend_i18n_map(),
		);
	}

	/**
	 * Build a translated string map for the frontend Vue app.
	 *
	 * This acts as a runtime fallback when wp_set_script_translations is not
	 * available or when the module bundle executes before wp.i18n is ready.
	 *
	 * @since 1.0.0
	 * @return array<string,string> Translated strings indexed by source text.
	 */
	private function get_frontend_i18n_map() {
		$strings = array(
			'Secure access',
			'Log in with WhatsApp',
			'Enter a valid phone number. The country code will be detected automatically.',
			'Code digit %d',
			'Phone number',
			'Sending...',
			'Use email and password',
			'Enter the access code',
			'Enter the %d-digit code sent to your WhatsApp.',
			'Code digit %d',
			'Remember me',
			'Resend code in',
			'seconds',
			'Resend code',
			'Verifying...',
			'Change number',
			'Or sign in with email',
			'Email or username',
			'Enter your email or username',
			'Password',
			'Enter your password',
			'Hide password',
			'Show password',
			'Forgot your password?',
			'Sign in',
			'Back to WhatsApp',
			'Please enter a number',
			'Invalid number',
			'Invalid country code',
			'Too short',
			'Too long',
			'Enter a valid phone number with country code.',
			'We could not complete the request right now. Please try again.',
			'Enter the verification code you received.',
			'Fill in the email or username and password.',
			'Or sign in with email',
			'Please enter a number',
			'Invalid number',
			'Invalid country code',
			'Too short',
			'Too long',
		);

		$map = array();

		foreach ( $strings as $string ) {
			$map[ $string ] = __( $string, 'joinotify-otp-login' );
		}

		return $map;
	}

	/**
	 * Resolve the Vite build manifest when the compiled bundle exists.
	 *
	 * @since 1.0.0
	 * @return array<string,mixed> Bundle metadata or a fallback flag.
	 */
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

	/**
	 * Convert the generated color palette into an associative map for JS.
	 *
	 * @since 1.0.0
	 * @param array<int,array<string,string>> $palette Palette rows.
	 * @return array<string,string> Palette indexed by token step.
	 */
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

	/**
	 * Map palette rows to a key/value object for easier consumption in Vue.
	 *
	 * @since 1.0.0
	 * @param array<int,array<string,string>> $palette Palette rows.
	 * @return array<string,string> Palette map keyed by step.
	 */
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
