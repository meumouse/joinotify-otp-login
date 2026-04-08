<?php

namespace MeuMouse\Joinotify\Otp_Login\Core;

use MeuMouse\Joinotify\Otp_Login\Views\Templates;

defined('ABSPATH') || exit;

class Shortcodes {

    public function __construct() {
        add_shortcode( 'joinotify_otp_login', array( $this, 'render_shortcode' ) );
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'redirect' => '',
                'show_header' => '0',
                'title' => '',
                'description' => '',
            ),
            (array) $atts,
            'joinotify_otp_login'
        );

        $redirect_url = ! empty( $atts['redirect'] ) ? esc_url_raw( $atts['redirect'] ) : home_url( '/' );
        $show_header = in_array( strtolower( (string) $atts['show_header'] ), array( '1', 'true', 'yes' ), true );

        ob_start();

        Templates::render(
            'shared/otp-login-form.php',
            array(
                'context' => 'shortcode',
                'redirect_url' => $redirect_url,
                'title' => $atts['title'],
                'description' => $atts['description'],
                'show_header' => $show_header,
            )
        );

        return (string) ob_get_clean();
    }
}
