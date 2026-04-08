<?php

namespace MeuMouse\Joinotify\Otp_Login\Integrations;

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
     * Register WooCommerce hooks.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 10, 3 );
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
}