<?php

namespace MeuMouse\Joinotify\Otp_Login\Rest;

defined('ABSPATH') || exit;

/**
 * REST endpoint that authenticates users using email and password.
 *
 * @since 1.0.0
 * @package MeuMouse\Joinotify\Otp_Login\Rest
 */
class Password_Login extends Abstract_Route {

	/**
	 * Register the route on the REST API.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_route() {
		register_rest_route(
			'joinotify-otp-login/v1',
			'/password-login',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle the password-login route.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Current REST request.
	 * @return \WP_REST_Response|\WP_Error REST response object or error.
	 */
	public function handle( $request ) {
		if ( is_wp_error( $error = $this->verify_nonce( $request ) ) ) {
			return $error;
		}

		$result = $this->auth_flow->login_with_password(
			sanitize_text_field( $request->get_param( 'identifier' ) ),
			(string) $request->get_param( 'password' ),
			! empty( $request->get_param( 'remember' ) )
		);

		if ( ! is_wp_error( $result ) ) {
			$result['redirect'] = $this->get_redirect_url( $request );
		}

		return $this->to_rest_response( $result );
	}
}
