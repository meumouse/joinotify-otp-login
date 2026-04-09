<?php

namespace MeuMouse\Joinotify\Otp_Login\Integrations;

use MeuMouse\Joinotify\Otp_Login\Repositories\User_Repository;
use MeuMouse\Joinotify\Otp_Login\Support\Phone_Utils;

defined('ABSPATH') || exit;

/**
 * Registers WooCommerce-specific integration points.
 *
 * @since 1.0.0
 * @package MeuMouse\Joinotify\Otp_Login\Integrations
 * @author MeuMouse.com
 */
class Woocommerce {

    /**
     * User repository used to persist phone metadata.
     *
     * @since 1.0.0
     * @var User_Repository
     */
    private $users;

    /**
     * Register WooCommerce hooks.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        $this->users = new User_Repository();

        add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 10, 3 );
        add_action( 'woocommerce_edit_account_form', array( $this, 'render_account_phone_field' ), 5 );
        add_action( 'woocommerce_save_account_details', array( $this, 'save_account_phone_field' ), 20, 1 );
    }


    /**
     * Override WooCommerce login templates with the plugin versions when available.
     *
     * @since 1.0.0
     * @param string $template Located template path.
     * @param string $template_name Requested template name.
     * @param string $template_path Template base path provided by WooCommerce.
     * @return string Template path to load.
     */
    public function locate_template( $template, $template_name, $template_path ) {
        $supported_templates = array(
            'myaccount/form-login.php',
            'checkout/form-login.php',
        );

        if ( ! in_array( $template_name, $supported_templates, true ) ) {
            return $template;
        }

        $plugin_template = trailingslashit( JOINOTIFY_OTP_LOGIN_DIR ) . 'templates/' . $template_name;

        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }

        return $template;
    }


    /**
     * Render the phone field inside the WooCommerce account details form.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_account_phone_field() {
        if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'edit-account' ) ) {
            return;
        }

        $user_id = get_current_user_id();
        $current_phone = $user_id ? (string) get_user_meta( $user_id, 'joinotify_user_phone', true ) : '';

        if ( empty( $current_phone ) && $user_id ) {
            $current_phone = (string) get_user_meta( $user_id, 'billing_phone', true );
        }

        ?>
        <div
            id="joinotify-account-phone"
            data-default-country="<?php echo esc_attr( $this->get_default_country() ); ?>"
            data-initial-phone="<?php echo esc_attr( $current_phone ); ?>"
        ></div>
        <?php
    }


    /**
     * Save the account phone field when the customer updates their profile.
     *
     * @since 1.0.0
     * @param int $user_id Current user ID.
     * @return void
     */
    public function save_account_phone_field( $user_id ) {
        if ( ! $user_id ) {
            return;
        }

        $phone = isset( $_POST['account_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['account_phone'] ) ) : '';

        if ( empty( $phone ) ) {
            return;
        }

        $normalized = Phone_Utils::normalize( $phone );

        if ( empty( $normalized ) ) {
            return;
        }

        $this->users->save_phone( $user_id, $normalized );
    }


    /**
     * Resolve the WooCommerce base country for the phone field default.
     *
     * @since 1.0.0
     * @return string
     */
    private function get_default_country() {
        if ( function_exists( 'wc_get_base_location' ) ) {
            $base_location = wc_get_base_location();

            if ( ! empty( $base_location['country'] ) ) {
                return strtolower( (string) $base_location['country'] );
            }
        }

        return 'br';
    }
}
