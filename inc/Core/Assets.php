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
            'nonce' => wp_create_nonce( 'joinotify_otp_login_nonce' ),
            'defaultCountry' => $default_country,
            'i18n' => array(
                'invalidPhone' => __( 'Informe um telefone valido com DDI.', 'joinotify-otp-login' ),
                'invalidOtp' => __( 'Informe o codigo de verificacao recebido.', 'joinotify-otp-login' ),
                'sending' => __( 'Enviando...', 'joinotify-otp-login' ),
                'verifying' => __( 'Verificando...', 'joinotify-otp-login' ),
                'loading' => __( 'Processando...', 'joinotify-otp-login' ),
                'unexpectedError' => __( 'Nao foi possivel concluir a solicitacao agora. Tente novamente.', 'joinotify-otp-login' ),
            ),
        );
    }
}
