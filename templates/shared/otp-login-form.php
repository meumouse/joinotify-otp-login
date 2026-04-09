<?php
/**
 * Shared OTP login form.
 *
 * @var string $context
 * @var string $redirect_url
 * @var string $title
 * @var string $description
 */

defined('ABSPATH') || exit;

$context = $context ?? 'myaccount';
$redirect_url = $redirect_url ?? home_url( '/' );
$title = $title ?? __( 'Log in with WhatsApp', 'joinotify-otp-login' );
$description = $description ?? '';
$show_header = isset( $show_header ) ? (bool) $show_header : true;
$otp_length = (int) apply_filters( 'Joinotify/Otp_Login/Otp_Length', 6 );
?>

<div
	class="joinotify-otp-login mx-auto w-full max-w-2xl"
	data-joinotify-otp-login
	data-context="<?php echo esc_attr( $context ); ?>"
	data-otp-length="<?php echo esc_attr( $otp_length ); ?>"
	data-default-country="<?php echo esc_attr( apply_filters( 'Joinotify/Otp_Login/Default_Country', 'br' ) ); ?>"
	data-redirect-url="<?php echo esc_url( $redirect_url ); ?>"
	data-show-header="<?php echo esc_attr( $show_header ? '1' : '0' ); ?>"
	data-title="<?php echo esc_attr( $title ); ?>"
	data-description="<?php echo esc_attr( $description ); ?>"
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
