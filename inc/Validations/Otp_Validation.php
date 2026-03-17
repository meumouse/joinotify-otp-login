<?php

namespace MeuMouse\Joinotify\Otp_Login\Validations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Class for handler with OTP validations
 * 
 * @since 1.0.0
 * @package MeuMouse\Joinotify\Otp_Login\Validations
 * @author MeuMouse.com
 */
class Otp_Validation {

    /**
     * OTP length
     * 
     * @since 1.0.0
     * @return int
     */
    public $otp_length;

    /**
     * OTP expire time
     * 
     * @since 1.0.0
     * @return int
     */
    public $otp_expiry_time;

    /**
     * Define transient key
     * 
     * @since 1.0.0
     * @var string
     */
    const TRANSIENT_KEY = 'joinotify_otp_login_';

    /**
     * Constructor
     * 
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        $this->otp_length = 4;
        $this->otp_expiry_time = 300; // 5 minutes
    }


    /**
     * Generate OTP code, storage for future validation and send message via WhatsApp
     *
     * @since 1.0.0
     * @param string $phone | Phone number
     * @return bool Returns true if the OTP was generated and sent successfully, false otherwise.
     */
    public function generate_and_send_otp( $phone ) {
        $otp = self::generate_otp();
        $expiration_time = time() + (int) $this->otp_expiry_time;

        // Save OTP and Expiry Time
        self::store_otp( $phone, $otp, $expiration_time );

        $sender = '';
        $message = self::set_message( $otp );

        // send message
        $send_otp = joinotify_send_whatsapp_message_text( $sender, $phone, $message );

        return 201 === $send_otp;
    }


    /**
     * Set message to sent with OTP code
     * 
     * @since 1.0.0
     * @param string $otp | OTP Code
     * @return string
     */
    public static function set_message( $otp ) {
        return '';
    }


    /**
     * Generate a random OTP code
     *
     * @since 1.0.0
     * @return string The generated OTP code
     */
    static function generate_otp() {
        return str_pad( random_int( 0, 9999 ), $this->otp_length, '0', STR_PAD_LEFT) ;
    }


    /**
     * Stores the generated OTP and its expiration time for future validation
     *
     * @since 1.0.0
     * @param string $phone | Phone number
     * @param string $otp | The generated OTP code
     * @param int $expiration_time | OTP Expiry Time in Unix Timestamp
     */
    static function store_otp( $phone, $otp, $expiration_time ) {
        set_transient( self::TRANSIENT_KEY . md5( $phone ), array( 'otp' => $otp, 'expires' => $expiration_time ), self::$otp_expiry_time );
    }


    /**
     * Validates the OTP provided by the user
     *
     * @since 1.0.0
     * @param string $phone | Phone number
     * @param string $user_provided_otp | The OTP provided by the user
     * @return bool Returns true if the OTP is valid, false otherwise
     */
    public function validate_otp( $phone, $user_provided_otp ) {
        $stored_data = get_transient( self::TRANSIENT_KEY . md5( $phone ) );

        if ( $stored_data && $stored_data['otp'] === $user_provided_otp ) {
            if ( time() <= $stored_data['expires'] ) {
                // OTP valid and within expiration time
                delete_transient( self::TRANSIENT_KEY . md5( $phone ) ); // Remove OTP after validation to prevent reuse

                return true;
            } else {
                // OTP expired
                delete_transient( self::TRANSIENT_KEY . md5( $phone ) );
            }
        }

        return false;
    }
}