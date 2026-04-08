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

    /**
     * Main frontend script handle.
     *
     * @since 1.0.0
     * @var string
     */
    const SCRIPT_HANDLE = 'joinotify-otp-login-frontend';

    /**
     * Main frontend style handle.
     *
     * @since 1.0.0
     * @var string
     */
    const STYLE_HANDLE = 'joinotify-otp-login-frontend';

    /**
     * Register WordPress hooks.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
    }


    /**
     * Register and enqueue frontend CSS and JavaScript for account and checkout pages.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_frontend_assets() {
        if ( ! $this->should_enqueue_frontend_assets() ) {
            return;
        }

        wp_register_style(
            'joinotify-intl-tel-input',
            'https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.0/build/css/intlTelInput.css',
            array(),
            '25.3.0'
        );

        wp_register_script(
            'joinotify-intl-tel-input',
            'https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.0/build/js/intlTelInput.min.js',
            array(),
            '25.3.0',
            true
        );

        $bundle = $this->get_frontend_asset_bundle();

        if ( ! empty( $bundle['use_vite'] ) ) {
            wp_register_style(
                self::STYLE_HANDLE,
                $bundle['style_src'],
                array( 'joinotify-intl-tel-input' ),
                $bundle['version']
            );

            wp_register_script(
                self::SCRIPT_HANDLE,
                $bundle['script_src'],
                array( 'joinotify-intl-tel-input' ),
                $bundle['version'],
                true
            );

            wp_script_add_data( self::SCRIPT_HANDLE, 'type', 'module' );
        } else {
            wp_register_style(
                self::STYLE_HANDLE,
                JOINOTIFY_OTP_LOGIN_ASSETS . 'css/frontend.css',
                array( 'joinotify-intl-tel-input' ),
                JOINOTIFY_OTP_LOGIN_VERSION
            );

            wp_register_script(
                self::SCRIPT_HANDLE,
                JOINOTIFY_OTP_LOGIN_ASSETS . 'js/frontend.js',
                array( 'jquery', 'joinotify-intl-tel-input' ),
                JOINOTIFY_OTP_LOGIN_VERSION,
                true
            );
        }

        wp_localize_script( self::SCRIPT_HANDLE, 'joinotifyOtpLogin', $this->get_script_params() );

        wp_enqueue_style( self::STYLE_HANDLE );
        wp_enqueue_script( self::SCRIPT_HANDLE );
    }


    /**
     * Determine whether the OTP login assets should be loaded on the current request.
     *
     * @since 1.0.0
     * @return bool True when assets should be enqueued, false otherwise.
     */
    private function should_enqueue_frontend_assets() {
        $is_account = function_exists( 'is_account_page' ) && is_account_page();
        $is_checkout = function_exists( 'is_checkout' ) && is_checkout();

        return ! is_user_logged_in() && ( $is_account || $is_checkout );
    }


    /**
     * Build the localized configuration passed to the frontend script.
     *
     * @since 1.0.0
     * @return array<string,mixed> Script configuration values.
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
            'otpLength' => (int) apply_filters( 'Joinotify/Otp_Login/Otp_Length', 6 ),
            'i18n' => array(
                'invalidPhone' => __( 'Enter a valid phone number with country code.', 'joinotify-otp-login' ),
                'invalidOtp' => __( 'Enter the verification code you received.', 'joinotify-otp-login' ),
                'sending' => __( 'Sending...', 'joinotify-otp-login' ),
                'verifying' => __( 'Verifying...', 'joinotify-otp-login' ),
                'loading' => __( 'Processing...', 'joinotify-otp-login' ),
                'resendOtpLabel' => __( 'Resend code in', 'joinotify-otp-login' ),
                'resendOtpButton' => __( 'Resend code', 'joinotify-otp-login' ),
                'secondsLabel' => __( 'seconds', 'joinotify-otp-login' ),
                'unexpectedError' => __( 'We could not complete the request right now. Please try again.', 'joinotify-otp-login' ),
            ),
        );
    }


    /**
     * Resolve a built Vite bundle when available and fall back to legacy assets otherwise.
     *
     * @since 1.0.0
     * @return array<string,mixed>
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
}
