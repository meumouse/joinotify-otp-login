<?php

namespace MeuMouse\Joinotify\Otp_Login\Rest;

defined('ABSPATH') || exit;

/**
 * Bootstrap all REST route handlers for the OTP login flow.
 *
 * @since 1.0.0
 * @package MeuMouse\Joinotify\Otp_Login\Rest
 */
class Routes {

	/**
	 * Instantiate and register every REST route class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		$routes = array(
			Request_Code::class,
			Verify_Code::class,
			Password_Login::class,
		);

		foreach ( $routes as $route_class ) {
			if ( class_exists( $route_class ) ) {
				$route = new $route_class();
				$route->register_route();
			}
		}
	}
}
