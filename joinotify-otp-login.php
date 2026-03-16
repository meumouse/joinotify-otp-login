<?php

/**
 * Plugin Name: 			Joinotify OTP Login - Autenticação simplificada sem senha
 * Description: 			Permita que seus usuários façam login com segurança utilizando verificação por código enviado via WhatsApp através do Joinotify, oferecendo uma experiência rápida e sem necessidade de senha.
 * Plugin URI: 				https://meumouse.com/plugins/joinotify/?utm_source=wordpress&utm_medium=plugin_list&utm_campaign=joinotify_otp_login
 * Requires Plugins: 		woocommerce
 * Author: 					MeuMouse.com
 * Author URI: 				https://meumouse.com/?utm_source=wordpress&utm_medium=plugin_list&utm_campaign=joinotify_otp_login
 * Version: 				1.0.0
 * Requires PHP: 			7.4
 * Tested up to:      		6.9.4
 * Text Domain: 			joinotify-otp-login
 * Domain Path: 			/languages
 * License:                 GPLv2 or later
 * License URI:             https://www.gnu.org/licenses/gpl-2.0.html
 */

use MeuMouse\Joinotify\Otp_Login\Core\Plugin;

defined('ABSPATH') || exit;

// Load Composer autoloader if available.
$autoload = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

$plugin_version = '1.0.0';

// Initialize the plugin
new Plugin( __FILE__, $plugin_version );