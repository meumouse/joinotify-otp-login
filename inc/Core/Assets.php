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
            'intlUtilsUrl' => 'https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.0/build/js/utils.js',
            'lostPasswordUrl' => esc_url_raw( wp_lostpassword_url() ),
            'otpLength' => (int) apply_filters( 'Joinotify/Otp_Login/Otp_Length', 6 ),
            'theme' => $this->get_theme_config(),
            'i18n' => array(
                'panelEyebrow' => __( 'Acesso seguro', 'joinotify-otp-login' ),
                'phoneTitle' => __( 'Entrar com WhatsApp', 'joinotify-otp-login' ),
                'phoneDescription' => __( 'Use seu número para receber o código e entrar com mais rapidez.', 'joinotify-otp-login' ),
                'phoneLabel' => __( 'Telefone', 'joinotify-otp-login' ),
                'phoneHelper' => __( 'Digite um número válido. O DDI será exibido automaticamente.', 'joinotify-otp-login' ),
                'phoneAction' => __( 'Entrar com WhatsApp', 'joinotify-otp-login' ),
                'useEmailPassword' => __( 'Usar e-mail e senha', 'joinotify-otp-login' ),
                'emailSeparator' => __( 'Ou entre com e-mail', 'joinotify-otp-login' ),
                'emailLabel' => __( 'E-mail', 'joinotify-otp-login' ),
                'emailPlaceholder' => __( 'Digite seu e-mail', 'joinotify-otp-login' ),
                'passwordLabel' => __( 'Senha', 'joinotify-otp-login' ),
                'passwordPlaceholder' => __( 'Digite sua senha', 'joinotify-otp-login' ),
                'rememberMe' => __( 'Lembrar de mim', 'joinotify-otp-login' ),
                'forgotPassword' => __( 'Esqueceu a senha?', 'joinotify-otp-login' ),
                'signIn' => __( 'Entrar', 'joinotify-otp-login' ),
                'signInLoading' => __( 'Processando...', 'joinotify-otp-login' ),
                'backToWhatsapp' => __( 'Voltar ao WhatsApp', 'joinotify-otp-login' ),
                'requestCode' => __( 'Receber código', 'joinotify-otp-login' ),
                'requestCodeLoading' => __( 'Enviando...', 'joinotify-otp-login' ),
                'enterCodeTitle' => __( 'Digite o código de acesso', 'joinotify-otp-login' ),
                'enterCodeDescription' => __( 'Informe o código de %d dígitos enviado para o seu WhatsApp.', 'joinotify-otp-login' ),
                'otpDigitLabel' => __( 'Dígito %d do código', 'joinotify-otp-login' ),
                'resendOtpLabel' => __( 'Reenviar código em', 'joinotify-otp-login' ),
                'resendOtpButton' => __( 'Reenviar código', 'joinotify-otp-login' ),
                'secondsLabel' => __( 'segundos', 'joinotify-otp-login' ),
                'verifyCode' => __( 'Verificar código', 'joinotify-otp-login' ),
                'verifyCodeLoading' => __( 'Verificando...', 'joinotify-otp-login' ),
                'changePhone' => __( 'Alterar número', 'joinotify-otp-login' ),
                'showPassword' => __( 'Mostrar senha', 'joinotify-otp-login' ),
                'hidePassword' => __( 'Ocultar senha', 'joinotify-otp-login' ),
                'missingCredentials' => __( 'Preencha o e-mail e a senha.', 'joinotify-otp-login' ),
                'invalidPhone' => __( 'Digite um telefone válido com DDI.', 'joinotify-otp-login' ),
                'invalidOtp' => __( 'Digite o código de verificação recebido.', 'joinotify-otp-login' ),
                'unexpectedError' => __( 'Não foi possível concluir a solicitação agora. Tente novamente.', 'joinotify-otp-login' ),
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


    /**
     * Build the frontend theme payload from saved options.
     *
     * @since 1.0.0
     * @return array<string,mixed>
     */
    private function get_theme_config() {
        $primary_color = get_option( 'joinotify_otp_login_primary_color', '#4f46e5' );
        $border_radius = (int) get_option( 'joinotify_otp_login_border_radius', 28 );
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
     * Convert palette rows into a lookup map.
     *
     * @since 1.0.0
     * @param array<int,array{step:string,color:string}> $palette Palette rows.
     * @return array<string,string>
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
