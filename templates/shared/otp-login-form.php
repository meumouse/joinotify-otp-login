<?php
/**
 * Shared OTP login form.
 *
 * @var string $context
 * @var string $redirect_url
 * @var string $title
 * @var string $description
 * @var string $root_class
 */

defined('ABSPATH') || exit;

$context = $context ?? 'myaccount';
$redirect_url = $redirect_url ?? home_url( '/' );
$title = $title ?? __( 'Log in with WhatsApp', 'joinotify-otp-login' );
$description = $description ?? '';
$show_header = isset( $show_header ) ? (bool) $show_header : true;
$hide_until_modal = 'checkout' === $context;
$root_class = trim( (string) ( $root_class ?? '' ) );
$root_class = $root_class ? ' ' . $root_class : '';
$otp_length = (int) apply_filters( 'Joinotify/Otp_Login/Otp_Length', 6 );
$i18n = array(
	'Secure access' => __( 'Secure access', 'joinotify-otp-login' ),
	'Log in with WhatsApp' => __( 'Log in with WhatsApp', 'joinotify-otp-login' ),
	'Enter a valid phone number. The country code will be detected automatically.' => __( 'Enter a valid phone number. The country code will be detected automatically.', 'joinotify-otp-login' ),
	'Phone number' => __( 'Phone number', 'joinotify-otp-login' ),
	'Sending...' => __( 'Sending...', 'joinotify-otp-login' ),
	'Use email and password' => __( 'Use email and password', 'joinotify-otp-login' ),
	'Enter the access code' => __( 'Enter the access code', 'joinotify-otp-login' ),
	'Enter the %d-digit code sent to your WhatsApp.' => __( 'Enter the %d-digit code sent to your WhatsApp.', 'joinotify-otp-login' ),
	'Code digit %d' => __( 'Code digit %d', 'joinotify-otp-login' ),
	'Remember me' => __( 'Remember me', 'joinotify-otp-login' ),
	'Resend code in' => __( 'Resend code in', 'joinotify-otp-login' ),
	'seconds' => __( 'seconds', 'joinotify-otp-login' ),
	'Resend code' => __( 'Resend code', 'joinotify-otp-login' ),
	'Verifying...' => __( 'Verifying...', 'joinotify-otp-login' ),
	'Change number' => __( 'Change number', 'joinotify-otp-login' ),
	'Or sign in with email' => __( 'Or sign in with email', 'joinotify-otp-login' ),
	'Email or username' => __( 'Email or username', 'joinotify-otp-login' ),
	'Enter your email or username' => __( 'Enter your email or username', 'joinotify-otp-login' ),
	'Password' => __( 'Password', 'joinotify-otp-login' ),
	'Enter your password' => __( 'Enter your password', 'joinotify-otp-login' ),
	'Hide password' => __( 'Hide password', 'joinotify-otp-login' ),
	'Show password' => __( 'Show password', 'joinotify-otp-login' ),
	'Forgot your password?' => __( 'Forgot your password?', 'joinotify-otp-login' ),
	'Sign in' => __( 'Sign in', 'joinotify-otp-login' ),
	'Back to WhatsApp' => __( 'Back to WhatsApp', 'joinotify-otp-login' ),
	'Please enter a number' => __( 'Please enter a number', 'joinotify-otp-login' ),
	'Invalid number' => __( 'Invalid number', 'joinotify-otp-login' ),
	'Invalid country code' => __( 'Invalid country code', 'joinotify-otp-login' ),
	'Too short' => __( 'Too short', 'joinotify-otp-login' ),
	'Too long' => __( 'Too long', 'joinotify-otp-login' ),
	'Enter a valid phone number with country code.' => __( 'Enter a valid phone number with country code.', 'joinotify-otp-login' ),
	'We could not complete the request right now. Please try again.' => __( 'We could not complete the request right now. Please try again.', 'joinotify-otp-login' ),
	'Enter the verification code you received.' => __( 'Enter the verification code you received.', 'joinotify-otp-login' ),
	'Fill in the email or username and password.' => __( 'Fill in the email or username and password.', 'joinotify-otp-login' ),
);
?>

<div
	class="joinotify-otp-login mx-auto w-full max-w-2xl<?php echo esc_attr( $root_class ); ?>"
	data-joinotify-otp-login
	data-hidden-until-modal="<?php echo esc_attr( $hide_until_modal ? '1' : '0' ); ?>"
	data-context="<?php echo esc_attr( $context ); ?>"
	data-otp-length="<?php echo esc_attr( $otp_length ); ?>"
	data-default-country="<?php echo esc_attr( apply_filters( 'Joinotify/Otp_Login/Default_Country', 'br' ) ); ?>"
	data-redirect-url="<?php echo esc_url( $redirect_url ); ?>"
	data-show-header="<?php echo esc_attr( $show_header ? '1' : '0' ); ?>"
	data-title="<?php echo esc_attr( $title ); ?>"
	data-description="<?php echo esc_attr( $description ); ?>"
	data-i18n="<?php echo esc_attr( wp_json_encode( $i18n ) ); ?>"
	style="<?php echo esc_attr( $hide_until_modal ? 'display:none;' : '' ); ?>"
>
	<noscript>
		<div class="rounded-[0.375rem] border border-slate-200 bg-white p-6 text-slate-700 shadow-[0_24px_80px_rgba(15,23,42,0.12)]">
			<?php if ( $show_header ) : ?>
				<h2 class="text-2xl font-semibold tracking-tight text-slate-900">
					<?php echo esc_html( $title ); ?>
				</h2>
				<?php if ( ! empty( $description ) ) : ?>
					<p class="mt-2 text-sm leading-6 text-slate-500"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			<?php endif; ?>
			<p class="mt-4 text-sm text-slate-500">
				<?php esc_html_e( 'Enable JavaScript to use login with WhatsApp or email and password.', 'joinotify-otp-login' ); ?>
			</p>
		</div>
	</noscript>
</div>
