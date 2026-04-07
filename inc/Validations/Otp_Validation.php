<?php

namespace MeuMouse\Joinotify\Otp_Login\Validations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handles OTP generation, storage and validation.
 * 
 * @since 1.0.0
 * @package MeuMouse\Joinotify\Otp_Login\Validations
 * @author MeuMouse.com
 */
class Otp_Validation {

    /**
     * OTP code length.
     *
     * @since 1.0.0
     * @var int
     */
    public $otp_length;

    /**
     * OTP expiration time in seconds.
     *
     * @since 1.0.0
     * @var int
     */
    public $otp_expiry_time;

    /**
     * Base transient key used to store OTP payloads.
     *
     * @since 1.0.0
     * @var string
     */
    const TRANSIENT_KEY = 'joinotify_otp_login_';

    /**
     * Initialize OTP defaults.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        $this->otp_length = 4;
        $this->otp_expiry_time = 300;
    }


    /**
     * Generate, store and send an OTP message to the provided WhatsApp number.
     *
     * @since 1.0.0
     * @param string $phone | Raw or normalized phone number.
     * @return bool True when the OTP was sent successfully, false otherwise.
     */
    public function generate_and_send_otp( $phone ) {
        $phone = preg_replace( '/\s+/', '', (string) $phone );
        $otp = $this->generate_otp();
        $expiration_time = time() + (int) $this->otp_expiry_time;

        $this->store_otp( $phone, $otp, $expiration_time );

        $sender = apply_filters( 'Joinotify/Otp_Validation/Sender', joinotify_get_first_sender() );
        $message = $this->set_message( $otp );
        $send_otp = joinotify_send_whatsapp_message_text( $sender, $phone, $message );

        return true === $send_otp || 201 === $send_otp || '201' === $send_otp;
    }


    /**
     * Build the WhatsApp message body that contains the OTP code.
     *
     * @since 1.0.0
     * @param string $otp | Generated OTP code.
     * @return string Message body.
     */
    public function set_message( $otp ) {
        $message = sprintf(
            __( 'Seu codigo de acesso e: %s. Este codigo expira em 5 minutos.', 'joinotify-otp-login' ),
            $otp
        );

        return apply_filters( 'Joinotify/Otp_Validation/Message', $message, $otp );
    }


    /**
     * Generate a random numeric OTP string.
     *
     * @since 1.0.0
     * @return string Generated OTP code.
     */
    public function generate_otp() {
        $max = (int) str_repeat( '9', $this->otp_length );

        return str_pad( (string) random_int( 0, $max ), $this->otp_length, '0', STR_PAD_LEFT );
    }


    /**
     * Store the OTP payload in a transient for later validation.
     *
     * @since 1.0.0
     * @param string $phone Normalized phone number.
     * @param string $otp Generated OTP code.
     * @param int    $expiration_time Unix timestamp for expiration.
     * @return void
     */
    public function store_otp( $phone, $otp, $expiration_time ) {
        set_transient(
            self::TRANSIENT_KEY . md5( $phone ),
            array(
                'otp' => $otp,
                'expires' => $expiration_time,
            ),
            $this->otp_expiry_time
        );
    }


    /**
     * Validate the submitted OTP against the stored transient payload.
     *
     * @since 1.0.0
     * @param string $phone Normalized phone number.
     * @param string $user_provided_otp Submitted OTP code.
     * @return bool True when the OTP is valid and not expired.
     */
    public function validate_otp( $phone, $user_provided_otp ) {
        $stored_data = get_transient( self::TRANSIENT_KEY . md5( $phone ) );

        if ( $stored_data && $stored_data['otp'] === $user_provided_otp ) {
            if ( time() <= $stored_data['expires'] ) {
                delete_transient( self::TRANSIENT_KEY . md5( $phone ) );

                return true;
            }

            delete_transient( self::TRANSIENT_KEY . md5( $phone ) );
        }

        return false;
    }
}