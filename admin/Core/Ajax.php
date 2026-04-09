<?php

namespace MeuMouse\Joinotify\Otp_Login\Core;

use MeuMouse\Joinotify\Otp_Login\Services\Auth_Flow_Service;

defined('ABSPATH') || exit;

/**
 * Registers and handles AJAX endpoints for the OTP login flow.
 *
 * @since 1.0.0
 * @package MeuMouse\Joinotify\Otp_Login\Core
 * @author MeuMouse.com
 */
class Ajax {

    /**
     * Authentication flow service.
     *
     * @since 1.0.0
     * @var Auth_Flow_Service
     */
    private $auth_flow;

    /**
     * Register AJAX actions for authenticated and guest users.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        $this->auth_flow = new Auth_Flow_Service();

        $ajax_actions = array(
            'joinotify_otp_request_code' => 'handle_request_code',
            'joinotify_otp_verify_code' => 'handle_verify_code',
            'joinotify_otp_password_login' => 'handle_password_login',
            'joinotify_otp_register_user' => 'handle_register_user',
        );

        foreach ( $ajax_actions as $action => $method ) {
            add_action( 'wp_ajax_' . $action, array( $this, $method ) );
            add_action( 'wp_ajax_nopriv_' . $action, array( $this, $method ) );
        }
    }


    /**
     * Handle the phone lookup step and request an OTP when a matching account exists.
     *
     * @since 1.0.0
     * @return void
     */
    public function handle_request_code() {
        $this->verify_request();

        $result = $this->auth_flow->request_otp_login( sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ) );
        $this->maybe_send_result( $result );
    }


    /**
     * Validate the submitted OTP and authenticate the user.
     *
     * @since 1.0.0
     * @return void
     */
    public function handle_verify_code() {
        $this->verify_request();

        $result = $this->auth_flow->verify_otp_login(
            sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
            sanitize_text_field( wp_unslash( $_POST['otp'] ?? '' ) ),
            ! empty( $_POST['remember'] )
        );

        if ( ! is_wp_error( $result ) ) {
            $result['redirect'] = $this->get_redirect_url();
        }

        $this->maybe_send_result( $result );
    }


    /**
     * Handle the fallback login flow using email and password.
     *
     * @since 1.0.0
     * @return void
     */
    public function handle_password_login() {
        $this->verify_request();

        $result = $this->auth_flow->login_with_password(
            sanitize_text_field( wp_unslash( $_POST['identifier'] ?? '' ) ),
            (string) wp_unslash( $_POST['password'] ?? '' ),
            ! empty( $_POST['remember'] )
        );

        if ( ! is_wp_error( $result ) ) {
            $result['redirect'] = $this->get_redirect_url();
        }

        $this->maybe_send_result( $result );
    }


    /**
     * Handle AJAX account creation and persist the submitted phone metadata.
     *
     * @since 1.0.0
     * @return void
     */
    public function handle_register_user() {
        $this->verify_request();

        $result = $this->auth_flow->register_user(
            array(
                'username' => sanitize_text_field( wp_unslash( $_POST['username'] ?? '' ) ),
                'email' => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
                'password' => (string) wp_unslash( $_POST['password'] ?? '' ),
                'phone' => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
            )
        );

        if ( ! is_wp_error( $result ) ) {
            $result['redirect'] = $this->get_redirect_url();
        }

        $this->maybe_send_result( $result );
    }


    /**
     * Validate the shared nonce sent by the frontend application.
     *
     * @since 1.0.0
     * @return void
     */
    private function verify_request() {
        check_ajax_referer( 'joinotify_otp_login_nonce', 'nonce' );
    }


    /**
     * Send a successful or error JSON response based on the service output.
     *
     * @since 1.0.0
     * @param array|\WP_Error $result Service response payload.
     * @return void
     */
    private function maybe_send_result( $result ) {
        if ( is_wp_error( $result ) ) {
            wp_send_json_error(
                array(
                    'message' => $result->get_error_message(),
                    'code' => $result->get_error_code(),
                )
            );
        }

        wp_send_json_success( $result );
    }


    /**
     * Resolve the redirect URL returned after a successful authentication event.
     *
     * @since 1.0.0
     * @return string Redirect URL.
     */
    private function get_redirect_url() {
        $redirect = esc_url_raw( wp_unslash( $_POST['redirect'] ?? '' ) );

        if ( ! empty( $redirect ) ) {
            return $redirect;
        }

        if ( function_exists( 'wc_get_page_permalink' ) ) {
            return wc_get_page_permalink( 'myaccount' );
        }

        return home_url( '/' );
    }
}
