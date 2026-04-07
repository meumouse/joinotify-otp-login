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
?>

<div class="joinotify-otp-login" data-context="<?php echo esc_attr( $context ?? 'myaccount' ); ?>">
    <div class="joinotify-otp-login__header">
        <h2 class="joinotify-otp-login__title"><?php echo esc_html( $title ?? __( 'Entrar com WhatsApp', 'joinotify-otp-login' ) ); ?></h2>
        <?php if ( ! empty( $description ) ) : ?>
            <p class="joinotify-otp-login__description"><?php echo esc_html( $description ); ?></p>
        <?php endif; ?>
    </div>

    <div class="joinotify-otp-notice joinotify-otp-notice--info" data-login-notice hidden></div>

    <div data-login-step="phone">
        <form class="joinotify-otp-login__form" data-phone-form>
            <p class="form-row form-row-wide">
                <label for="joinotify-phone-<?php echo esc_attr( $context ?? 'default' ); ?>">
                    <?php esc_html_e( 'Telefone', 'joinotify-otp-login' ); ?>
                    <span class="required" aria-hidden="true">*</span>
                </label>
                <input type="tel" id="joinotify-phone-<?php echo esc_attr( $context ?? 'default' ); ?>" class="input-text" data-phone-visible autocomplete="tel" />
                <input type="hidden" name="phone" data-phone-hidden />
            </p>
            <p class="joinotify-otp-login__actions">
                <button type="submit" class="button alt"><?php esc_html_e( 'Receber codigo', 'joinotify-otp-login' ); ?></button>
                <a href="#" class="joinotify-otp-link" data-switch-step="password"><?php esc_html_e( 'Entrar com e-mail e senha', 'joinotify-otp-login' ); ?></a>
            </p>
        </form>
    </div>

    <div data-login-step="otp" hidden>
        <form class="joinotify-otp-login__form" data-otp-form>
            <input type="hidden" name="phone" data-phone-hidden />
            <input type="hidden" name="redirect" value="<?php echo esc_url( $redirect_url ?? home_url( '/' ) ); ?>" />

            <p class="form-row form-row-wide">
                <label for="joinotify-otp-code-<?php echo esc_attr( $context ?? 'default' ); ?>">
                    <?php esc_html_e( 'Codigo OTP', 'joinotify-otp-login' ); ?>
                    <span class="required" aria-hidden="true">*</span>
                </label>
                <input type="text" id="joinotify-otp-code-<?php echo esc_attr( $context ?? 'default' ); ?>" class="input-text" name="otp" inputmode="numeric" autocomplete="one-time-code" maxlength="6" />
            </p>

            <p class="form-row">
                <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
                    <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="remember" type="checkbox" value="1" />
                    <span><?php esc_html_e( 'Lembrar de mim', 'joinotify-otp-login' ); ?></span>
                </label>
            </p>

            <p class="joinotify-otp-login__actions">
                <button type="submit" class="button alt"><?php esc_html_e( 'Validar codigo', 'joinotify-otp-login' ); ?></button>
                <a href="#" class="joinotify-otp-link" data-switch-step="phone"><?php esc_html_e( 'Alterar telefone', 'joinotify-otp-login' ); ?></a>
            </p>
        </form>
    </div>

    <div data-login-step="password" hidden>
        <div class="joinotify-otp-login__separator"><?php esc_html_e( 'Login com e-mail', 'joinotify-otp-login' ); ?></div>

        <form class="joinotify-otp-login__form" data-password-form>
            <input type="hidden" name="redirect" value="<?php echo esc_url( $redirect_url ?? home_url( '/' ) ); ?>" />

            <p class="form-row form-row-wide">
                <label for="joinotify-email-<?php echo esc_attr( $context ?? 'default' ); ?>">
                    <?php esc_html_e( 'E-mail', 'joinotify-otp-login' ); ?>
                    <span class="required" aria-hidden="true">*</span>
                </label>
                <input type="email" id="joinotify-email-<?php echo esc_attr( $context ?? 'default' ); ?>" class="input-text" name="email" autocomplete="email" />
            </p>

            <p class="form-row form-row-wide">
                <label for="joinotify-password-<?php echo esc_attr( $context ?? 'default' ); ?>">
                    <?php esc_html_e( 'Senha', 'joinotify-otp-login' ); ?>
                    <span class="required" aria-hidden="true">*</span>
                </label>
                <input type="password" id="joinotify-password-<?php echo esc_attr( $context ?? 'default' ); ?>" class="input-text" name="password" autocomplete="current-password" />
            </p>

            <p class="form-row">
                <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
                    <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="remember" type="checkbox" value="1" />
                    <span><?php esc_html_e( 'Lembrar de mim', 'joinotify-otp-login' ); ?></span>
                </label>
            </p>

            <p class="joinotify-otp-login__actions">
                <button type="submit" class="button alt"><?php esc_html_e( 'Entrar', 'joinotify-otp-login' ); ?></button>
                <a href="#" class="joinotify-otp-link" data-switch-step="phone"><?php esc_html_e( 'Voltar para telefone', 'joinotify-otp-login' ); ?></a>
            </p>
        </form>

        <p class="woocommerce-LostPassword lost_password">
            <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Esqueceu sua senha?', 'joinotify-otp-login' ); ?></a>
        </p>
    </div>
</div>
