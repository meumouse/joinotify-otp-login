<?php

namespace MeuMouse\Joinotify\Otp_Login\Rest;

use MeuMouse\Joinotify\Otp_Login\Services\Auth_Flow_Service;

defined('ABSPATH') || exit;

/**
 * Shared helpers for Joinotify REST routes.
 *
 * @since 1.0.0
 * @package MeuMouse\Joinotify\Otp_Login\Rest
 */
abstract class Abstract_Route {

	/**
	 * Authentication flow service.
	 *
	 * @since 1.0.0
	 * @var Auth_Flow_Service
	 */
	protected $auth_flow;

	/**
	 * Build the route with the shared authentication service.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		$this->auth_flow = new Auth_Flow_Service();
	}

	/**
	 * Validate the WordPress REST nonce sent by the frontend.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Current REST request.
	 * @return true|\WP_Error Returns true when the nonce is valid or a WP_Error otherwise.
	 */
	protected function verify_nonce( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			$error = new \WP_Error( 'rest_forbidden', __( 'Invalid request nonce.', 'joinotify-otp-login' ) );
			$error->add_data( array( 'status' => 403 ) );

			return $error;
		}

		return true;
	}

	/**
	 * Transform a service result into a standard REST response.
	 *
	 * @since 1.0.0
	 * @param array|\WP_Error $result Service response payload.
	 * @return \WP_REST_Response REST response wrapper.
	 */
	protected function to_rest_response( $result ) {
		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'data' => array(
						'message' => $result->get_error_message(),
						'code' => $result->get_error_code(),
					),
				),
				200
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data' => $result,
			),
			200
		);
	}

	/**
	 * Resolve the redirect URL after a successful authentication event.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Current REST request.
	 * @return string Redirect URL.
	 */
	protected function get_redirect_url( $request ) {
		$redirect = esc_url_raw( $request->get_param( 'redirect' ) );

		if ( ! empty( $redirect ) ) {
			return $redirect;
		}

		if ( function_exists( 'wc_get_page_permalink' ) ) {
			return wc_get_page_permalink( 'myaccount' );
		}

		return home_url( '/' );
	}
}
