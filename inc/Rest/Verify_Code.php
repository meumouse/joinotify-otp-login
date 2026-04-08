<?php

namespace MeuMouse\Joinotify\Otp_Login\Rest;

defined('ABSPATH') || exit;

/**
 * REST endpoint that validates an OTP code and authenticates the user.
 *
 * @since 1.0.0
 * @package MeuMouse\Joinotify\Otp_Login\Rest
 */
class Verify_Code extends Abstract_Route {

	/**
	 * Register the route on the REST API.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_route() {
		register_rest_route(
			'joinotify-otp-login/v1',
			'/verify-code',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle the verify-code route.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Current REST request.
	 * @return \WP_REST_Response|\WP_Error REST response object or error.
	 */
	public function handle( $request ) {
		if ( is_wp_error( $error = $this->verify_nonce( $request ) ) ) {
			return $error;
		}

		$result = $this->auth_flow->verify_otp_login(
			sanitize_text_field( $request->get_param( 'phone' ) ),
			sanitize_text_field( $request->get_param( 'otp' ) ),
			! empty( $request->get_param( 'remember' ) )
		);

		if ( ! is_wp_error( $result ) ) {
			$result['redirect'] = $this->get_redirect_url( $request );
		}

		return $this->to_rest_response( $result );
	}
}
