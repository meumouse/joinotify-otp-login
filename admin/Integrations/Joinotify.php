<?php

namespace MeuMouse\Joinotify\Otp_Login\Integrations;

use MeuMouse\Joinotify\Otp_Login\Support\Settings;

defined('ABSPATH') || exit;

/**
 * Joinotify builder integration for OTP login triggers and settings modal.
 *
 * @since 1.0.0
 * @package MeuMouse\Joinotify\Otp_Login\Integrations
 */
class Joinotify {

	/**
	 * Register Joinotify hooks for OTP login.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_filter( 'Joinotify/Settings/Tabs/Integrations', array( $this, 'add_integration_item' ), 20, 1 );
		add_filter( 'Joinotify/Otp_Validation/Sender', array( $this, 'filter_sender' ), 10, 1 );
		add_action( 'Joinotify/Settings/Tabs/Integrations/Otp_Login', array( $this, 'render_settings_modal' ) );
	}

	/**
	 * Register the OTP login card in the Joinotify integrations tab.
	 *
	 * @since 1.0.0
	 * @param array $integrations Existing integrations list.
	 * @return array
	 */
	public function add_integration_item( $integrations ) {
		$integrations['otp_login'] = array(
			'title' => esc_html__( 'OTP Login - Passwordless authentication', 'joinotify-otp-login' ),
			'description' => esc_html__( 'Let your users log in securely with a verification code sent via WhatsApp through Joinotify, offering a fast passwordless experience.', 'joinotify-otp-login' ),
			'icon' => '<svg fill="#000000" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round"></g><g><g><g> <path d="M469.779,145.37H42.221C18.941,145.37,0,164.31,0,187.591v85.511c0,23.281,18.941,42.221,42.221,42.221H332.96 c4.427,0,8.017-3.589,8.017-8.017s-3.589-8.017-8.017-8.017H42.221c-14.44,0-26.188-11.748-26.188-26.188v-85.511 c0-14.44,11.748-26.188,26.188-26.188h427.557c14.44,0,26.188,11.748,26.188,26.188v42.756c0,4.427,3.589,8.017,8.017,8.017 c4.427,0,8.017-3.589,8.017-8.017v-42.756C512,164.31,493.059,145.37,469.779,145.37z"></path> </g> </g> <g> <g> <path d="M477.795,249.302v-18.956c0-27.995-22.777-50.772-50.772-50.772c-27.995,0-50.772,22.777-50.772,50.772v18.956 c-9.93,3.354-17.102,12.752-17.102,23.8v25.653c0,37.426,30.448,67.875,67.875,67.875c37.426,0,67.875-30.448,67.875-67.875 v-25.653C494.898,262.054,487.725,252.656,477.795,249.302z M392.284,230.347c0-19.155,15.584-34.739,34.739-34.739 c19.155,0,34.739,15.584,34.739,34.739v17.637h-69.478V230.347z M478.864,298.756c0,28.585-23.256,51.841-51.841,51.841 c-28.585,0-51.841-23.256-51.841-51.841v-25.653c0-5.01,4.076-9.086,9.086-9.086h85.511c5.01,0,9.086,4.076,9.086,9.086V298.756z"></path> </g> </g> <g> <g> <path d="M427.023,282.188c-9.136,0-16.568,7.432-16.568,16.568c0,6.228,3.458,11.659,8.551,14.489v5.553 c0,4.427,3.589,8.017,8.017,8.017c4.427,0,8.017-3.589,8.017-8.017v-5.553c5.093-2.829,8.551-8.26,8.551-14.489 C443.591,289.62,436.159,282.188,427.023,282.188z"></path> </g> </g> <g> <g> <path d="M238.324,240.506l-17.597-10.16l17.597-10.16c3.834-2.214,5.148-7.117,2.934-10.951 c-2.214-3.835-7.117-5.149-10.951-2.934l-17.597,10.16v-20.32c0-4.427-3.589-8.017-8.017-8.017s-8.017,3.589-8.017,8.017v20.32 l-17.597-10.16c-3.835-2.215-8.737-0.9-10.951,2.934s-0.9,8.737,2.934,10.951l17.597,10.16l-17.597,10.16 c-3.834,2.214-5.148,7.117-2.934,10.951c1.485,2.572,4.179,4.009,6.95,4.009c1.36,0,2.738-0.346,4.001-1.075l17.597-10.16v20.32 c0,4.427,3.589,8.017,8.017,8.017s8.017-3.589,8.017-8.017v-20.32l17.597,10.16c1.262,0.729,2.641,1.075,4.001,1.075 c2.771,0,5.465-1.439,6.95-4.009C243.471,247.623,242.158,242.72,238.324,240.506z"></path> </g> </g> <g> <g> <path d="M135.71,240.506l-17.597-10.16l17.597-10.16c3.834-2.214,5.148-7.117,2.934-10.951c-2.214-3.835-7.117-5.149-10.951-2.934 l-17.597,10.16v-20.32c0-4.427-3.589-8.017-8.017-8.017s-8.017,3.589-8.017,8.017v20.32l-17.597-10.16 c-3.835-2.215-8.737-0.9-10.951,2.934s-0.9,8.737,2.934,10.951l17.597,10.16l-17.597,10.16c-3.834,2.214-5.148,7.117-2.934,10.951 c1.485,2.572,4.179,4.009,6.95,4.009c1.36,0,2.738-0.346,4.001-1.075l17.597-10.16v20.32c0,4.427,3.589,8.017,8.017,8.017 s8.017-3.589,8.017-8.017v-20.32l17.597,10.16c1.262,0.729,2.641,1.075,4.001,1.075c2.771,0,5.465-1.439,6.95-4.009 C140.858,247.623,139.544,242.72,135.71,240.506z"></path> </g> </g> <g> <g> <path d="M340.938,240.506l-17.597-10.16l17.597-10.16c3.834-2.214,5.148-7.117,2.934-10.951 c-2.214-3.835-7.117-5.149-10.951-2.934l-17.597,10.16v-20.32c0-4.427-3.589-8.017-8.017-8.017s-8.017,3.589-8.017,8.017v20.32 l-17.597-10.16c-3.835-2.215-8.737-0.9-10.951,2.934s-0.9,8.737,2.934,10.951l17.597,10.16l-17.597,10.16 c-3.834,2.214-5.148,7.117-2.934,10.951c1.485,2.572,4.179,4.009,6.95,4.009c1.36,0,2.739-0.346,4.001-1.075l17.597-10.16v20.32 c0,4.427,3.589,8.017,8.017,8.017s8.017-3.589,8.017-8.017v-20.32l17.597,10.16c1.262,0.729,2.641,1.075,4.001,1.075 c2.771,0,5.465-1.439,6.95-4.009C346.085,247.623,344.772,242.72,340.938,240.506z"></path> </g> </g> </g></svg>',
			'setting_key' => 'enable_otp_login_integration',
			'action_hook' => 'Joinotify/Settings/Tabs/Integrations/Otp_Login',
		);

		return $integrations;
	}

	/**
	 * Prefer the sender selected in the OTP Login modal.
	 *
	 * @since 1.0.0
	 * @param string $sender | Sender value provided by the upstream filter.
	 * @return string
	 */
	public function filter_sender( $sender ) {
		$selected_sender = Settings::get_selected_sender();

		if ( ! empty( $selected_sender ) ) {
			return $selected_sender;
		}

		return $sender;
	}

	/**
	 * Render the modal content attached to the OTP Login integration card.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_modal() {
		$enabled = Settings::is_enabled();
		$primary_color = Settings::get_primary_color();
		$border_radius_value = Settings::get_border_radius_value();
		$border_radius_unit = Settings::get_border_radius_unit();
		$border_radius = Settings::get_border_radius();
		$palette_rows = Settings::get_palette_rows();
		$senders = Settings::get_available_senders();
		$stored_sender = Settings::get_sender_phone();
		$selected_sender = Settings::get_selected_sender();
		$modal_id = 'joinotify-otp-login-settings-modal'; ?>

		<?php wp_enqueue_script(
			'joinotify-otp-login-admin-settings',
			JOINOTIFY_OTP_LOGIN_ASSETS . 'js/admin-settings.js',
			array( 'jquery' ),
			JOINOTIFY_OTP_LOGIN_VERSION,
			true
		); ?>

		<button id="joinotify_otp_login_settings_trigger" type="button" class="btn btn-outline-primary mb-5" ><?php esc_html_e( 'Configure', 'joinotify-otp-login' ); ?></button>

		<div id="<?php echo esc_attr( $modal_id ); ?>" class="joinotify-popup-container">
			<div class="joinotify-popup-content popup-lg">
				<div class="joinotify-popup-header">
					<h5 class="joinotify-popup-title"><?php esc_html_e( 'OTP Login integration settings', 'joinotify-otp-login' ); ?></h5>
					<button id="joinotify_otp_login_settings_close" class="btn-close fs-lg" aria-label="<?php esc_attr_e( 'Fechar', 'joinotify-otp-login' ); ?>"></button>
				</div>

				<div class="joinotify-popup-body my-3">
					<table class="popup-table">
						<tbody>
							<tr>
								<th>
									<?php esc_html_e( 'Enable OTP login form display', 'joinotify-otp-login' ); ?>
									<span class="joinotify-description"><?php esc_html_e( 'Enable this option to show the OTP login form on Joinotify-integrated screens.', 'joinotify-otp-login' ); ?></span>
								</th>
								<td class="d-flex align-items-center">
									<div class="form-check form-switch">
										<input type="checkbox" class="toggle-switch" id="enable_otp_login_integration" name="joinotify_settings[enable_otp_login_integration]" value="yes" <?php checked( $enabled ); ?>>
									</div>
								</td>
							</tr>

							<tr>
								<th>
									<?php esc_html_e( 'Color palette', 'joinotify-otp-login' ); ?>
									<span class="joinotify-description"><?php esc_html_e( 'The primary color generates the base palette. You can manually adjust each saved shade.', 'joinotify-otp-login' ); ?></span>
								</th>
								<td>
									<div class="d-flex align-items-center gap-3 mb-3">
										<span class="badge bg-secondary"><?php echo esc_html( $primary_color ); ?></span>
										<span class="badge bg-secondary"><?php echo esc_html( $border_radius ); ?></span>
									</div>

									<div class="d-grid gap-3" data-joinotify-otp-login-palette>
										<div class="d-flex align-items-center gap-3 flex-wrap">
											<span class="joinotify-description mb-0 flex-shrink-0"><?php esc_html_e( 'Base color', 'joinotify-otp-login' ); ?></span>
											<input
												type="text"
												class="form-control"
												id="otp_login_primary_color"
												name="joinotify_settings[otp_login_primary_color]"
												value="<?php echo esc_attr( $primary_color ); ?>"
												placeholder="#4f46e5"
												inputmode="text"
												pattern="^#?[0-9a-fA-F]{6}$"
												autocomplete="off"
												data-joinotify-otp-login-primary-text
											/>
											<input
												type="color"
												class="form-control form-control-color"
												value="<?php echo esc_attr( $primary_color ); ?>"
												data-joinotify-otp-login-primary-picker
												aria-label="<?php esc_attr_e( 'Select primary color', 'joinotify-otp-login' ); ?>"
											/>
										</div>

										<?php foreach ( $palette_rows as $row ) : ?>
											<div class="d-flex align-items-center gap-3 flex-wrap" data-joinotify-otp-login-palette-row data-step="<?php echo esc_attr( $row['step'] ); ?>">
												<span class="joinotify-description mb-0 flex-shrink-0">
													<?php echo esc_html( '0' === $row['step'] ? __( 'Base', 'joinotify-otp-login' ) : sprintf( __( 'Shade %s', 'joinotify-otp-login' ), $row['step'] ) ); ?>
												</span>
												<input
													type="color"
													class="form-control form-control-color"
													id="otp_login_palette_<?php echo esc_attr( $row['step'] ); ?>"
													name="joinotify_settings[otp_login_palette][<?php echo esc_attr( $row['step'] ); ?>]"
													value="<?php echo esc_attr( $row['color'] ); ?>"
													data-joinotify-otp-login-palette-picker
												/>
												<input type="text" class="form-control form-control-sm" value="<?php echo esc_attr( $row['color'] ); ?>" readonly data-joinotify-otp-login-palette-text>
											</div>
										<?php endforeach; ?>
									</div>
								</td>
							</tr>

							<tr>
								<th>
									<?php esc_html_e( 'Element border radius', 'joinotify-otp-login' ); ?>
									<span class="joinotify-description"><?php esc_html_e( 'Defines the rounding applied to fields, buttons, and other login elements.', 'joinotify-otp-login' ); ?></span>
								</th>
								<td>
									<div class="d-flex align-items-center gap-3 flex-wrap">
										<input
											type="number"
											class="form-control"
											min="0"
											step="0.001"
											id="otp_login_border_radius_value"
											name="joinotify_settings[otp_login_border_radius_value]"
											value="<?php echo esc_attr( $border_radius_value ); ?>"
										/>
										<select class="form-select" id="otp_login_border_radius_unit" name="joinotify_settings[otp_login_border_radius_unit]">
											<option value="px" <?php selected( $border_radius_unit, 'px' ); ?>><?php esc_html_e( 'Pixel', 'joinotify-otp-login' ); ?></option>
											<option value="em" <?php selected( $border_radius_unit, 'em' ); ?>><?php esc_html_e( 'EM', 'joinotify-otp-login' ); ?></option>
											<option value="rem" <?php selected( $border_radius_unit, 'rem' ); ?>><?php esc_html_e( 'REM', 'joinotify-otp-login' ); ?></option>
											<option value="%" <?php selected( $border_radius_unit, '%' ); ?>><?php esc_html_e( '%', 'joinotify-otp-login' ); ?></option>
										</select>
										<strong id="otp_login_border_radius_output"><?php echo esc_html( $border_radius ); ?></strong>
									</div>
								</td>
							</tr>

							<tr>
								<th>
									<?php esc_html_e( 'Select sender phone', 'joinotify-otp-login' ); ?>
									<span class="joinotify-description"><?php esc_html_e( 'Choose a registered Joinotify number to send OTPs.', 'joinotify-otp-login' ); ?></span>
								</th>
								<td>
									<select class="form-select" id="otp_login_sender_phone" name="joinotify_settings[otp_login_sender_phone]">
										<option value=""><?php esc_html_e( 'Use the first available sender', 'joinotify-otp-login' ); ?></option>
										<?php foreach ( $senders as $sender ) : ?>
											<option value="<?php echo esc_attr( $sender ); ?>" <?php selected( $stored_sender, $sender ); ?>>
												<?php echo esc_html( $sender ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<?php if ( ! empty( $selected_sender ) ) : ?>
										<p class="joinotify-description mt-2 mb-0">
											<?php echo esc_html( sprintf( __( 'Current sender: %s', 'joinotify-otp-login' ), $selected_sender ) ); ?>
										</p>
									<?php endif; ?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php
	}
}
