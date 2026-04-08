<?php

namespace MeuMouse\Joinotify\Otp_Login\Support;

defined('ABSPATH') || exit;

class Color_Scheme {

    public static $steps = array( 0, 50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950 );

    public static function generate_palette( $hex_color ) {
        $hex_color = self::sanitize_hex( $hex_color );

        if ( empty( $hex_color ) ) {
            $hex_color = '#4f46e5';
        }

        $input_color = self::hex_to_rgb( $hex_color );
        $input_luminance = self::luminance( $input_color[0], $input_color[1], $input_color[2] );
        $lightest_color = array( 245, 245, 245 );
        $darkest_color = array( 8, 8, 8 );
        $lightest_luminance = self::luminance( 245, 245, 245 );
        $darkest_luminance = self::luminance( 8, 8, 8 );
        $luminance_range = $lightest_luminance - $darkest_luminance;
        $colors = array(
            array(
                'step' => '0',
                'color' => '#ffffff',
            ),
        );

        foreach ( self::$steps as $step ) {
            if ( 0 === (int) $step ) {
                continue;
            }

            $target_luminance = $lightest_luminance - ( $step / 1000 ) * $luminance_range;

            if ( $target_luminance > $input_luminance ) {
                $factor = ( $target_luminance - $input_luminance ) / max( $lightest_luminance - $input_luminance, 0.0001 );
                $result_color = self::interpolate_color( $input_color, $lightest_color, $factor );
            } else {
                $factor = ( $input_luminance - $target_luminance ) / max( $input_luminance - $darkest_luminance, 0.0001 );
                $result_color = self::interpolate_color( $input_color, $darkest_color, $factor );
            }

            $colors[] = array(
                'step' => (string) $step,
                'color' => self::rgb_to_hex( $result_color ),
            );
        }

        return $colors;
    }

    public static function sanitize_hex( $hex_color ) {
        $hex_color = sanitize_hex_color( $hex_color );

        return $hex_color ? $hex_color : '';
    }

    private static function hex_to_rgb( $hex_color ) {
        $hex_color = ltrim( (string) $hex_color, '#' );

        return array(
            hexdec( substr( $hex_color, 0, 2 ) ),
            hexdec( substr( $hex_color, 2, 2 ) ),
            hexdec( substr( $hex_color, 4, 2 ) ),
        );
    }

    private static function luminance( $r, $g, $b ) {
        $channels = array( $r, $g, $b );
        $adjusted = array_map(
            function( $value ) {
                $value = $value / 255;
                return $value <= 0.03928 ? $value / 12.92 : pow( ( $value + 0.055 ) / 1.055, 2.4 );
            },
            $channels
        );

        return $adjusted[0] * 0.2126 + $adjusted[1] * 0.7152 + $adjusted[2] * 0.0722;
    }

    private static function interpolate_color( array $color1, array $color2, $factor ) {
        $factor = max( 0, min( 1, (float) $factor ) );
        $result = array();

        foreach ( $color1 as $index => $channel ) {
            $result[] = (int) round( $channel + ( $color2[ $index ] - $channel ) * $factor );
        }

        return $result;
    }

    private static function rgb_to_hex( array $color ) {
        $color = array_map(
            function( $value ) {
                return max( 0, min( 255, (int) $value ) );
            },
            $color
        );

        return sprintf( '#%02x%02x%02x', $color[0], $color[1], $color[2] );
    }
}
