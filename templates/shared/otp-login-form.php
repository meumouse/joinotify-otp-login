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
$title = $title ?? __( 'Sign in with WhatsApp', 'joinotify-otp-login' );
$description = $description ?? '';
$otp_length = (int) apply_filters( 'Joinotify/Otp_Login/Otp_Length', 6 );
?>

<div
    class="joinotify-otp-login"
    data-joinotify-otp-login
    data-context="<?php echo esc_attr( $context ); ?>"
    data-otp-length="<?php echo esc_attr( $otp_length ); ?>"
    data-default-country="<?php echo esc_attr( apply_filters( 'Joinotify/Otp_Login/Default_Country', 'br' ) ); ?>"
    data-redirect-url="<?php echo esc_url( $redirect_url ); ?>"
>
    <div class="joinotify-otp-login__panel">
        <div class="joinotify-otp-login__header">
            <p class="joinotify-otp-login__eyebrow"><?php esc_html_e( 'Joinotify OTP Login', 'joinotify-otp-login' ); ?></p>
            <h2 class="joinotify-otp-login__title"><?php echo esc_html( $title ); ?></h2>
            <?php if ( ! empty( $description ) ) : ?>
                <p class="joinotify-otp-login__description"><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>
        </div>

        <div class="joinotify-otp-notice joinotify-otp-notice--info" data-login-notice hidden></div>

        <section class="joinotify-otp-step" data-login-step="phone">
            <form class="joinotify-otp-login__form joinotify-otp-login__form--phone" data-phone-form>
                <p class="form-row form-row-wide joinotify-phone-field">
                    <label for="joinotify-phone-<?php echo esc_attr( $context ); ?>">
                        <?php esc_html_e( 'Phone', 'joinotify-otp-login' ); ?>
                        <span class="required" aria-hidden="true">*</span>
                    </label>
                    <input
                        type="tel"
                        id="joinotify-phone-<?php echo esc_attr( $context ); ?>"
                        class="input-text joinotify-phone-field__visible"
                        data-phone-visible
                        autocomplete="tel"
                        inputmode="tel"
                        placeholder="<?php esc_attr_e( '55 11 99999-9999', 'joinotify-otp-login' ); ?>"
                    />
                    <input type="hidden" name="phone" data-phone-hidden />
                    <small class="joinotify-phone-field__helper">
                        <?php esc_html_e( 'Use the full phone number. We also accept matching numbers without DDI and DDD.', 'joinotify-otp-login' ); ?>
                    </small>
                </p>

                <div class="joinotify-otp-login__actions">
                    <button type="submit" class="button alt">
                        <?php esc_html_e( 'Receive code', 'joinotify-otp-login' ); ?>
                    </button>
                    <button type="button" class="button button-secondary" data-switch-step="password">
                        <?php esc_html_e( 'Use email and password', 'joinotify-otp-login' ); ?>
                    </button>
                </div>
            </form>
        </section>

        <section class="joinotify-otp-step" data-login-step="otp" hidden>
            <form class="joinotify-otp-login__form joinotify-otp-login__form--otp" data-otp-form novalidate>
                <input type="hidden" name="phone" data-phone-hidden />
                <input type="hidden" name="redirect" value="<?php echo esc_url( $redirect_url ); ?>" />
                <input type="hidden" name="otp" data-otp-hidden />

                <div class="joinotify-otp-login__otp-header">
                    <h3 class="joinotify-otp-login__subtitle"><?php esc_html_e( 'Enter the access code', 'joinotify-otp-login' ); ?></h3>
                    <p class="joinotify-otp-login__description">
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: %d: OTP length. */
                                __( 'Type the %d-digit code sent to your WhatsApp.', 'joinotify-otp-login' ),
                                $otp_length
                            )
                        );
                        ?>
                    </p>
                    <p class="joinotify-otp-login__phone-preview" data-otp-phone-preview></p>
                </div>

                <div class="joinotify-otp-code-grid" data-otp-group data-otp-length="<?php echo esc_attr( $otp_length ); ?>">
                    <?php for ( $i = 0; $i < $otp_length; $i++ ) : ?>
                        <input
                            type="text"
                            class="otp-input-item"
                            data-otp-digit
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="1"
                            aria-label="<?php echo esc_attr( sprintf( __( 'OTP digit %d', 'joinotify-otp-login' ), $i + 1 ) ); ?>"
                        />
                    <?php endfor; ?>
                </div>

                <div class="joinotify-otp-login__resend" data-resend-otp>
                    <span class="joinotify-otp-login__resend-label"><?php esc_html_e( 'Resend code in', 'joinotify-otp-login' ); ?></span>
                    <span class="joinotify-otp-login__resend-countdown countdown-otp-resend">60</span>
                    <span><?php esc_html_e( 'seconds', 'joinotify-otp-login' ); ?></span>
                </div>

                <div class="joinotify-otp-login__actions joinotify-otp-login__actions--stacked">
                    <button type="submit" class="button alt">
                        <?php esc_html_e( 'Verify code', 'joinotify-otp-login' ); ?>
                    </button>
                    <button type="button" class="button button-secondary" data-switch-step="phone">
                        <?php esc_html_e( 'Change phone number', 'joinotify-otp-login' ); ?>
                    </button>
                </div>
            </form>
        </section>

        <section class="joinotify-otp-step" data-login-step="password" hidden>
            <div class="joinotify-otp-login__separator"><?php esc_html_e( 'Sign in with email', 'joinotify-otp-login' ); ?></div>

            <form class="joinotify-otp-login__form joinotify-otp-login__form--password" data-password-form>
                <input type="hidden" name="redirect" value="<?php echo esc_url( $redirect_url ); ?>" />

                <p class="form-row form-row-wide">
                    <label for="joinotify-email-<?php echo esc_attr( $context ); ?>">
                        <?php esc_html_e( 'Email', 'joinotify-otp-login' ); ?>
                        <span class="required" aria-hidden="true">*</span>
                    </label>
                    <input type="email" id="joinotify-email-<?php echo esc_attr( $context ); ?>" class="input-text" name="email" autocomplete="email" />
                </p>

                <p class="form-row form-row-wide">
                    <label for="joinotify-password-<?php echo esc_attr( $context ); ?>">
                        <?php esc_html_e( 'Password', 'joinotify-otp-login' ); ?>
                        <span class="required" aria-hidden="true">*</span>
                    </label>
                    <input type="password" id="joinotify-password-<?php echo esc_attr( $context ); ?>" class="input-text" name="password" autocomplete="current-password" />
                </p>

                <p class="form-row">
                    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
                        <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="remember" type="checkbox" value="1" />
                        <span><?php esc_html_e( 'Remember me', 'joinotify-otp-login' ); ?></span>
                    </label>
                </p>

                <div class="joinotify-otp-login__actions">
                    <button type="submit" class="button alt"><?php esc_html_e( 'Sign in', 'joinotify-otp-login' ); ?></button>
                    <button type="button" class="button button-secondary" data-switch-step="phone">
                        <?php esc_html_e( 'Back to phone', 'joinotify-otp-login' ); ?>
                    </button>
                </div>

                <p class="woocommerce-LostPassword lost_password">
                    <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot your password?', 'joinotify-otp-login' ); ?></a>
                </p>
            </form>
        </section>
    </div>
</div>
