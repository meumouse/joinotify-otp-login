<?php

/**
 * Plugin Name:           Joinotify OTP Login - Passwordless authentication
 * Description:           Let your users log in securely with a verification code sent via WhatsApp through Joinotify, offering a fast passwordless experience.
 * Plugin URI:            https://meumouse.com/plugins/joinotify/?utm_source=wordpress&utm_medium=plugin_list&utm_campaign=joinotify_otp_login
 * Requires Plugins:      joinotify
 * Author:                MeuMouse.com
 * Author URI:            https://meumouse.com/?utm_source=wordpress&utm_medium=plugin_list&utm_campaign=joinotify_otp_login
 * Version:               1.0.0
 * Requires PHP:          7.4
 * Tested up to:          6.9.4
 * Text Domain:           joinotify-otp-login
 * Domain Path:           /languages
 * License:               GPLv2 or later
 * License URI:           https://www.gnu.org/licenses/gpl-2.0.html
 */

use MeuMouse\Joinotify\Otp_Login\Core\Plugin;

defined('ABSPATH') || exit;

$autoload = plugin_dir_path( __FILE__ ) . 'admin/vendor/autoload.php';

if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

$plugin_version = '1.0.0';

new Plugin( __FILE__, $plugin_version );
