<?php

namespace MeuMouse\Joinotify\Otp_Login\Support;

defined('ABSPATH') || exit;

/**
 * Utility helpers for phone normalization and UI masking.
 *
 * @since 1.0.0
 * @package MeuMouse\Joinotify\Otp_Login\Support
 * @author MeuMouse.com
 */
class Phone_Utils {

    /**
     * Normalize a phone number to a simplified E.164-like format.
     *
     * @since 1.0.0
     * @param string $phone Raw phone number.
     * @return string Normalized phone prefixed with a plus sign, or an empty string.
     */
    public static function normalize( $phone ) {
        $digits = preg_replace( '/\D+/', '', (string) $phone );

        if ( empty( $digits ) ) {
            return '';
        }

        return '+' . $digits;
    }


    /**
     * Strip all non-digit characters from a phone number.
     *
     * @since 1.0.0
     * @param string $phone Raw phone number.
     * @return string Digits-only phone representation.
     */
    public static function digits_only( $phone ) {
        return preg_replace( '/\D+/', '', (string) $phone );
    }


    /**
     * Mask part of a phone number for safer frontend display.
     *
     * @since 1.0.0
     * @param string $phone Raw or normalized phone number.
     * @return string Masked phone number.
     */
    public static function mask( $phone ) {
        $normalized = self::normalize( $phone );
        $digits = self::digits_only( $normalized );

        if ( strlen( $digits ) <= 4 ) {
            return $normalized;
        }

        $visible_prefix = substr( $digits, 0, 4 );
        $visible_suffix = substr( $digits, -2 );
        $masked = str_repeat( '*', max( strlen( $digits ) - 6, 2 ) );

        return '+' . $visible_prefix . $masked . $visible_suffix;
    }
}