<?php
/**
 * Reusable international phone field.
 *
 * @var string $field_id
 * @var string $field_name
 * @var string $label
 * @var string $context
 * @var string $helper
 */

defined('ABSPATH') || exit;

$field_id = $field_id ?? 'joinotify-phone';
$field_name = $field_name ?? 'phone';
$label = $label ?? __( 'Phone', 'joinotify-otp-login' );
$context = $context ?? 'default';
$helper = $helper ?? '';
?>

<p class="form-row form-row-wide joinotify-phone-field">
    <label for="<?php echo esc_attr( $field_id ); ?>">
        <?php echo esc_html( $label ); ?>
        <span class="required" aria-hidden="true">*</span>
    </label>
    <input
        type="tel"
        class="input-text joinotify-phone-field__visible"
        id="<?php echo esc_attr( $field_id ); ?>"
        data-phone-visible
        autocomplete="tel"
        inputmode="tel"
    />
    <input type="hidden" name="<?php echo esc_attr( $field_name ); ?>" data-phone-hidden />

    <?php if ( ! empty( $helper ) ) : ?>
        <small class="joinotify-phone-field__helper"><?php echo esc_html( $helper ); ?></small>
    <?php endif; ?>
</p>
