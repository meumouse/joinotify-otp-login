<?php
/**
 * Checkout login form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-login.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.0.0
 */

defined('ABSPATH') || exit;

use MeuMouse\Joinotify\Otp_Login\Views\Templates;

$registration_at_checkout   = WC_Checkout::instance()->is_registration_enabled();
$login_reminder_at_checkout = 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' );

if ( is_user_logged_in() ) {
	return;
}

if ( $login_reminder_at_checkout ) : ?>
	<div class="woocommerce-form-login-toggle">
		<?php
		wc_print_notice(
			apply_filters( 'woocommerce_checkout_login_message', esc_html__( 'Already have an account?', 'joinotify-otp-login' ) ) .
			' <a href="#" class="showlogin">' . esc_html__( 'Click here to log in', 'joinotify-otp-login' ) . '</a>',
			'notice'
		);
		?>
	</div>
	<?php
endif;

if ( $registration_at_checkout || $login_reminder_at_checkout ) :
	Templates::render(
		'shared/otp-login-form.php',
		array(
			'context' => 'checkout',
			'redirect_url' => wc_get_checkout_url(),
			'title' => __( 'Log in to continue', 'joinotify-otp-login' ),
			'description' => __( 'Use your phone number to receive the code on WhatsApp or sign in with email and password.', 'joinotify-otp-login' ),
			'show_header' => true,
		)
	);
endif;
