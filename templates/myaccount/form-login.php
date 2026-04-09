<?php
/**
 * Login Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-login.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

defined('ABSPATH') || exit;

use MeuMouse\Joinotify\Otp_Login\Views\Templates;

do_action( 'woocommerce_before_customer_login_form' ); ?>

<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>

<div class="u-columns col2-set" id="customer_login">

	<div class="u-column1 col-1">

<?php endif; ?>

		<h2><?php esc_html_e( 'Log in', 'joinotify-otp-login' ); ?></h2>

		<?php do_action( 'woocommerce_login_form_start' ); ?>

<?php
		Templates::render(
			'shared/otp-login-form.php',
			array(
				'context' => 'myaccount',
				'redirect_url' => wc_get_page_permalink( 'myaccount' ),
				'title' => __( 'Log in with WhatsApp', 'joinotify-otp-login' ),
				'description' => __( 'Use your phone number to receive a login code and continue.', 'joinotify-otp-login' ),
				'show_header' => true,
			)
		);
		?>

		<?php do_action( 'woocommerce_login_form' ); ?>
		<?php do_action( 'woocommerce_login_form_end' ); ?>

<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>

	</div>

	<div class="u-column2 col-2">

		<h2><?php esc_html_e( 'Register', 'joinotify-otp-login' ); ?></h2>

		<div class="joinotify-otp-register">
			<div class="joinotify-otp-notice joinotify-otp-notice--info" data-register-notice hidden></div>

			<form method="post" class="woocommerce-form woocommerce-form-register register joinotify-otp-register-form" <?php do_action( 'woocommerce_register_form_tag' ); ?> novalidate>

				<?php do_action( 'woocommerce_register_form_start' ); ?>

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>

					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_username"><?php esc_html_e( 'Username', 'joinotify-otp-login' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'joinotify-otp-login' ); ?></span></label>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" required aria-required="true" />
					</p>

				<?php endif; ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_email"><?php esc_html_e( 'Email', 'joinotify-otp-login' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'joinotify-otp-login' ); ?></span></label>
					<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" required aria-required="true" />
				</p>

				<?php
				Templates::render(
					'shared/intl-phone-field.php',
					array(
						'field_id' => 'reg_phone',
						'field_name' => 'phone',
						'label' => __( 'Phone number', 'joinotify-otp-login' ),
						'context' => 'myaccount-register',
						'helper' => __( 'Include the country code so the number is normalized correctly.', 'joinotify-otp-login' ),
					)
				);
				?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_password"><?php esc_html_e( 'Password', 'joinotify-otp-login' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'joinotify-otp-login' ); ?></span></label>
					<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
				</p>

				<input type="hidden" name="redirect" value="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" />

				<?php do_action( 'woocommerce_register_form' ); ?>

				<p class="woocommerce-form-row form-row">
					<button type="submit" class="woocommerce-Button woocommerce-button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Register', 'joinotify-otp-login' ); ?>"><?php esc_html_e( 'Register', 'joinotify-otp-login' ); ?></button>
				</p>

				<?php do_action( 'woocommerce_register_form_end' ); ?>

			</form>
		</div>

	</div>

</div>
<?php endif; ?>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
