<?php

defined('ABSPATH') || exit;

$primary_color = $primary_color ?? '#4f46e5';
$border_radius = isset( $border_radius ) ? (int) $border_radius : 6;
$palette = is_array( $palette ?? null ) ? $palette : array();
$senders = is_array( $senders ?? null ) ? $senders : array();
$selected_sender = $selected_sender ?? '';
?>

<div class="wrap joinotify-otp-settings">
	<h1><?php echo esc_html__( 'OTP Login Settings', 'joinotify-otp-login' ); ?></h1>

	<div class="joinotify-otp-settings__hero">
		<div class="joinotify-otp-settings__hero-copy">
			<p class="joinotify-otp-settings__eyebrow"><?php echo esc_html__( 'Highlighted theme colors', 'joinotify-otp-login' ); ?></p>
			<h2><?php echo esc_html__( 'Define the primary color, border radius, and OTP sender phone number.', 'joinotify-otp-login' ); ?></h2>
			<p><?php echo esc_html__( 'The palette is generated automatically from the base color to keep the login, buttons, and focus states visually consistent.', 'joinotify-otp-login' ); ?></p>
		</div>

		<div class="joinotify-otp-settings__hero-card">
			<div class="joinotify-otp-settings__preview" style="<?php echo esc_attr( '--joinotify-preview-color: ' . $primary_color . '; --joinotify-preview-radius: ' . (int) $border_radius . 'px;' ); ?>">
				<div class="joinotify-otp-settings__preview-header">
					<span class="joinotify-otp-settings__preview-dot"></span>
					<span><?php echo esc_html__( 'Hero preview', 'joinotify-otp-login' ); ?></span>
				</div>
				<div class="joinotify-otp-settings__preview-body">
					<strong><?php echo esc_html__( 'WhatsApp Login', 'joinotify-otp-login' ); ?></strong>
					<span><?php echo esc_html__( 'Buttons, focus states, and highlights follow your brand.', 'joinotify-otp-login' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<form method="post" action="options.php" class="joinotify-otp-settings__form">
		<?php settings_fields( \MeuMouse\Joinotify\Otp_Login\Admin\Settings_Page::OPTION_GROUP ); ?>

		<div class="joinotify-otp-settings__grid">
			<div class="joinotify-otp-settings__panel">
				<h3><?php echo esc_html__( 'Visual customization', 'joinotify-otp-login' ); ?></h3>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="joinotify_otp_login_primary_color"><?php echo esc_html__( 'Primary color', 'joinotify-otp-login' ); ?></label>
						</th>
						<td>
							<input type="color" id="joinotify_otp_login_primary_color" name="joinotify_otp_login_primary_color" value="<?php echo esc_attr( $primary_color ); ?>" />
							<p class="description"><?php echo esc_html__( 'This color automatically generates the full theme scale.', 'joinotify-otp-login' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="joinotify_otp_login_border_radius"><?php echo esc_html__( 'Border radius', 'joinotify-otp-login' ); ?></label>
						</th>
						<td>
							<input type="range" min="0" max="80" step="1" id="joinotify_otp_login_border_radius" name="joinotify_otp_login_border_radius" value="<?php echo esc_attr( $border_radius ); ?>" />
							<output for="joinotify_otp_login_border_radius" id="joinotify_otp_login_border_radius_output"><?php echo esc_html( $border_radius ); ?>px</output>
							<p class="description"><?php echo esc_html__( 'Control the rounding of cards and form fields.', 'joinotify-otp-login' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="joinotify-otp-settings__panel">
				<h3><?php echo esc_html__( 'Sender phone number', 'joinotify-otp-login' ); ?></h3>

				<?php if ( ! empty( $senders ) ) : ?>
					<p class="description"><?php echo esc_html__( 'Select which Joinotify number will be used to send OTP codes.', 'joinotify-otp-login' ); ?></p>
					<select name="joinotify_otp_login_sender_phone" class="regular-text">
						<?php foreach ( $senders as $sender ) : ?>
							<option value="<?php echo esc_attr( $sender ); ?>" <?php selected( $selected_sender, $sender ); ?>>
								<?php echo esc_html( $sender ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<div class="notice notice-warning inline">
						<p><?php echo esc_html__( 'No sender phone number was found in Joinotify. Add a number to enable OTP delivery.', 'joinotify-otp-login' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="joinotify-otp-settings__palette">
			<h3><?php echo esc_html__( 'Automatically generated scale', 'joinotify-otp-login' ); ?></h3>
			<div class="joinotify-otp-settings__palette-list">
				<?php foreach ( $palette as $token ) : ?>
					<div class="joinotify-otp-settings__palette-row">
						<span class="joinotify-otp-settings__palette-step"><?php echo esc_html( $token['step'] ); ?></span>
						<span class="joinotify-otp-settings__palette-swatch" style="<?php echo esc_attr( '--joinotify-swatch:' . $token['color'] ); ?>"></span>
						<input type="text" readonly value="<?php echo esc_attr( $token['color'] ); ?>" />
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<?php submit_button( esc_html__( 'Save settings', 'joinotify-otp-login' ) ); ?>
	</form>
</div>

<script>
(function() {
	var input = document.getElementById('joinotify_otp_login_border_radius');
	var output = document.getElementById('joinotify_otp_login_border_radius_output');

	if (!input || !output) {
		return;
	}

	var sync = function() {
		output.textContent = input.value + 'px';
	};

	input.addEventListener('input', sync);
	sync();
})();
</script>
