<?php

namespace MeuMouse\Joinotify\Otp_Login\Views;

defined('ABSPATH') || exit;

/**
 * Small template renderer for plugin partials.
 *
 * @since 1.0.0
 * @package MeuMouse\Joinotify\Otp_Login\Views
 * @author MeuMouse.com
 */
class Templates {

    /**
     * Render a plugin template file with scoped arguments.
     *
     * @since 1.0.0
     * @param string               $template Relative template path.
     * @param array<string,mixed>  $args Optional variables exposed to the template.
     * @return void
     */
    public static function render( $template, array $args = array() ) {
        $file = trailingslashit( JOINOTIFY_OTP_LOGIN_DIR ) . 'templates/' . ltrim( $template, '/' );

        if ( ! file_exists( $file ) ) {
            return;
        }

        extract( $args, EXTR_SKIP );

        include $file;
    }
}